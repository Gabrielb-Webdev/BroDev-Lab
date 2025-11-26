/**
 * WebSocket Client para BroDev Lab
 * Reemplazo del sistema de polling con sincronización instantánea
 * 
 * Ventajas vs Polling:
 * - Latencia < 50ms vs 3000ms
 * - Bi-direccional (servidor puede pushear)
 * - 95% menos tráfico de red
 * - Reconexión automática
 */

interface WebSocketMessage {
    type: string;
    payload?: any;
    [key: string]: any;
}

interface FieldUpdate {
    type: 'field-updated';
    entityType: string;
    entityId: number;
    fieldId: number;
    fieldName: string;
    oldValue: any;
    newValue: any;
    changedBy?: number;
    timestamp: string;
}

interface FieldCreated {
    type: 'field-created';
    entityType: string;
    fieldId: number;
    fieldData: any;
    timestamp: string;
}

interface FieldDeleted {
    type: 'field-deleted';
    entityType: string;
    fieldId: number;
    timestamp: string;
}

type EntityChange = FieldUpdate | FieldCreated | FieldDeleted;

class WebSocketClient {
    private ws: WebSocket | null = null;
    private clientId: string | null = null;
    private reconnectAttempts: number = 0;
    private maxReconnectAttempts: number = 10;
    private reconnectDelay: number = 1000;
    private pingInterval: NodeJS.Timer | null = null;
    private isConnecting: boolean = false;
    
    // Callbacks
    private onConnectCallbacks: Array<() => void> = [];
    private onDisconnectCallbacks: Array<() => void> = [];
    private onUpdateCallbacks: Array<(change: EntityChange) => void> = [];
    private onErrorCallbacks: Array<(error: Error) => void> = [];
    
    // Estado
    private subscribedEntities: Set<string> = new Set();
    private wsUrl: string;
    
    constructor(wsUrl: string = 'ws://localhost:8080') {
        this.wsUrl = wsUrl;
    }
    
    /**
     * Conectar al servidor WebSocket
     */
    connect(): Promise<void> {
        return new Promise((resolve, reject) => {
            if (this.isConnecting) {
                console.warn('⚠️ Ya hay una conexión en progreso');
                return;
            }
            
            if (this.ws && this.ws.readyState === WebSocket.OPEN) {
                console.warn('⚠️ Ya estás conectado');
                resolve();
                return;
            }
            
            this.isConnecting = true;
            console.log(`🔌 Conectando a WebSocket: ${this.wsUrl}`);
            
            try {
                this.ws = new WebSocket(this.wsUrl);
                
                this.ws.onopen = () => {
                    this.isConnecting = false;
                    this.reconnectAttempts = 0;
                    console.log('✅ WebSocket conectado');
                    this.startPing();
                    this.onConnectCallbacks.forEach(cb => cb());
                    resolve();
                };
                
                this.ws.onmessage = (event) => {
                    try {
                        const message: WebSocketMessage = JSON.parse(event.data);
                        this.handleMessage(message);
                    } catch (error) {
                        console.error('❌ Error parseando mensaje:', error);
                    }
                };
                
                this.ws.onerror = (error) => {
                    this.isConnecting = false;
                    console.error('❌ Error en WebSocket:', error);
                    this.onErrorCallbacks.forEach(cb => cb(new Error('WebSocket error')));
                    reject(error);
                };
                
                this.ws.onclose = (event) => {
                    this.isConnecting = false;
                    console.log(`🔌 WebSocket cerrado: ${event.code} - ${event.reason}`);
                    this.stopPing();
                    this.onDisconnectCallbacks.forEach(cb => cb());
                    
                    // Reconexión automática
                    if (!event.wasClean && this.reconnectAttempts < this.maxReconnectAttempts) {
                        const delay = this.reconnectDelay * Math.pow(2, this.reconnectAttempts);
                        console.log(`🔄 Reconectando en ${delay}ms... (intento ${this.reconnectAttempts + 1}/${this.maxReconnectAttempts})`);
                        
                        setTimeout(() => {
                            this.reconnectAttempts++;
                            this.connect().catch(err => {
                                console.error('❌ Error en reconexión:', err);
                            });
                        }, delay);
                    } else if (this.reconnectAttempts >= this.maxReconnectAttempts) {
                        console.error('❌ Máximo de intentos de reconexión alcanzado');
                        this.showReconnectDialog();
                    }
                };
                
            } catch (error) {
                this.isConnecting = false;
                reject(error);
            }
        });
    }
    
    /**
     * Desconectar del servidor
     */
    disconnect(): void {
        if (this.ws) {
            this.reconnectAttempts = this.maxReconnectAttempts; // Evitar reconexión automática
            this.ws.close(1000, 'Desconexión intencional');
            this.ws = null;
            this.stopPing();
        }
    }
    
    /**
     * Suscribirse a cambios de entidades
     */
    subscribe(entityTypes: string[]): void {
        entityTypes.forEach(type => this.subscribedEntities.add(type));
        
        this.send({
            type: 'subscribe',
            payload: { entityTypes }
        });
        
        console.log(`📡 Suscrito a: ${entityTypes.join(', ')}`);
    }
    
    /**
     * Desuscribirse de entidades
     */
    unsubscribe(entityTypes: string[]): void {
        entityTypes.forEach(type => this.subscribedEntities.delete(type));
        
        this.send({
            type: 'unsubscribe',
            payload: { entityTypes }
        });
        
        console.log(`📡 Desuscrito de: ${entityTypes.join(', ')}`);
    }
    
    /**
     * Actualizar valor de campo
     */
    async updateField(fieldId: number, entityId: number, value: any, changedBy?: number): Promise<void> {
        this.send({
            type: 'update-field',
            payload: { fieldId, entityId, value, changedBy }
        });
    }
    
    /**
     * Crear campo
     */
    async createField(entityType: string, fieldData: any): Promise<void> {
        this.send({
            type: 'create-field',
            payload: { entityType, fieldData }
        });
    }
    
    /**
     * Eliminar campo
     */
    async deleteField(fieldId: number): Promise<void> {
        this.send({
            type: 'delete-field',
            payload: { fieldId }
        });
    }
    
    /**
     * Solicitar sincronización
     */
    requestSync(entityType: string, lastSync: string): void {
        this.send({
            type: 'sync-request',
            payload: { entityType, lastSync }
        });
    }
    
    /**
     * Enviar mensaje al servidor
     */
    private send(message: WebSocketMessage): void {
        if (!this.ws || this.ws.readyState !== WebSocket.OPEN) {
            console.error('❌ WebSocket no conectado');
            this.showConnectionError();
            return;
        }
        
        this.ws.send(JSON.stringify(message));
    }
    
    /**
     * Manejar mensaje del servidor
     */
    private handleMessage(message: WebSocketMessage): void {
        switch (message.type) {
            case 'connected':
                this.clientId = message.clientId;
                console.log(`🆔 Client ID asignado: ${this.clientId}`);
                
                // Re-suscribirse después de reconexión
                if (this.subscribedEntities.size > 0) {
                    this.subscribe(Array.from(this.subscribedEntities));
                }
                break;
                
            case 'field-updated':
            case 'field-created':
            case 'field-deleted':
                console.log(`📥 Cambio recibido: ${message.type}`, message);
                this.onUpdateCallbacks.forEach(cb => cb(message as EntityChange));
                this.showUpdateNotification(message);
                break;
                
            case 'sync-response':
                console.log(`📥 Sync response: ${message.updates?.length || 0} updates`);
                message.updates?.forEach((update: any) => {
                    this.onUpdateCallbacks.forEach(cb => cb(update));
                });
                break;
                
            case 'pong':
                // Respuesta a ping, conexión activa
                break;
                
            case 'error':
                console.error('❌ Error del servidor:', message.error);
                this.onErrorCallbacks.forEach(cb => cb(new Error(message.error)));
                break;
                
            default:
                console.warn(`⚠️ Tipo de mensaje desconocido: ${message.type}`);
        }
    }
    
    /**
     * Mantener conexión viva con pings
     */
    private startPing(): void {
        this.stopPing();
        this.pingInterval = setInterval(() => {
            if (this.ws && this.ws.readyState === WebSocket.OPEN) {
                this.send({ type: 'ping' });
            }
        }, 30000); // Cada 30 segundos
    }
    
    private stopPing(): void {
        if (this.pingInterval) {
            clearInterval(this.pingInterval);
            this.pingInterval = null;
        }
    }
    
    /**
     * Callbacks
     */
    onConnect(callback: () => void): void {
        this.onConnectCallbacks.push(callback);
    }
    
    onDisconnect(callback: () => void): void {
        this.onDisconnectCallbacks.push(callback);
    }
    
    onUpdate(callback: (change: EntityChange) => void): void {
        this.onUpdateCallbacks.push(callback);
    }
    
    onError(callback: (error: Error) => void): void {
        this.onErrorCallbacks.push(callback);
    }
    
    /**
     * UI Notifications
     */
    private showUpdateNotification(change: EntityChange): void {
        const notification = document.createElement('div');
        notification.className = 'ws-notification';
        
        let message = '';
        let icon = '🔄';
        
        switch (change.type) {
            case 'field-updated':
                message = `Campo actualizado en ${change.entityType}`;
                icon = '✏️';
                break;
            case 'field-created':
                message = `Nueva columna agregada: ${change.fieldData?.field_label || ''}`;
                icon = '➕';
                break;
            case 'field-deleted':
                message = `Columna eliminada`;
                icon = '🗑️';
                break;
        }
        
        notification.innerHTML = `
            <span class="ws-icon">${icon}</span>
            <span class="ws-message">${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => notification.classList.add('show'), 10);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    private showConnectionError(): void {
        const exists = document.getElementById('ws-connection-error');
        if (exists) return;
        
        const error = document.createElement('div');
        error.id = 'ws-connection-error';
        error.className = 'ws-error-banner';
        error.innerHTML = `
            <span>⚠️ Sin conexión al servidor. Los cambios no se sincronizarán.</span>
            <button onclick="window.wsClient.connect()">Reconectar</button>
        `;
        
        document.body.prepend(error);
    }
    
    private showReconnectDialog(): void {
        const dialog = document.createElement('div');
        dialog.className = 'ws-reconnect-dialog';
        dialog.innerHTML = `
            <div class="ws-dialog-content">
                <h3>❌ Conexión Perdida</h3>
                <p>No se pudo reconectar al servidor después de ${this.maxReconnectAttempts} intentos.</p>
                <p>Los cambios no se sincronizarán hasta que te reconectes.</p>
                <button onclick="window.location.reload()" class="btn btn-primary">Recargar Página</button>
                <button onclick="window.wsClient.connect(); this.closest('.ws-reconnect-dialog').remove()" class="btn btn-secondary">Intentar Reconectar</button>
            </div>
        `;
        
        document.body.appendChild(dialog);
    }
    
    /**
     * Estado de la conexión
     */
    get isConnected(): boolean {
        return this.ws !== null && this.ws.readyState === WebSocket.OPEN;
    }
    
    get connectionState(): string {
        if (!this.ws) return 'disconnected';
        
        switch (this.ws.readyState) {
            case WebSocket.CONNECTING: return 'connecting';
            case WebSocket.OPEN: return 'connected';
            case WebSocket.CLOSING: return 'closing';
            case WebSocket.CLOSED: return 'closed';
            default: return 'unknown';
        }
    }
}

// Exportar para uso global
if (typeof window !== 'undefined') {
    (window as any).WebSocketClient = WebSocketClient;
}

export default WebSocketClient;
