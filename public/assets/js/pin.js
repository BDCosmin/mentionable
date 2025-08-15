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

                        // Get the dropdown container
                        const dropdown = btn.closest('.dropdown');
                        if (!dropdown) return;

                        // Try to find existing pin icon inside dropdown
                        let icon = dropdown.querySelector('.pinned-icon');

                        if (data.isPinned) {
                            // If no icon exists, create it
                            if (!icon) {
                                icon = document.createElement('i');
                                icon.className = 'bx bx-pin pinned-icon me-3';
                                icon.title = 'Pinned';
                                icon.style.cssText = 'color:#ffffff; font-size: 20px';

                                // Insert before the dropdown toggle <a>
                                const dropdownToggle = dropdown.querySelector('[data-bs-toggle="dropdown"]');
                                if (dropdownToggle) {
                                    dropdown.insertBefore(icon, dropdownToggle);
                                }
                            }
                        } else if (icon) {
                            // Remove icon if unpinned
                            icon.remove();
                        }
                    } else {
                        alert('Error toggling pin');
                    }
                });
        });
    });
});
