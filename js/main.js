document.addEventListener('DOMContentLoaded', function() {

    // Mobile Menu Toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const nav = document.querySelector('nav');
    if (menuToggle && nav) {
        menuToggle.addEventListener('click', function() {
            nav.classList.toggle('open');
        });
        document.addEventListener('click', function(e) {
            if (!nav.contains(e.target) && !menuToggle.contains(e.target)) {
                nav.classList.remove('open');
            }
        });
    }

    // Transparent Header Scroll Effect
    const header = document.getElementById('siteHeader');
    if (header && header.classList.contains('header-transparent')) {
        function handleScroll() {
            if (window.scrollY > 80) {
                header.classList.add('header-scrolled');
            } else {
                header.classList.remove('header-scrolled');
            }
        }
        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll();
    }

    // File Upload Area
    const uploadArea = document.querySelector('.upload-area');
    const fileInput = document.querySelector('#pdf_file');
    const fileName = document.querySelector('.file-name');

    if (uploadArea && fileInput) {
        uploadArea.addEventListener('click', function() {
            fileInput.click();
        });

        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#4da6ff';
            this.style.background = 'rgba(77,166,255,0.1)';
        });

        uploadArea.addEventListener('dragleave', function() {
            this.style.borderColor = '';
            this.style.background = '';
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '';
            this.style.background = '';
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                if (fileName) fileName.textContent = e.dataTransfer.files[0].name;
            }
        });

        fileInput.addEventListener('change', function() {
            if (this.files.length && fileName) {
                fileName.textContent = this.files[0].name;
            }
        });
    }

    // Delete Confirmation
    document.querySelectorAll('.btn-delete').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this note?')) {
                e.preventDefault();
            }
        });
    });

    // Auto-hide alerts
    document.querySelectorAll('.alert').forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(function() { alert.remove(); }, 500);
        }, 4000);
    });

    // Scroll Animations
    var animElements = document.querySelectorAll('.fade-in, .slide-up, .slide-left, .slide-right');
    if (animElements.length > 0) {
        var animObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    animObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });

        animElements.forEach(function(el) {
            animObserver.observe(el);
        });
    }

    // WhatsApp Button show/hide
    var waBtn = document.querySelector('.whatsapp-float');
    if (waBtn) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                waBtn.classList.add('wa-visible');
            } else {
                waBtn.classList.remove('wa-visible');
            }
        }, { passive: true });
    }

    // Gallery Lightbox
    var galleryItems = document.querySelectorAll('.gallery-item');
    var lightbox = document.getElementById('galleryLightbox');
    var lightboxImg = document.querySelector('.lightbox-img');
    var lightboxClose = document.querySelector('.lightbox-close');

    galleryItems.forEach(function(item) {
        item.addEventListener('click', function() {
            var img = this.querySelector('img');
            if (img && lightbox && lightboxImg) {
                lightboxImg.src = img.src;
                lightboxImg.alt = img.alt;
                lightbox.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        });
    });

    if (lightboxClose) {
        lightboxClose.addEventListener('click', function() {
            lightbox.classList.remove('active');
            document.body.style.overflow = '';
        });
    }

    if (lightbox) {
        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox) {
                lightbox.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }

});
