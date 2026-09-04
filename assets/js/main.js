// VAREEN Academy - Enhanced Modern JavaScript with Advanced Animations

// DOM Ready with enhanced initialization
document.addEventListener('DOMContentLoaded', () => {
    initializeWebsite();
});

// Initialize all website functionality with enhanced features
const initializeWebsite = () => {
    console.log('VAREEN Academy website initialized with enhanced animations');

    // Initialize core features
    initializeNavigation();
    initializeAdvancedAnimations();
    initializeScrollEffects();
    initializeFormValidation();
    initializeBackToTop();
    initializeMobileMenu();
    initializeLazyLoading();
    initializeGallery();
    initializeCounters();
    initializePWA();
    initializeParticleEffects();
    initializeTypingEffect();
    initializeMorphingElements();
    initializeFloatingElements();
    initializeTestimonials();
};

// Initialize testimonials slider with Slick
const initializeTestimonials = () => {
    if (typeof $ !== 'undefined' && $.fn && $.fn.slick && $('.testimonials-slider').length > 0) {
        $('.testimonials-slider').slick({
            slidesToShow: 3,
            slidesToScroll: 1,
            autoplay: true,
            autoplaySpeed: 5000,
            dots: true,
            arrows: true,
            infinite: true,
            pauseOnHover: true,
            adaptiveHeight: false,
            responsive: [
                {
                    breakpoint: 992,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 576,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1
                    }
                }
            ]
        });
    }
};

// Enhanced Navigation with smooth animations
const initializeNavigation = () => {
    const navbar = document.querySelector('.navbar');
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');

    // Navbar scroll effect. NOTE: no inline transform is set here — a transform
    // on .navbar would make it the containing block for the fixed off-canvas
    // drawer and backdrop, breaking their full-viewport positioning. The
    // previous code set translateY(0) in both branches (a no-op) anyway.
    const handleScroll = debounce(() => {
        const scrolled = window.scrollY;
        if (scrolled > 50) {
            navbar.classList.add('navbar-scrolled');
        } else {
            navbar.classList.remove('navbar-scrolled');
        }
    }, 10);

    window.addEventListener('scroll', handleScroll);

    // Active link highlighting with animation
    const currentPage = window.location.pathname.split('/').pop();
    navLinks.forEach(link => {
        const linkPage = link.getAttribute('href');
        if (linkPage === currentPage || (currentPage === '' && linkPage === 'index.html')) {
            link.classList.add('active');
            animateActiveLink(link);
        }
    });

    // Enhanced smooth scrolling with easing
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            const href = link.getAttribute('href');
            if (href.startsWith('#')) {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    smoothScrollTo(target, 1000, 'easeInOutCubic');
                    // Close mobile menu after clicking a link
                    if (navbarCollapse && navbarCollapse.classList.contains('show')) {
                        navbarToggler.click();
                    }
                }
            }
        });
    });

    // Touch event handling for mobile navigation
    if ('ontouchstart' in window) {
        navLinks.forEach(link => {
            link.addEventListener('touchstart', (e) => {
                e.target.classList.add('touch-active');
            });
            link.addEventListener('touchend', (e) => {
                setTimeout(() => e.target.classList.remove('touch-active'), 150);
            });
        });
    }
};

// Advanced Animations System
const initializeAdvancedAnimations = () => {
    // Enhanced Intersection Observer with multiple animation types
    const observerOptions = {
        threshold: [0.1, 0.3, 0.5],
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                const element = entry.target;
                const delay = index * 100; // Staggered animation

                setTimeout(() => {
                    animateElementIn(element, entry.intersectionRatio);
                }, delay);

                // Unobserve after animation
                if (entry.intersectionRatio > 0.3) {
                    observer.unobserve(element);
                }
            }
        });
    }, observerOptions);

    // Observe multiple element types
    const animateElements = document.querySelectorAll(
        '.service-card, .testimonial-card, .gallery-item, .feature-item, .news-card, .hero-buttons .btn'
    );
    animateElements.forEach(element => observer.observe(element));

    // Add hover animations
    initializeHoverAnimations();
};

// Animate element entrance with different effects based on type
const animateElementIn = (element, ratio) => {
    const rect = element.getBoundingClientRect();
    const isVisible = rect.top < window.innerHeight && rect.bottom > 0;

    if (isVisible) {
        if (element.classList.contains('service-card')) {
            element.style.animation = 'slideInUp 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards';
        } else if (element.classList.contains('testimonial-card')) {
            element.style.animation = 'fadeInScale 0.6s ease-out forwards';
        } else if (element.classList.contains('gallery-item')) {
            element.style.animation = 'bounceIn 0.8s ease-out forwards';
        } else if (element.classList.contains('feature-item')) {
            element.style.animation = 'slideInLeft 0.7s ease-out forwards';
        } else {
            element.classList.add('animate-fade-in');
        }
    }
};

// Hover animations for interactive elements
const initializeHoverAnimations = () => {
    const hoverElements = document.querySelectorAll('.service-card, .gallery-item, .btn, .feature-item');

    hoverElements.forEach(element => {
        element.addEventListener('mouseenter', (e) => {
            const rect = element.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;
            const mouseX = e.clientX - centerX;
            const mouseY = e.clientY - centerY;

            element.style.transform = `perspective(1000px) rotateY(${mouseX * 0.01}deg) rotateX(${mouseY * -0.01}deg) scale(1.05)`;
        });

        element.addEventListener('mouseleave', () => {
            element.style.transform = 'perspective(1000px) rotateY(0deg) rotateX(0deg) scale(1)';
        });
    });
};

// Enhanced Scroll Effects with parallax and reveal animations
const initializeScrollEffects = () => {
    let ticking = false;

    const handleScroll = () => {
        if (!ticking) {
            requestAnimationFrame(() => {
                const scrolled = window.pageYOffset;
                const rate = scrolled * -0.5;

                // Parallax effect for hero background
                const hero = document.querySelector('.hero-section');
                if (hero) {
                    hero.style.transform = `translateY(${rate * 0.5}px)`;
                }

                // Reveal animations for sections
                const sections = document.querySelectorAll('section');
                sections.forEach(section => {
                    const rect = section.getBoundingClientRect();
                    if (rect.top < window.innerHeight * 0.8) {
                        section.classList.add('section-revealed');
                    }
                });

                ticking = false;
            });
            ticking = true;
        }
    };

    window.addEventListener('scroll', handleScroll, { passive: true });
};

// Particle Effects for background animation
const initializeParticleEffects = () => {
    const canvas = document.createElement('canvas');
    canvas.id = 'particle-canvas';
    canvas.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: -1;
        opacity: 0.3;
    `;

    document.body.appendChild(canvas);

    const ctx = canvas.getContext('2d');
    let particles = [];
    let animationId;

    const resizeCanvas = () => {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    };

    const createParticle = () => {
        return {
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height,
            vx: (Math.random() - 0.5) * 0.5,
            vy: (Math.random() - 0.5) * 0.5,
            size: Math.random() * 2 + 1,
            opacity: Math.random() * 0.5 + 0.2
        };
    };

    const initParticles = () => {
        particles = [];
        for (let i = 0; i < 50; i++) {
            particles.push(createParticle());
        }
    };

    const animateParticles = () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        particles.forEach(particle => {
            particle.x += particle.vx;
            particle.y += particle.vy;

            if (particle.x < 0 || particle.x > canvas.width) particle.vx *= -1;
            if (particle.y < 0 || particle.y > canvas.height) particle.vy *= -1;

            ctx.beginPath();
            ctx.arc(particle.x, particle.y, particle.size, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(59, 130, 246, ${particle.opacity})`;
            ctx.fill();
        });

        animationId = requestAnimationFrame(animateParticles);
    };

    window.addEventListener('resize', () => {
        resizeCanvas();
        initParticles();
    });

    resizeCanvas();
    initParticles();
    animateParticles();
};

// Typing Effect for hero text
const initializeTypingEffect = () => {
    const heroTitle = document.querySelector('.hero-title');
    if (!heroTitle) return;

    const text = heroTitle.textContent;
    heroTitle.textContent = '';
    heroTitle.style.borderRight = '2px solid var(--accent-color)';

    let i = 0;
    const typeWriter = () => {
        if (i < text.length) {
            heroTitle.textContent += text.charAt(i);
            i++;
            setTimeout(typeWriter, 100);
        } else {
            setTimeout(() => {
                heroTitle.style.borderRight = 'none';
                // Add blinking cursor effect
                setInterval(() => {
                    heroTitle.style.borderRight = heroTitle.style.borderRight ? 'none' : '2px solid var(--accent-color)';
                }, 500);
            }, 1000);
        }
    };

    // Start typing after a delay
    setTimeout(typeWriter, 1000);
};

// Morphing Elements Animation
const initializeMorphingElements = () => {
    const morphElements = document.querySelectorAll('.service-icon, .feature-item i');

    morphElements.forEach(element => {
        element.addEventListener('mouseenter', () => {
            element.style.animation = 'morph 0.6s ease-in-out';
        });

        element.addEventListener('animationend', () => {
            element.style.animation = '';
        });
    });
};

// Floating Elements Animation
const initializeFloatingElements = () => {
    const floatingElements = document.querySelectorAll('.gallery-item img, .service-icon');

    floatingElements.forEach((element, index) => {
        element.style.animation = `float ${3 + index * 0.5}s ease-in-out infinite`;
        element.style.animationDelay = `${index * 0.2}s`;
    });
};

// Enhanced Form Validation with animations
const initializeFormValidation = () => {
    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, textarea, select');

        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.classList.add('input-focused');
            });

            input.addEventListener('blur', () => {
                input.parentElement.classList.remove('input-focused');
            });

            input.addEventListener('input', () => {
                if (input.checkValidity()) {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                } else {
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                }
            });
        });

        form.addEventListener('submit', (e) => {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();

                // Animate invalid inputs
                const invalidInputs = form.querySelectorAll(':invalid');
                invalidInputs.forEach(input => {
                    input.style.animation = 'shake 0.5s ease-in-out';
                    setTimeout(() => input.style.animation = '', 500);
                });
            } else {
                handleFormSubmission(form, e);
            }
            form.classList.add('was-validated');
        });
    });
};

// Enhanced Form Submission with loading animations
const handleFormSubmission = async (form, event) => {
    event.preventDefault();

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;

    // Enhanced loading state with spinner
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
        <div class="spinner-container">
            <div class="spinner"></div>
            <span>Submitting...</span>
        </div>
    `;

    // Add loading animation to form
    form.style.animation = 'pulse 1s infinite';

    try {
        const apiEndpoint = form.dataset.api;
        if (apiEndpoint) {
            const formData = new FormData(form);
            const resp = await fetch(apiEndpoint, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const json = await resp.json().catch(() => ({ success: false, message: 'Invalid JSON response' }));

            if (resp.ok && json && json.success) {
                showSuccessMessage(json.message || 'Submitted successfully');
                form.reset();
                form.classList.remove('was-validated');
                form.style.animation = 'bounceIn 0.6s ease-out';
            } else {
                const msg = (json && json.message) || (json && json.errors && json.errors.join(', ')) || 'Submission failed';
                showErrorMessage(msg);
                form.style.animation = 'shake 0.5s ease-in-out';
            }
        } else {
            await new Promise(resolve => setTimeout(resolve, 1500));
            showSuccessMessage('Thank you for your message! We will contact you soon.');
            form.reset();
            form.classList.remove('was-validated');
            form.style.animation = 'bounceIn 0.6s ease-out';
        }

    } catch (error) {
        console.error('Form submission error:', error);
        showErrorMessage('An error occurred. Please try again.');
        form.style.animation = 'shake 0.5s ease-in-out';
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
        form.style.animation = '';
    }
};

// Enhanced Back to Top Button with smooth animation
const initializeBackToTop = () => {
    const backToTopBtn = document.createElement('button');
    backToTopBtn.innerHTML = '<i class="bi bi-arrow-up" aria-hidden="true"></i>';
    backToTopBtn.className = 'btn btn-primary back-to-top animate-bounce-in';
    backToTopBtn.setAttribute('aria-label', 'Back to top');
    backToTopBtn.style.cssText = `
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        z-index: 1000;
        display: none;
        border-radius: 50%;
        width: 3rem;
        height: 3rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        transform: scale(0);
    `;

    document.body.appendChild(backToTopBtn);

    const toggleBackToTop = debounce(() => {
        if (window.pageYOffset > 300) {
            backToTopBtn.style.display = 'flex';
            backToTopBtn.style.alignItems = 'center';
            backToTopBtn.style.justifyContent = 'center';
            backToTopBtn.style.transform = 'scale(1)';
        } else {
            backToTopBtn.style.transform = 'scale(0)';
            setTimeout(() => backToTopBtn.style.display = 'none', 300);
        }
    }, 10);

    window.addEventListener('scroll', toggleBackToTop);

    backToTopBtn.addEventListener('click', () => {
        smoothScrollTo(document.body, 800, 'easeInOutCubic');
    });

    // Add hover effect
    backToTopBtn.addEventListener('mouseenter', () => {
        backToTopBtn.style.transform = 'scale(1.1)';
    });

    backToTopBtn.addEventListener('mouseleave', () => {
        backToTopBtn.style.transform = 'scale(1)';
    });
};

// Premium off-canvas mobile menu (right-side drawer).
// This function fully owns the open/close state (the data-bs-toggle attributes
// were removed from the markup so Bootstrap Collapse no longer races us).
// It handles: slide-in/out via the .show class (CSS transform transition),
// backdrop overlay, body scroll lock, Escape key, close button/backdrop taps,
// link-tap close and a focus trap. The old implementation manually fought
// Bootstrap's Collapse animation with keyframes that did not exist.
const initializeMobileMenu = () => {
    const collapseEl = document.getElementById('navbarNav');
    const toggler = document.querySelector('.navbar-toggler');
    if (!collapseEl || !toggler) return;

    const navbar = collapseEl.closest('.navbar');
    const backdrop = navbar ? navbar.querySelector('.nav-backdrop') : null;
    const closeBtn = collapseEl.querySelector('.navbar-close');
    const isMobile = () => window.matchMedia('(max-width: 991.98px)').matches;

    const openMenu = () => {
        collapseEl.classList.add('show');
        toggler.setAttribute('aria-expanded', 'true');
        toggler.setAttribute('aria-label', 'Close navigation menu');
        if (backdrop) backdrop.classList.add('show');
        document.body.classList.add('nav-open');
        window.setTimeout(() => { if (closeBtn) closeBtn.focus(); }, 300);
    };

    const closeMenu = () => {
        collapseEl.classList.remove('show');
        toggler.setAttribute('aria-expanded', 'false');
        toggler.setAttribute('aria-label', 'Open navigation menu');
        if (backdrop) backdrop.classList.remove('show');
        document.body.classList.remove('nav-open');
    };

    toggler.addEventListener('click', () => {
        if (collapseEl.classList.contains('show')) {
            closeMenu();
            toggler.focus();
        } else {
            openMenu();
        }
    });

    if (closeBtn) closeBtn.addEventListener('click', () => { closeMenu(); toggler.focus(); });
    if (backdrop) backdrop.addEventListener('click', closeMenu);

    // Escape-key support.
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isMobile() && collapseEl.classList.contains('show')) {
            closeMenu();
            toggler.focus();
        }
    });

    // Selecting a link (or the CTA) closes the drawer, then lets the link navigate.
    collapseEl.querySelectorAll('.nav-link, .drawer-cta').forEach((link) => {
        link.addEventListener('click', () => {
            if (isMobile() && collapseEl.classList.contains('show')) closeMenu();
        });
    });

    // Simple focus trap while the drawer is open on mobile.
    collapseEl.addEventListener('keydown', (e) => {
        if (e.key !== 'Tab' || !isMobile() || !collapseEl.classList.contains('show')) return;
        const focusables = collapseEl.querySelectorAll('a[href], button:not([disabled])');
        if (!focusables.length) return;
        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    });

    // Leaving the mobile breakpoint: force-clean any open state.
    window.matchMedia('(min-width: 992px)').addEventListener('change', (e) => {
        if (e.matches) {
            collapseEl.classList.remove('show');
            if (backdrop) backdrop.classList.remove('show');
            document.body.classList.remove('nav-open');
        }
    });
};

// Enhanced Lazy Loading with blur effect
const initializeLazyLoading = () => {
    const images = document.querySelectorAll('img[data-src]');

    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.style.filter = 'blur(10px)';
                    img.src = img.dataset.src;

                    img.addEventListener('load', () => {
                        img.style.filter = 'none';
                        img.style.animation = 'fadeIn 0.5s ease-out';
                        img.classList.remove('lazy');
                    });

                    imageObserver.unobserve(img);
                }
            });
        });

        images.forEach(img => imageObserver.observe(img));
    } else {
        images.forEach(img => {
            img.src = img.dataset.src;
        });
    }
};

// Enhanced Gallery initialization with lightbox and animations
const initializeGallery = () => {
    console.log('Initializing gallery functionality');

    // Initialize GLightbox for gallery images
    if (typeof GLightbox !== 'undefined') {
        const lightbox = GLightbox({
            selector: '.gallery-item img, .glightbox',
            touchNavigation: true,
            loop: true,
            autoplayVideos: false,
            zoomable: true,
            draggable: true,
            openEffect: 'fade',
            closeEffect: 'fade',
            slideEffect: 'slide',
            moreText: 'See more',
            moreLength: 60,
            closeButton: true,
            preload: false
        });

        // Add error handling for gallery images
        const galleryImages = document.querySelectorAll('.gallery-item img');
        galleryImages.forEach(img => {
            img.addEventListener('error', () => {
                console.warn('Gallery image failed to load:', img.src);
                img.style.display = 'none';
            });

            img.addEventListener('load', () => {
                img.style.opacity = '1';
            });
        });
    } else {
        console.warn('GLightbox library not loaded');
    }

    // Add gallery interaction animations
    const galleryItems = document.querySelectorAll('.gallery-item');
    galleryItems.forEach((item, index) => {
        item.style.animationDelay = `${index * 0.1}s`;

        // Touch event handling for mobile gallery
        if ('ontouchstart' in window) {
            item.addEventListener('touchstart', () => {
                item.classList.add('touch-active');
            });

            item.addEventListener('touchend', () => {
                setTimeout(() => item.classList.remove('touch-active'), 150);
            });
        }
    });

    // Gallery filter functionality (if implemented)
    const galleryFilters = document.querySelectorAll('.gallery-filter');
    if (galleryFilters.length > 0) {
        galleryFilters.forEach(filter => {
            filter.addEventListener('click', (e) => {
                e.preventDefault();
                const category = filter.dataset.filter;

                // Remove active class from all filters
                galleryFilters.forEach(f => f.classList.remove('active'));
                filter.classList.add('active');

                // Filter gallery items
                galleryItems.forEach(item => {
                    if (category === 'all' || item.dataset.category === category) {
                        item.style.display = 'block';
                        item.style.animation = 'fadeInUp 0.5s ease-out';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    }
};

// Enhanced Counter Animation with easing
const initializeCounters = () => {
    const counters = document.querySelectorAll('.counter');

    const animateCounter = (counter) => {
        const target = +counter.getAttribute('data-target');
        const duration = 2500;
        const start = performance.now();
        const startValue = 0;

        const animate = (currentTime) => {
            const elapsed = currentTime - start;
            const progress = Math.min(elapsed / duration, 1);

            const easeOutCubic = 1 - Math.pow(1 - progress, 3);
            const current = Math.floor(startValue + (target - startValue) * easeOutCubic);

            counter.textContent = current.toLocaleString();

            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                counter.textContent = target.toLocaleString();
                counter.style.animation = 'bounce 0.5s ease-out';
            }
        };

        requestAnimationFrame(animate);
    };

    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                counterObserver.unobserve(entry.target);
            }
        });
    });

    counters.forEach(counter => counterObserver.observe(counter));
};

// Enhanced Toast Messages with animations
const showSuccessMessage = (message) => {
    const toast = createToast('success', message);
    animateToastIn(toast);
};

const showErrorMessage = (message) => {
    const toast = createToast('error', message);
    animateToastIn(toast);
};

const createToast = (type, message) => {
    const toast = document.createElement('div');
    toast.className = `toast ${type}-toast position-fixed top-50 start-50 translate-middle`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');

    const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
    const iconClass = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';

    toast.innerHTML = `
        <div class="toast-body ${bgClass} text-contrast rounded p-3 shadow-lg">
            <i class="bi ${iconClass} me-2" aria-hidden="true"></i>
            <span>${message}</span>
            <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;

    document.body.appendChild(toast);
    return toast;
};

const animateToastIn = (toast) => {
    toast.style.animation = 'slideInFromBottom 0.5s ease-out';
    setTimeout(() => {
        toast.style.animation = 'slideOutToBottom 0.5s ease-in forwards';
        setTimeout(() => toast.remove(), 500);
    }, 4000);
};

// Smooth scroll utility function
const smoothScrollTo = (element, duration, easing) => {
    const start = window.pageYOffset;
    const end = element.offsetTop;
    const distance = end - start;
    let startTime = null;

    const animation = (currentTime) => {
        if (startTime === null) startTime = currentTime;
        const timeElapsed = currentTime - startTime;
        const progress = Math.min(timeElapsed / duration, 1);

        const ease = easingFunctions[easing] || easingFunctions.linear;
        const scrollY = start + distance * ease(progress);

        window.scrollTo(0, scrollY);

        if (timeElapsed < duration) {
            requestAnimationFrame(animation);
        }
    };

    requestAnimationFrame(animation);
};

// Easing functions
const easingFunctions = {
    linear: t => t,
    easeInOutCubic: t => t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2
};

// Animate active navigation link
const animateActiveLink = (link) => {
    link.style.animation = 'pulse 0.6s ease-out';
};

// Utility Functions
const debounce = (func, wait) => {
    let timeout;
    return function(...args) {
        const context = this;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
    };
};

// Enhanced PWA features with animations
const initializePWA = () => {
    // Register service worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then((registration) => {
                    console.log('SW registered: ', registration);
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                showUpdatePrompt();
                            }
                        });
                    });
                })
                .catch((registrationError) => {
                    console.log('SW registration failed: ', registrationError);
                });
        });
    }

    // Handle install prompt with animation
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        showInstallPrompt();
    });

    window.addEventListener('appinstalled', (evt) => {
        console.log('PWA was installed successfully');
        hideInstallPrompt();
    });

    // Enhanced online/offline status with animations
    window.addEventListener('online', () => {
        showOnlineStatus();
    });

    window.addEventListener('offline', () => {
        showOfflineStatus();
    });
};

// Enhanced install prompt with animation
const showInstallPrompt = () => {
    const installBanner = document.createElement('div');
    installBanner.id = 'install-banner';
    installBanner.className = 'install-banner bg-primary text-contrast text-center py-2 px-3 position-fixed top-0 w-100 animate-slide-in-down';
    installBanner.style.cssText = `
        z-index: 9999;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        font-size: 0.9rem;
    `;
    installBanner.innerHTML = `
        <div class="d-flex justify-content-between align-items-center">
            <span class="visually-hidden">VAREEN Academy install prompt</span>
            <div>
                <button class="btn btn-light btn-sm me-2 animate-bounce-in" onclick="installPWA()">Install</button>
                <button class="btn btn-outline-light btn-sm" onclick="hideInstallPrompt()">Later</button>
            </div>
        </div>
    `;

    document.body.insertBefore(installBanner, document.body.firstChild);
};

// Enhanced status messages with animations
const showOnlineStatus = () => {
    const statusToast = createStatusToast('online', 'You\'re back online', 'success');
    animateToastIn(statusToast);
};

const showOfflineStatus = () => {
    const statusToast = createStatusToast('offline', 'You\'re offline. Some features may be limited.', 'warning');
    animateToastIn(statusToast);
};

const createStatusToast = (type, message, style) => {
    const toast = document.createElement('div');
    toast.className = `toast ${type}-toast position-fixed bottom-0 end-0 m-3 animate-slide-in-up`;

    const bgClass = style === 'success' ? 'bg-success' : style === 'warning' ? 'bg-warning text-dark' : 'bg-info';
    const iconClass = type === 'online' ? 'bi-wifi' : 'bi-wifi-off';

    toast.innerHTML = `
        <div class="toast-body ${bgClass} rounded p-2 shadow">
            <i class="bi ${iconClass} me-2"></i>
            <span>${message}</span>
        </div>
    `;

    document.body.appendChild(toast);
    return toast;
};

// Performance monitoring with enhanced logging
const initializePerformanceMonitoring = () => {
    if ('performance' in window && 'getEntriesByType' in performance) {
        window.addEventListener('load', () => {
            const perfData = performance.getEntriesByType('navigation')[0];
            const loadTime = perfData.loadEventEnd - perfData.fetchStart;
            console.log(`Page load time: ${loadTime}ms`);

            // Animate page load indicator
            const loadIndicator = document.querySelector('.load-indicator');
            if (loadIndicator) {
                loadIndicator.style.animation = 'fadeOut 0.5s ease-out forwards';
                setTimeout(() => loadIndicator.remove(), 500);
            }
        });
    }
};

initializePerformanceMonitoring();

// Export functions for potential module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        initializeWebsite,
        showSuccessMessage,
        showErrorMessage,
        smoothScrollTo
    };
}

