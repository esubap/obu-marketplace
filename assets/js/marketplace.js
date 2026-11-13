/**
 * OBU Student Marketplace - Marketplace Interactions
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Image Upload Previews for Add/Edit Product
    const imageInput = document.getElementById('productImageInput');
    const previewContainer = document.getElementById('imagePreviewContainer');

    if (imageInput && previewContainer) {
        imageInput.addEventListener('change', function () {
            previewContainer.innerHTML = '';
            const files = Array.from(this.files);

            if (files.length > 4) {
                alert('You can select a maximum of 4 images.');
                this.value = '';
                return;
            }

            files.forEach((file, index) => {
                if (!file.type.match('image.*')) return;

                const reader = new FileReader();
                reader.onload = function (e) {
                    const col = document.createElement('div');
                    col.className = 'col-3 position-relative';
                    col.innerHTML = `
                        <div class="card p-1 shadow-sm h-100">
                            <img src="${e.target.result}" class="rounded w-100" style="height: 100px; object-fit: cover;" alt="Preview">
                            ${index === 0 ? '<span class="badge bg-primary position-absolute top-0 start-0 m-2">Cover</span>' : ''}
                        </div>
                    `;
                    previewContainer.appendChild(col);
                };
                reader.readAsDataURL(file);
            });
        });
    }

    // 2. Favorite Toggle Button
    document.querySelectorAll('.favorite-btn-action').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const productId = this.getAttribute('data-product-id');
            const icon = this.querySelector('i');

            if (!productId) return;

            fetch((window.OBU_BASE_URL || '') + '/actions/favorite-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'product_id=' + encodeURIComponent(productId) + '&csrf_token=' + encodeURIComponent(window.OBU_CSRF_TOKEN || '')
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'unauthenticated') {
                    window.location.href = (window.OBU_BASE_URL || '') + '/login.php';
                    return;
                }
                if (data.success) {
                    if (data.is_favorited) {
                        icon.classList.remove('bi-heart');
                        icon.classList.add('bi-heart-fill', 'text-danger');
                    } else {
                        icon.classList.remove('bi-heart-fill', 'text-danger');
                        icon.classList.add('bi-heart');
                    }
                }
            })
            .catch(err => console.error('Favorite error:', err));
        });
    });

    // 3. Chat Auto-Scroll to Bottom
    const chatStream = document.getElementById('chatMessagesStream');
    if (chatStream) {
        chatStream.scrollTop = chatStream.scrollHeight;
    }
});
