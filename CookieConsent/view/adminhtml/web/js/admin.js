function openAllProducts() {
    window.open('https://plugins.miniorange.com/magento', '_blank');
}

document.addEventListener('DOMContentLoaded', function () {
    var submitBtn = document.getElementById('mo-submit-query');
    if (submitBtn) {
        submitBtn.addEventListener('click', function () {
            window.alert('Thank you! Your query has been submitted. Our team will reach out shortly.');
        });
    }
});
