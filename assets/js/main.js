/**
 * Main JavaScript File
 */

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchBtn = document.querySelector('.btn-search');
    if (searchBtn) {
        searchBtn.addEventListener('click', function(e) {
            const businessInput = document.querySelector('input[name="business"]');
            const categorySelect = document.querySelector('select[name="category"]');
            const locationSelect = document.querySelector('select[name="location"]');

            let url = '/pages/search.php?';
            if (businessInput && businessInput.value) {
                url += 'business=' + encodeURIComponent(businessInput.value) + '&';
            }
            if (categorySelect && categorySelect.value) {
                url += 'category=' + encodeURIComponent(categorySelect.value) + '&';
            }
            if (locationSelect && locationSelect.value) {
                url += 'location=' + encodeURIComponent(locationSelect.value);
            }
            window.location.href = url;
        });
    }
});

// Format phone number as clickable tel link
function makePhoneClickable(phone) {
    const cleanPhone = phone.replace(/\D/g, '');
    return '<a href="tel:' + cleanPhone + '">' + phone + '</a>';
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Copied to clipboard!');
    });
}

// Open WhatsApp
function openWhatsApp(phone) {
    const cleanPhone = phone.replace(/\D/g, '');
    window.open('https://wa.me/' + cleanPhone);
}
