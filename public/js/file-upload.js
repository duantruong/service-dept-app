// File upload handler - updates file name display
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('file');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const fileName = this.files[0]?.name || 'Choose File';
            const fileNameSpan = this.closest('form').querySelector('.file-name');
            if (fileNameSpan) {
                fileNameSpan.textContent = fileName;
            }
        });
    }
});

