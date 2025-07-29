document.addEventListener('DOMContentLoaded', () => {
    function showFlashMessage(message, type = 'success') {
        const flashDiv = document.createElement('div');
        flashDiv.className = `alert alert-${type} alert-dismissible fade show`;
        flashDiv.role = 'alert';
        flashDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        // Adaugă flash-ul în containerul principal (modifică selectorul după nevoie)
        const container = document.querySelector('.content-wrapper > .row') || document.body;
        container.prepend(flashDiv);

        setTimeout(() => {
            bootstrap.Alert.getOrCreateInstance(flashDiv).close();
        }, 5000);
    }

    document.querySelectorAll('.ajax-ticket-reply-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const url = this.action;

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const modalEl = this.closest('.modal');
                        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                        modal.hide();

                        showFlashMessage('Reply sent successfully!', 'success');
                    } else {
                        showFlashMessage('Eroare: ' + (data.message || 'The message could not be sent.'), 'danger');
                    }
                })
                .catch(() => showFlashMessage('An error occurred while sending the message.', 'danger'));
        });
    });
});
