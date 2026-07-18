/* ==========================================================================
   INTERACTIVIDAD Y EFECTOS - ROJAS CONSTRUCCIONES
   Lógica Vanilla JS moderna y animaciones fluidas
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {

    // 1. PRELOADER
    const preloader = document.getElementById('preloader');
    if (preloader) {
        window.addEventListener('load', () => {
            preloader.style.opacity = '0';
            setTimeout(() => {
                preloader.style.display = 'none';
            }, 500);
        });
        
        // Fallback en caso de que tarde demasiado la carga
        setTimeout(() => {
            preloader.style.opacity = '0';
            setTimeout(() => {
                preloader.style.display = 'none';
            }, 500);
        }, 3000);
    }

    // 2. HEADER AL SCROLL (Glassmorphism trigger)
    const header = document.querySelector('.header');
    const handleScroll = () => {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    };
    window.addEventListener('scroll', handleScroll);
    handleScroll(); // Trigger inicial

    // 3. MENÚ DE NAVEGACIÓN MÓVIL
    const mobileNavToggle = document.getElementById('mobile-nav-toggle');
    const navMenu = document.getElementById('nav-menu');
    const navLinks = document.querySelectorAll('.nav-link');

    if (mobileNavToggle && navMenu) {
        mobileNavToggle.addEventListener('click', () => {
            mobileNavToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
            
            // Prevenir scroll en el body cuando el menú está abierto
            if (navMenu.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });

        // Cerrar menú al hacer clic en un enlace de navegación
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                mobileNavToggle.classList.remove('active');
                navMenu.classList.remove('active');
                document.body.style.overflow = '';
            });
        });
    }

    // Navegación Activa basada en Scroll (Spy Scroll)
    const sections = document.querySelectorAll('section[id]');
    const activeScrollSpy = () => {
        const scrollY = window.pageYOffset;

        sections.forEach(current => {
            const sectionHeight = current.offsetHeight;
            const sectionTop = current.offsetTop - 120;
            const sectionId = current.getAttribute('id');
            const correspondingLink = document.querySelector(`.nav-menu a[href*=${sectionId}]`);

            if (correspondingLink) {
                if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
                    navLinks.forEach(link => link.classList.remove('active'));
                    correspondingLink.classList.add('active');
                }
            }
        });
    };
    window.addEventListener('scroll', activeScrollSpy);

    // 4. SERVICIOS - TABS INTERACTIVOS
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetTab = button.getAttribute('data-tab');

            // Desactivar botones y tabs activos previos
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            // Activar actual
            button.classList.add('active');
            const targetContent = document.getElementById(`tab-${targetTab}`);
            if (targetContent) {
                targetContent.classList.add('active');
            }
        });
    });

    // 5. PORTAFOLIO - FILTRADO DINÁMICO
    const filterButtons = document.querySelectorAll('.filter-btn');
    const portfolioItems = document.querySelectorAll('.portfolio-item');

    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            const filterValue = button.getAttribute('data-filter');

            // Cambiar clase activa en botones
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            // Filtrar elementos de la grilla
            portfolioItems.forEach(item => {
                const itemCategory = item.getAttribute('data-category');
                
                if (filterValue === 'all' || itemCategory === filterValue) {
                    item.style.display = 'block';
                    // Pequeño timeout para animar entrada
                    setTimeout(() => {
                        item.style.opacity = '1';
                        item.style.transform = 'scale(1)';
                    }, 50);
                } else {
                    item.style.opacity = '0';
                    item.style.transform = 'scale(0.95)';
                    // Esperar a la animación antes de ocultar del layout
                    setTimeout(() => {
                        item.style.display = 'none';
                    }, 350);
                }
            });
        });
    });

    // 6. PORTAFOLIO - MODAL / LIGHTBOX
    const projectModal = document.getElementById('project-modal');
    const modalClose = document.getElementById('modal-close');
    const modalImg = document.getElementById('modal-img');
    const modalTitle = document.getElementById('modal-title');
    const modalDesc = document.getElementById('modal-desc');
    const modalBadge = document.getElementById('modal-badge');
    const btnViewProjects = document.querySelectorAll('.btn-view-project');

    const openModal = (imgSrc, title, desc, category) => {
        if (!projectModal) return;
        modalImg.src = imgSrc;
        modalTitle.textContent = title;
        modalDesc.textContent = desc;
        modalBadge.textContent = category;
        
        projectModal.classList.add('active');
        projectModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };

    const closeModal = () => {
        if (!projectModal) return;
        projectModal.classList.remove('active');
        projectModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        // Limpiar src para evitar saltos visuales al reabrir
        setTimeout(() => {
            modalImg.src = '';
        }, 300);
    };

    btnViewProjects.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const card = e.target.closest('.portfolio-card');
            const category = card.querySelector('.portfolio-category').textContent;
            const title = btn.getAttribute('data-title');
            const desc = btn.getAttribute('data-desc');
            const imgSrc = btn.getAttribute('data-img');
            openModal(imgSrc, title, desc, category);
        });
    });

    if (modalClose) modalClose.addEventListener('click', closeModal);
    
    // Cerrar al hacer clic en el backdrop/overlay
    if (projectModal) {
        projectModal.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-backdrop') || e.target.classList.contains('modal-wrapper')) {
                closeModal();
            }
        });
    }

    // Cerrar con la tecla Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && projectModal && projectModal.classList.contains('active')) {
            closeModal();
        }
    });

    // 7. ANIMACIONES DE REVELADO CON INTERSECTION OBSERVER
    const revealElements = document.querySelectorAll('.reveal-fade, .reveal-slide-up, .reveal-left, .reveal-right');
    
    if ('IntersectionObserver' in window) {
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    console.log('REVEALED:', entry.target.className);
                    // Dejar de observar una vez revelado
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.15,
            rootMargin: '0px 0px -50px 0px'
        });

        revealElements.forEach(el => revealObserver.observe(el));
    } else {
        // Fallback en navegadores viejos sin soporte
        revealElements.forEach(el => el.classList.add('revealed'));
    }

    // 8. FORMULARIO DE CONTACTO AJAX CON FORMSUBMIT
    const contactForm = document.getElementById('contact-form');
    const formMessage = document.getElementById('form-message');
    const btnSubmit = document.getElementById('btn-submit');

    if (contactForm) {
        contactForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Cambiar estado del botón a enviando
            const originalBtnText = btnSubmit.innerHTML;
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span>Enviando...</span> <i class="fa-solid fa-spinner fa-spin"></i>';
            
            // Ocultar mensaje previo
            formMessage.style.display = 'none';
            formMessage.className = 'form-message';

            const formData = new FormData(contactForm);
            
            // Enviar vía Fetch/AJAX a FormSubmit
            fetch(contactForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (response.ok) {
                    return response.json();
                }
                throw new Error('Error al procesar la respuesta del servidor.');
            })
            .then(data => {
                // Éxito
                formMessage.textContent = '¡Consulta enviada con éxito! Te responderemos en menos de 24 horas. Recordá verificar tu mail si FormSubmit te solicita la activación única.';
                formMessage.classList.add('success');
                contactForm.reset();
            })
            .catch(error => {
                // Error
                formMessage.textContent = 'Hubo un problema al enviar tu consulta. Por favor, intentá de nuevo o contactanos directamente por WhatsApp.';
                formMessage.classList.add('error');
                console.error('Contact Form Error:', error);
            })
            .finally(() => {
                // Restaurar botón
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = originalBtnText;
            });
        });
    }

    // 9. LAZY LOADING DE IMÁGENES
    const lazyImages = document.querySelectorAll('img.lazy');
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const image = entry.target;
                    image.src = image.src; // Forzar carga en fallback, o si tuviera data-src se asignaría
                    image.classList.remove('lazy');
                    imageObserver.unobserve(image);
                }
            });
        });
        lazyImages.forEach(img => imageObserver.observe(img));
    }
});
