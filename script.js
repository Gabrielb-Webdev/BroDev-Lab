// ============================================
// NAVIGATION & SCROLL EFFECTS
// ============================================

// Navbar scroll effect
const navbar = document.getElementById('navbar');
const heroSection = document.querySelector('.hero');
let lastScroll = 0;

window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset;
    
    // Add background effect when scrolling
    if (currentScroll > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
    
    lastScroll = currentScroll;
});

// Mobile menu toggle
const hamburger = document.getElementById('hamburger');
const navMenu = document.getElementById('navMenu');

hamburger.addEventListener('click', () => {
    navMenu.classList.toggle('active');
    
    // Animate hamburger
    const spans = hamburger.querySelectorAll('span');
    if (navMenu.classList.contains('active')) {
        spans[0].style.transform = 'rotate(45deg) translateY(10px)';
        spans[1].style.opacity = '0';
        spans[2].style.transform = 'rotate(-45deg) translateY(-10px)';
    } else {
        spans[0].style.transform = 'none';
        spans[1].style.opacity = '1';
        spans[2].style.transform = 'none';
    }
});

// Close mobile menu when clicking on a link
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
        navMenu.classList.remove('active');
        const spans = hamburger.querySelectorAll('span');
        spans[0].style.transform = 'none';
        spans[1].style.opacity = '1';
        spans[2].style.transform = 'none';
    });
});

// Active nav link on scroll
const sections = document.querySelectorAll('section[id]');
const navLinks = document.querySelectorAll('.nav-link');

window.addEventListener('scroll', () => {
    let current = '';
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.clientHeight;
        if (window.pageYOffset >= sectionTop - 200) {
            current = section.getAttribute('id');
        }
    });

    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === `#${current}`) {
            link.classList.add('active');
        }
    });
});

// ============================================
// SMOOTH SCROLL
// ============================================

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// ============================================
// SCROLL REVEAL ANIMATIONS
// ============================================

const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            
            // Animate stats counter
            if (entry.target.classList.contains('stat-item')) {
                animateCounter(entry.target);
            }
        }
    });
}, observerOptions);

// Observe all elements with data-scroll attribute
document.querySelectorAll('[data-scroll]').forEach(el => {
    observer.observe(el);
});

// Observe stat items
document.querySelectorAll('.stat-item').forEach(el => {
    observer.observe(el);
});

// Observe service cards
document.querySelectorAll('.service-card').forEach(el => {
    observer.observe(el);
});

// Observe portfolio items
document.querySelectorAll('.portfolio-item').forEach(el => {
    observer.observe(el);
});

// Observe testimonial cards
document.querySelectorAll('.testimonial-card').forEach(el => {
    observer.observe(el);
});

// ============================================
// COUNTER ANIMATION
// ============================================

function animateCounter(element) {
    const numberElement = element.querySelector('.stat-number');
    const target = parseInt(numberElement.getAttribute('data-count'));
    const duration = 1200; // Más rápido: de 2000ms a 1200ms
    const startTime = performance.now();
    
    function easeOutQuart(x) {
        return 1 - Math.pow(1 - x, 4); // Curva natural
    }

    function updateCounter(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const easedProgress = easeOutQuart(progress);
        const current = Math.floor(easedProgress * target);
        
        numberElement.textContent = current;
        
        if (progress < 1) {
            requestAnimationFrame(updateCounter);
        } else {
            numberElement.textContent = target;
        }
    }
    
    requestAnimationFrame(updateCounter);
}

// ============================================
// 3D TILT EFFECT
// ============================================

const tiltElements = document.querySelectorAll('[data-tilt]');

tiltElements.forEach(element => {
    element.addEventListener('mousemove', (e) => {
        const rect = element.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        const centerX = rect.width / 2;
        const centerY = rect.height / 2;

        const rotateX = ((y - centerY) / centerY) * -10;
        const rotateY = ((x - centerX) / centerX) * 10;

        element.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.05, 1.05, 1.05)`;
    });

    element.addEventListener('mouseleave', () => {
        element.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale3d(1, 1, 1)';
    });
});

// ============================================
// FORM VALIDATION & SUBMISSION
// ============================================

const contactForm = document.getElementById('contactForm');

if (contactForm) {
    contactForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const submitBtn = contactForm.querySelector('.btn-submit');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.innerHTML = '<span>Enviando...</span>';
        submitBtn.disabled = true;
        
        // Get form data
        const formData = {
            name: document.getElementById('name').value,
            email: document.getElementById('email').value,
            company: document.getElementById('company').value,
            service: document.getElementById('service').value,
            message: document.getElementById('message').value
        };
        
        // Simulate form submission (replace with actual API call)
        setTimeout(() => {
            // Show success message
            submitBtn.innerHTML = '<span>¡Enviado! ✓</span>';
            submitBtn.style.background = 'linear-gradient(135deg, #10B981 0%, #059669 100%)';
            
            // Reset form
            contactForm.reset();
            
            // Reset button after 3 seconds
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.style.background = '';
                submitBtn.disabled = false;
            }, 3000);
            
            // Log form data (for testing)
            console.log('Form submitted:', formData);
            
            // Here you would typically send the data to your backend
            // Example:
            // fetch('your-api-endpoint', {
            //     method: 'POST',
            //     headers: { 'Content-Type': 'application/json' },
            //     body: JSON.stringify(formData)
            // });
            
        }, 1500);
    });
    
    // Real-time validation
    const inputs = contactForm.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.addEventListener('blur', () => {
            if (input.required && !input.value) {
                input.style.borderColor = '#EF4444';
            } else if (input.type === 'email' && !isValidEmail(input.value)) {
                input.style.borderColor = '#EF4444';
            } else {
                input.style.borderColor = 'rgba(124, 58, 237, 0.2)';
            }
        });
        
        input.addEventListener('focus', () => {
            input.style.borderColor = 'var(--primary)';
        });
    });
}

function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// ============================================
// CTA BUTTON CLICK
// ============================================

document.querySelectorAll('.cta-button').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        const contactSection = document.getElementById('contacto');
        if (contactSection) {
            contactSection.scrollIntoView({ behavior: 'smooth' });
        }
    });
});

// ============================================
// FLOATING ANIMATION
// ============================================

// Add subtle floating animation to service icons
document.querySelectorAll('.service-icon').forEach((icon, index) => {
    const delay = index * 0.2;
    icon.style.animation = `float 3s ease-in-out ${delay}s infinite`;
});

// Add floating animation keyframes dynamically
const style = document.createElement('style');
style.textContent = `
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
`;
document.head.appendChild(style);

// ============================================
// CURSOR EFFECTS (OPTIONAL)
// ============================================

const cursorFollower = document.createElement('div');
cursorFollower.classList.add('cursor-follower');
document.body.appendChild(cursorFollower);

// Add cursor styles
const cursorStyle = document.createElement('style');
cursorStyle.textContent = `
    .cursor-follower {
        width: 40px;
        height: 40px;
        border: 2px solid var(--primary);
        border-radius: 50%;
        position: fixed;
        pointer-events: none;
        z-index: 9998;
        transition: transform 0.15s ease-out, opacity 0.2s ease;
        display: none;
    }
    
    .cursor-follower::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--primary);
    }
    
    @media (min-width: 1024px) {
        .cursor-follower {
            display: block;
        }
        body {
            cursor: none;
        }
        a, button, .service-card, .portfolio-item {
            cursor: none;
        }
    }
`;
document.head.appendChild(cursorStyle);

let mouseX = 0;
let mouseY = 0;
let followerX = 0;
let followerY = 0;

document.addEventListener('mousemove', (e) => {
    mouseX = e.clientX;
    mouseY = e.clientY;
});

function animateCursorFollower() {
    const distX = mouseX - followerX;
    const distY = mouseY - followerY;
    
    // Cambio de 10 a 4 para hacerlo más rápido (menor número = más rápido)
    followerX += distX / 4;
    followerY += distY / 4;
    
    cursorFollower.style.left = followerX - 20 + 'px';
    cursorFollower.style.top = followerY - 20 + 'px';
    
    requestAnimationFrame(animateCursorFollower);
}

animateCursorFollower();

// Scale cursor on hover
document.querySelectorAll('a, button, .service-card, .portfolio-item').forEach(element => {
    element.addEventListener('mouseenter', () => {
        cursorFollower.style.transform = 'scale(1.5)';
    });
    
    element.addEventListener('mouseleave', () => {
        cursorFollower.style.transform = 'scale(1)';
    });
});

// ============================================
// PERFORMANCE OPTIMIZATION
// ============================================

// Lazy load images (if you add actual images later)
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            }
        });
    });

    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}

// ============================================
// PAGE LOAD ANIMATION
// ============================================

window.addEventListener('load', () => {
    document.body.classList.add('loaded');
    
    // Animate hero elements on load
    setTimeout(() => {
        document.querySelector('.hero-text')?.classList.add('visible');
        document.querySelector('.hero-visual')?.classList.add('visible');
    }, 100);
});

// ============================================
// SCROLL TO TOP BUTTON (Optional Enhancement)
// ============================================

const createScrollToTop = () => {
    const scrollBtn = document.createElement('button');
    scrollBtn.innerHTML = '↑';
    scrollBtn.classList.add('scroll-to-top');
    scrollBtn.setAttribute('aria-label', 'Scroll to top');
    document.body.appendChild(scrollBtn);
    
    const scrollBtnStyle = document.createElement('style');
    scrollBtnStyle.textContent = `
        .scroll-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: var(--gradient-primary);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition-normal);
            z-index: 999;
            box-shadow: var(--shadow-lg);
        }
        
        .scroll-to-top.visible {
            opacity: 1;
            visibility: visible;
        }
        
        .scroll-to-top:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-glow);
        }
    `;
    document.head.appendChild(scrollBtnStyle);
    
    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 500) {
            scrollBtn.classList.add('visible');
        } else {
            scrollBtn.classList.remove('visible');
        }
    });
    
    scrollBtn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
};

createScrollToTop();

// ============================================
// CONSOLE MESSAGE
// ============================================

console.log(
    '%c🚀 BroDev Lab',
    'color: #7C3AED; font-size: 24px; font-weight: bold;'
);
console.log(
    '%c¿Te gusta lo que ves? ¡Contáctanos!',
    'color: #EC4899; font-size: 14px;'
);
console.log(
    '%ccontacto@brodevlab.com',
    'color: #8B5CF6; font-size: 12px;'
);

// ============================================
// EASTER EGG - KONAMI CODE
// ============================================

const konamiCode = ['ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight', 'b', 'a'];
let konamiIndex = 0;

document.addEventListener('keydown', (e) => {
    if (e.key === konamiCode[konamiIndex]) {
        konamiIndex++;
        if (konamiIndex === konamiCode.length) {
            activateEasterEgg();
            konamiIndex = 0;
        }
    } else {
        konamiIndex = 0;
    }
});

function activateEasterEgg() {
    document.body.style.animation = 'rainbow 2s linear infinite';
    const easterStyle = document.createElement('style');
    easterStyle.textContent = `
        @keyframes rainbow {
            0% { filter: hue-rotate(0deg); }
            100% { filter: hue-rotate(360deg); }
        }
    `;
    document.head.appendChild(easterStyle);
    
    setTimeout(() => {
        document.body.style.animation = '';
    }, 5000);
    
    console.log('%c🎉 ¡Easter Egg Activado!', 'color: #10B981; font-size: 20px; font-weight: bold;');
}

// ============================================
// ANALYTICS (Add your tracking code here)
// ============================================

// Google Analytics, Facebook Pixel, etc.
// Example:
// window.dataLayer = window.dataLayer || [];
// function gtag(){dataLayer.push(arguments);}
// gtag('js', new Date());
// gtag('config', 'YOUR-GA-ID');
