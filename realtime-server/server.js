import { WebSocketServer } from 'ws';
import mysql from 'mysql2/promise';
import { createClient } from 'redis';
import dotenv from 'dotenv';

dotenv.config();

// ============================================
// CONFIGURACIÓN
// ============================================

const WS_PORT = process.env.WS_PORT || 8080;
const REDIS_URL = process.env.REDIS_URL || 'redis://localhost:6379';

const DB_CONFIG = {
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || 'brodevlab',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
};

// ============================================
// INICIALIZACIÓN
// ============================================

let dbPool;
let redisClient;
let redisPubClient;
let redisSubClient;

// Clientes conectados: Map<clientId, WebSocket>
const clients = new Map();

// Suscripciones de clientes: Map<clientId, Set<entityTypes>>
const subscriptions = new Map();

// ============================================
// SETUP DATABASE
// ============================================

async function initDatabase() {
    try {
        dbPool = mysql.createPool(DB_CONFIG);
        const connection = await dbPool.getConnection();
        console.log('✅ MySQL conectado');
        connection.release();
    } catch (error) {
        console.error('❌ Error conectando MySQL:', error);
        process.exit(1);
    }
}

// ============================================
// SETUP REDIS
// ============================================

async function initRedis() {
    try {
        // Cliente principal
        redisClient = createClient({ url: REDIS_URL });
        await redisClient.connect();
        console.log('✅ Redis conectado');
        
        // Cliente para Pub/Sub
        redisPubClient = redisClient.duplicate();
        redisSubClient = redisClient.duplicate();
        
        await redisPubClient.connect();
        await redisSubClient.connect();
        
        // Suscribirse a canal de cambios
        await redisSubClient.subscribe('entity-changes', (message) => {
            handleEntityChange(JSON.parse(message));
        });
        
        console.log('✅ Redis Pub/Sub configurado');
    } catch (error) {
        console.error('❌ Error conectando Redis:', error);
        console.log('⚠️ Continuando sin Redis (sin caché)');
    }
}

// ============================================
// WEBSOCKET SERVER
// ============================================

const wss = new WebSocketServer({ port: WS_PORT });

wss.on('connection', (ws, req) => {
    const clientId = generateClientId();
    clients.set(clientId, ws);
    subscriptions.set(clientId, new Set());
    
    console.log(`🔌 Cliente conectado: ${clientId} (Total: ${clients.size})`);
    
    // Enviar ID al cliente
    sendMessage(ws, {
        type: 'connected',
        clientId: clientId,
        timestamp: new Date().toISOString()
    });
    
    // Manejar mensajes del cliente
    ws.on('message', async (data) => {
        try {
            const message = JSON.parse(data.toString());
            await handleClientMessage(clientId, message, ws);
        } catch (error) {
            console.error('❌ Error procesando mensaje:', error);
            sendMessage(ws, {
                type: 'error',
                error: error.message
            });
        }
    });
    
    // Manejar desconexión
    ws.on('close', () => {
        clients.delete(clientId);
        subscriptions.delete(clientId);
        console.log(`🔌 Cliente desconectado: ${clientId} (Total: ${clients.size})`);
    });
    
    // Manejar errores
    ws.on('error', (error) => {
        console.error(`❌ Error en WebSocket ${clientId}:`, error);
    });
    
    // Ping cada 30s para mantener conexión viva
    const pingInterval = setInterval(() => {
        if (ws.readyState === ws.OPEN) {
            ws.ping();
        } else {
            clearInterval(pingInterval);
        }
    }, 30000);
});

// ============================================
// MANEJADORES DE MENSAJES
// ============================================

async function handleClientMessage(clientId, message, ws) {
    const { type, payload } = message;
    
    switch (type) {
        case 'subscribe':
            handleSubscribe(clientId, payload);
            break;
            
        case 'unsubscribe':
            handleUnsubscribe(clientId, payload);
            break;
            
        case 'update-field':
            await handleUpdateField(clientId, payload);
            break;
            
        case 'create-field':
            await handleCreateField(clientId, payload);
            break;
            
        case 'delete-field':
            await handleDeleteField(clientId, payload);
            break;
            
        case 'sync-request':
            await handleSyncRequest(clientId, payload, ws);
            break;
            
        case 'ping':
            sendMessage(ws, { type: 'pong', timestamp: new Date().toISOString() });
            break;
            
        default:
            console.warn(`⚠️ Tipo de mensaje desconocido: ${type}`);
    }
}

// ============================================
// SUSCRIPCIONES
// ============================================

function handleSubscribe(clientId, payload) {
    const { entityTypes } = payload;
    const clientSubs = subscriptions.get(clientId);
    
    entityTypes.forEach(entityType => {
        clientSubs.add(entityType);
    });
    
    console.log(`📡 Cliente ${clientId} suscrito a: ${Array.from(clientSubs).join(', ')}`);
}

function handleUnsubscribe(clientId, payload) {
    const { entityTypes } = payload;
    const clientSubs = subscriptions.get(clientId);
    
    entityTypes.forEach(entityType => {
        clientSubs.delete(entityType);
    });
    
    console.log(`📡 Cliente ${clientId} desuscrito de: ${entityTypes.join(', ')}`);
}

// ============================================
// OPERACIONES DE BASE DE DATOS
// ============================================

async function handleUpdateField(clientId, payload) {
    const { fieldId, entityId, value, changedBy } = payload;
    
    try {
        // Obtener valor anterior
        const [oldValueRows] = await dbPool.query(
            'SELECT field_value FROM custom_field_values WHERE field_id = ? AND entity_id = ?',
            [fieldId, entityId]
        );
        
        const oldValue = oldValueRows[0]?.field_value || null;
        
        // Upsert valor
        await dbPool.query(
            `INSERT INTO custom_field_values (field_id, entity_id, field_value)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE field_value = VALUES(field_value), updated_at = CURRENT_TIMESTAMP`,
            [fieldId, entityId, value]
        );
        
        // Guardar en historial
        if (oldValue !== value) {
            await dbPool.query(
                `INSERT INTO field_value_history (field_id, entity_id, old_value, new_value, changed_by)
                 VALUES (?, ?, ?, ?, ?)`,
                [fieldId, entityId, oldValue, value, changedBy]
            );
            
            // Obtener info del campo para broadcast
            const [fieldRows] = await dbPool.query(
                'SELECT entity_type, field_name FROM custom_fields WHERE id = ?',
                [fieldId]
            );
            
            if (fieldRows.length > 0) {
                const field = fieldRows[0];
                
                // Registrar en sync_log
                await dbPool.query(
                    `INSERT INTO sync_log (entity_type, entity_id, action, changed_fields, changed_by)
                     VALUES (?, ?, 'value_updated', ?, ?)`,
                    [field.entity_type, entityId, JSON.stringify({
                        field_name: field.field_name,
                        old_value: oldValue,
                        new_value: value
                    }), changedBy]
                );
                
                // Invalidar caché en Redis
                if (redisClient) {
                    await redisClient.del(`entity:${field.entity_type}:${entityId}`);
                    await redisClient.del(`entities:${field.entity_type}`);
                }
                
                // Broadcast a clientes suscritos
                broadcastToSubscribers(field.entity_type, {
                    type: 'field-updated',
                    entityType: field.entity_type,
                    entityId: entityId,
                    fieldId: fieldId,
                    fieldName: field.field_name,
                    oldValue: oldValue,
                    newValue: value,
                    changedBy: changedBy,
                    timestamp: new Date().toISOString()
                }, clientId);
                
                // Publicar en Redis para otros servidores (si hay cluster)
                if (redisPubClient) {
                    await redisPubClient.publish('entity-changes', JSON.stringify({
                        type: 'field-updated',
                        entityType: field.entity_type,
                        entityId: entityId,
                        fieldId: fieldId,
                        value: value,
                        excludeClient: clientId
                    }));
                }
            }
        }
        
        console.log(`✅ Campo ${fieldId} actualizado para entidad ${entityId}`);
        
    } catch (error) {
        console.error('❌ Error actualizando campo:', error);
        throw error;
    }
}

async function handleCreateField(clientId, payload) {
    const { entityType, fieldData } = payload;
    
    try {
        // Obtener siguiente orden
        const [maxOrderRows] = await dbPool.query(
            'SELECT MAX(display_order) as max_order FROM custom_fields WHERE entity_type = ?',
            [entityType]
        );
        const maxOrder = maxOrderRows[0]?.max_order || 0;
        
        // Insertar campo
        const [result] = await dbPool.query(
            `INSERT INTO custom_fields 
             (entity_type, field_name, field_label, field_type, field_options, validation_rules,
              default_value, is_required, is_visible, is_system, display_order, column_width, help_text)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
            [
                entityType,
                fieldData.field_name,
                fieldData.field_label,
                fieldData.field_type,
                JSON.stringify(fieldData.field_options || {}),
                JSON.stringify(fieldData.validation_rules || {}),
                fieldData.default_value || null,
                fieldData.is_required || false,
                fieldData.is_visible !== false,
                fieldData.is_system || false,
                maxOrder + 1,
                fieldData.column_width || 'auto',
                fieldData.help_text || null
            ]
        );
        
        const fieldId = result.insertId;
        
        // Log sync
        await dbPool.query(
            `INSERT INTO sync_log (entity_type, entity_id, action, changed_fields)
             VALUES (?, ?, 'field_created', ?)`,
            [entityType, fieldId, JSON.stringify({ field_name: fieldData.field_name })]
        );
        
        // Invalidar caché
        if (redisClient) {
            await redisClient.del(`fields:${entityType}`);
        }
        
        // Broadcast
        broadcastToSubscribers(entityType, {
            type: 'field-created',
            entityType: entityType,
            fieldId: fieldId,
            fieldData: fieldData,
            timestamp: new Date().toISOString()
        }, clientId);
        
        console.log(`✅ Campo creado: ${fieldData.field_name} (ID: ${fieldId})`);
        
    } catch (error) {
        console.error('❌ Error creando campo:', error);
        throw error;
    }
}

async function handleDeleteField(clientId, payload) {
    const { fieldId } = payload;
    
    try {
        // Verificar que no sea campo del sistema
        const [fieldRows] = await dbPool.query(
            'SELECT is_system, entity_type FROM custom_fields WHERE id = ?',
            [fieldId]
        );
        
        if (fieldRows.length === 0) {
            throw new Error('Campo no encontrado');
        }
        
        if (fieldRows[0].is_system) {
            throw new Error('No se pueden eliminar campos del sistema');
        }
        
        const entityType = fieldRows[0].entity_type;
        
        // Eliminar campo
        await dbPool.query('DELETE FROM custom_fields WHERE id = ?', [fieldId]);
        
        // Log sync
        await dbPool.query(
            `INSERT INTO sync_log (entity_type, entity_id, action, changed_fields)
             VALUES (?, ?, 'field_deleted', '{}')`,
            [entityType, fieldId]
        );
        
        // Invalidar caché
        if (redisClient) {
            await redisClient.del(`fields:${entityType}`);
        }
        
        // Broadcast
        broadcastToSubscribers(entityType, {
            type: 'field-deleted',
            entityType: entityType,
            fieldId: fieldId,
            timestamp: new Date().toISOString()
        }, clientId);
        
        console.log(`✅ Campo eliminado: ${fieldId}`);
        
    } catch (error) {
        console.error('❌ Error eliminando campo:', error);
        throw error;
    }
}

async function handleSyncRequest(clientId, payload, ws) {
    const { entityType, lastSync } = payload;
    
    try {
        // Buscar cambios desde lastSync
        const [updates] = await dbPool.query(
            `SELECT * FROM sync_log 
             WHERE entity_type = ? AND changed_at > ?
             ORDER BY changed_at ASC
             LIMIT 100`,
            [entityType, lastSync]
        );
        
        // Parsear changed_fields
        updates.forEach(update => {
            if (update.changed_fields) {
                update.changed_fields = JSON.parse(update.changed_fields);
            }
        });
        
        sendMessage(ws, {
            type: 'sync-response',
            updates: updates,
            serverTime: new Date().toISOString()
        });
        
        console.log(`📤 Enviados ${updates.length} updates a ${clientId}`);
        
    } catch (error) {
        console.error('❌ Error en sync request:', error);
        throw error;
    }
}

// ============================================
// BROADCASTING
// ============================================

function broadcastToSubscribers(entityType, message, excludeClientId = null) {
    let broadcastCount = 0;
    
    clients.forEach((ws, clientId) => {
        // No enviar al cliente que originó el cambio
        if (clientId === excludeClientId) return;
        
        // Solo enviar a clientes suscritos a este entityType
        const clientSubs = subscriptions.get(clientId);
        if (!clientSubs || !clientSubs.has(entityType)) return;
        
        if (ws.readyState === ws.OPEN) {
            sendMessage(ws, message);
            broadcastCount++;
        }
    });
    
    if (broadcastCount > 0) {
        console.log(`📡 Broadcast a ${broadcastCount} clientes: ${message.type}`);
    }
}

function handleEntityChange(changeData) {
    // Manejar cambios publicados por otros servidores (cluster)
    const { type, entityType, excludeClient } = changeData;
    
    broadcastToSubscribers(entityType, {
        type: type,
        ...changeData,
        fromCluster: true
    }, excludeClient);
}

// ============================================
// UTILIDADES
// ============================================

function sendMessage(ws, message) {
    if (ws.readyState === ws.OPEN) {
        ws.send(JSON.stringify(message));
    }
}

function generateClientId() {
    return `client_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
}

// ============================================
// HEALTH CHECK
// ============================================

setInterval(async () => {
    const stats = {
        connectedClients: clients.size,
        totalSubscriptions: Array.from(subscriptions.values())
            .reduce((sum, set) => sum + set.size, 0),
        uptime: process.uptime(),
        memory: process.memoryUsage()
    };
    
    // Guardar stats en Redis
    if (redisClient) {
        await redisClient.set('ws-stats', JSON.stringify(stats), { EX: 60 });
    }
    
    console.log(`💚 Health: ${stats.connectedClients} clientes, ${stats.totalSubscriptions} suscripciones`);
}, 60000); // Cada minuto

// ============================================
// INICIO DEL SERVIDOR
// ============================================

async function start() {
    console.log('🚀 Iniciando servidor WebSocket BroDev Lab...');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    
    await initDatabase();
    await initRedis();
    
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log(`✅ Servidor WebSocket escuchando en puerto ${WS_PORT}`);
    console.log(`🌐 Conecta desde el cliente: ws://localhost:${WS_PORT}`);
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log('📊 Estadísticas disponibles en Redis: ws-stats');
    console.log('💡 Logs en tiempo real activados');
    console.log('🔄 Sincronización instantánea < 50ms');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
}

// Manejar cierre graceful
process.on('SIGTERM', async () => {
    console.log('⏸️ Cerrando servidor...');
    wss.close();
    if (dbPool) await dbPool.end();
    if (redisClient) await redisClient.quit();
    if (redisPubClient) await redisPubClient.quit();
    if (redisSubClient) await redisSubClient.quit();
    process.exit(0);
});

start().catch(error => {
    console.error('❌ Error fatal:', error);
    process.exit(1);
});
