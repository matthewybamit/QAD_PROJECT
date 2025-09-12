  document.getElementById('school-logo-input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewContainer = document.getElementById('logo-preview');
                    const currentLogo = document.getElementById('current-logo');
                    const placeholder = document.getElementById('no-logo-placeholder');
                    
                    // Remove existing content
                    if (currentLogo) currentLogo.remove();
                    if (placeholder) placeholder.remove();
                    
                    // Add new preview image
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Logo Preview';
                    img.className = 'w-full h-full object-contain rounded-lg';
                    img.id = 'logo-preview-img';
                    
                    previewContainer.appendChild(img);
                };
                reader.readAsDataURL(file);
            }
        });