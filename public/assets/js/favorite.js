document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.favorite-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();

            const noteId = btn.dataset.id;
            const icon = btn.querySelector('i');

            fetch('/note/' + noteId + '/favorite', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                },
            })
                .then(response => response.json())
                .then(data => {
                    const favoritesList = document.querySelector('#favorites-list');
                    const noteElement = btn.closest('.note-item');

                    if (data.status === 'added') {
                        btn.classList.add('btn-warning');
                        btn.classList.remove('btn-outline-warning');
                        if (icon) {
                            icon.classList.add('bxs-star');
                            icon.classList.remove('bx-star');
                        }

                        if (favoritesList && noteElement && !favoritesList.querySelector('[data-id="' + noteId + '"]')) {
                            const clone = noteElement.cloneNode(true);
                            favoritesList.prepend(clone);
                        }
                    } else if (data.status === 'removed') {
                        btn.classList.add('btn-outline-warning');
                        btn.classList.remove('btn-warning');
                        if (icon) {
                            icon.classList.add('bx-star');
                            icon.classList.remove('bxs-star');
                        }

                        if (favoritesList) {
                            const favoriteItem = favoritesList.querySelector('[data-id="' + noteId + '"]');
                            if (favoriteItem) favoriteItem.remove();
                        }
                    }
                })
                .catch(() => {
                    alert('A apărut o eroare. Încearcă din nou.');
                });
        });
    });
});
