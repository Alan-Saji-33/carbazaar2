// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            targetElement.scrollIntoView({
                behavior: 'smooth'
            });
        }
    });
});

// Image thumbnail click handler
document.querySelectorAll('.thumbnail').forEach(thumbnail => {
    thumbnail.addEventListener('click', function() {
        const mainImage = this.closest('.car-images').querySelector('.main-image img');
        const thumbnailSrc = this.querySelector('img').src;
        
        // Swap images
        const tempSrc = mainImage.src;
        mainImage.src = thumbnailSrc;
        this.querySelector('img').src = tempSrc;
    });
});

// Form validation
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const requiredFields = this.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.style.borderColor = 'var(--danger)';
                isValid = false;
            } else {
                field.style.borderColor = '';
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
});

// Reset field styles on input
document.querySelectorAll('input, textarea, select').forEach(field => {
    field.addEventListener('input', function() {
        this.style.borderColor = '';
    });
});

// Initialize car image thumbnails
function initCarThumbnails() {
    document.querySelectorAll('.thumbnail-images').forEach(container => {
        const thumbnails = container.querySelectorAll('.thumbnail');
        if (thumbnails.length > 0) {
            thumbnails[0].click();
        }
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initCarThumbnails();
    
    // Show active tab based on URL hash
    if (window.location.hash) {
        const tabId = window.location.hash.substring(1);
        const tab = document.getElementById(tabId);
        if (tab) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            
            tab.classList.add('active');
            const correspondingBtn = document.querySelector(`.tab-btn[onclick*="${tabId}"]`);
            if (correspondingBtn) {
                correspondingBtn.classList.add('active');
            }
        }
    }
});

// Admin tab switching
function openAdminTab(tabId) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show the selected tab content
    document.getElementById(tabId).classList.add('active');
    
    // Add active class to the clicked tab button
    event.currentTarget.classList.add('active');
}
