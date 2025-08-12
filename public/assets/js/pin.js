document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.pin-toggle-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const noteId = btn.dataset.noteId;
            const csrfToken = btn.dataset.csrfToken;

            fetch(`/note/${noteId}/toggle-pin`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `_token=${encodeURIComponent(csrfToken)}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.isPinned !== undefined) {
                        btn.textContent = data.isPinned ? 'Unpin Note' : 'Pin Note';

                        const noteProfile = btn.closest('.navbar-profile');
                        if (!noteProfile) return;

                        let icon = noteProfile.querySelector('.pinned-icon');
                        const nameTagLink = noteProfile.querySelector('#nametagNote');

                        if (data.isPinned) {
                            if (!icon && nameTagLink) {
                                icon = document.createElement('span');
                                icon.className = 'pinned-icon';
                                icon.title = 'Pinned';
                                icon.style.cssText = 'font-size: 1.2rem; margin-left: 7px; color: orange;';
                                icon.textContent = '📌';

                                nameTagLink.appendChild(icon);
                            }
                        } else if (icon) {
                            icon.remove();
                        }
                    } else {
                        alert('Error toggling pin');
                    }
                })
        });
    });
});
