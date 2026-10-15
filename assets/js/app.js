(function () {
    var toastEl = document.getElementById('cart-toast');
    var toastBody = document.getElementById('cart-toast-body');
    var toast = toastEl && window.bootstrap ? new bootstrap.Toast(toastEl, { delay: 2200 }) : null;
    var nav = document.querySelector('.navbar-aurloum');

    function showToast(message) {
        if (!toast || !toastBody) {
            return;
        }
        toastBody.textContent = message;
        toast.show();
    }

    function setCartCount(count) {
        ['cart-count', 'cart-count-mobile'].forEach(function (id) {
            var badge = document.getElementById(id);
            if (badge) {
                badge.textContent = count;
            }
        });
    }

    document.querySelectorAll('.js-add-to-cart').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var data = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: data
            })
                .then(function (response) {
                    return response.json().then(function (json) {
                        return { ok: response.ok, json: json };
                    });
                })
                .then(function (result) {
                    if (!result.ok) {
                        showToast(result.json.message || 'Impossible d’ajouter au panier');
                        return;
                    }
                    setCartCount(result.json.count);
                    showToast(result.json.message);
                    var btn = form.querySelector('.btn-add, button[type="submit"]');
                    if (btn) {
                        btn.classList.remove('is-added');
                        void btn.offsetWidth;
                        btn.classList.add('is-added');
                    }
                })
                .catch(function () {
                    form.submit();
                });
        });
    });

    var preview = document.getElementById('image-preview');
    var previewEmpty = document.getElementById('image-preview-empty');
    var fileInput = document.getElementById('image_file');
    var urlInput = document.getElementById('image');

    function showPreview(src) {
        if (!preview) {
            return;
        }
        if (src) {
            preview.src = src;
            preview.hidden = false;
            if (previewEmpty) {
                previewEmpty.hidden = true;
            }
        } else {
            preview.removeAttribute('src');
            preview.hidden = true;
            if (previewEmpty) {
                previewEmpty.hidden = false;
            }
        }
    }

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            var file = fileInput.files && fileInput.files[0];
            if (file) {
                showPreview(URL.createObjectURL(file));
            }
        });
    }
    if (urlInput) {
        urlInput.addEventListener('input', function () {
            if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                showPreview(urlInput.value);
            }
        });
    }

    function onScroll() {
        if (!nav) {
            return;
        }
        nav.classList.toggle('is-scrolled', window.scrollY > 12);
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    var cards = document.querySelectorAll('.js-product-card');
    if (!cards.length) {
        return;
    }
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
        cards.forEach(function (card) {
            card.classList.add('is-visible');
        });
        return;
    }
    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) {
                return;
            }
            var card = entry.target;
            var delay = Number(card.getAttribute('data-delay') || 0);
            window.setTimeout(function () {
                card.classList.add('is-visible');
            }, delay);
            observer.unobserve(card);
        });
    }, { threshold: 0.18, rootMargin: '0px 0px -40px 0px' });

    cards.forEach(function (card, index) {
        card.setAttribute('data-delay', String((index % 8) * 70));
        observer.observe(card);
    });
})();
