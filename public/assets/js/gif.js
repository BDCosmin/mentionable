document.addEventListener('DOMContentLoaded', function () {
    const newNoteModalEl = document.getElementById('newNoteModal');
    if (!newNoteModalEl) return;

    // Creează instanța Bootstrap Modal
    const newNoteModal = new bootstrap.Modal(newNoteModalEl);

    newNoteModalEl.addEventListener('shown.bs.modal', () => {
        const gifButton = newNoteModalEl.querySelector('.gif-toggle-btn');
        const gifDropdown = newNoteModalEl.querySelector('#gifDropdown');
        const gifSearch = gifDropdown.querySelector('.gif-search');
        const gifResults = gifDropdown.querySelector('.gif-results');
        const gifPreviewContainer = newNoteModalEl.querySelector('.gif-preview-container');
        const gifPreview = gifPreviewContainer.querySelector('.gif-preview');
        const gifPreviewClear = gifPreviewContainer.querySelector('.gif-preview-clear');
        const noteImageContainer = newNoteModalEl.querySelector('#noteImageContainer');
        const gifUrlInput = newNoteModalEl.querySelector('.gif-url-input');

        // Deschide/închide dropdown GIF
        gifButton.addEventListener('click', e => {
            e.stopPropagation();
            gifDropdown.style.display = gifDropdown.style.display === 'block' ? 'none' : 'block';
            gifDropdown.style.width = window.innerWidth < 768 ? '100%' : '850px';
            gifSearch.focus();
        });

        gifDropdown.addEventListener('click', e => e.stopPropagation());

        // Clear GIF preview
        gifPreviewClear.addEventListener('click', () => {
            gifPreview.src = '';
            gifPreviewContainer.style.display = 'none';
            noteImageContainer.style.display = 'block';
            gifUrlInput.value = '';
        });

        // Căutare GIF
        let gifTimeout;
        gifSearch.addEventListener('keyup', e => {
            const query = e.target.value.trim();
            if (query.length < 2) return;

            clearTimeout(gifTimeout);
            gifTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`/gif/search/${encodeURIComponent(query)}`);
                    const gifs = await response.json();

                    gifResults.innerHTML = '';
                    gifs.forEach(url => {
                        const img = document.createElement('img');
                        img.src = url;
                        img.style.width = '100%';
                        img.style.cursor = 'pointer';
                        img.addEventListener('click', () => {
                            gifDropdown.style.display = 'none';
                            gifPreview.src = url;
                            gifPreviewContainer.style.display = 'flex';
                            gifPreviewContainer.style.justifyContent = 'center';
                            gifPreviewContainer.style.alignItems = 'center';
                            noteImageContainer.style.display = 'none';
                            gifUrlInput.value = url;
                        });
                        gifResults.appendChild(img);
                    });
                } catch (err) {
                    console.error('Error fetching GIFs:', err);
                }
            }, 300);
        });

        document.addEventListener('click', () => {
            gifDropdown.style.display = 'none';
        });
    });

    // Închiderea modalului prin API Bootstrap (fără manipulări manuale)
    document.getElementById('new-note-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        try {
            const res = await fetch(this.action, { method: 'POST', body: formData });
            if (!res.ok) throw new Error('Server error');

            // Închide modalul corect
            newNoteModal.hide();

            this.reset();
            location.reload();
        } catch (err) {
            console.error(err);
            newNoteModal.hide(); // și aici
        }
    });

    document.querySelectorAll('.ajax-comment-form').forEach(form => {
        const gifButton = form.querySelector('.gif-toggle-btn');
        const gifDropdownDesktop = form.querySelector('.gif-dropdown');
        const gifDropdownMobile = form.querySelector('.gif-dropdown-mobile');
        const gifPreviewContainer = form.querySelector('.gif-preview-container');
        const gifPreview = form.querySelector('.gif-preview');
        const gifPreviewClear = form.querySelector('.gif-preview-clear');
        const gifUrlInput = form.querySelector('.gif-url-input');

        gifButton.addEventListener('click', e => {
            e.stopPropagation();
            const isMobile = window.innerWidth < 576;
            if (isMobile && gifDropdownMobile) {
                gifDropdownMobile.classList.toggle('d-none');
                gifDropdownMobile.querySelector('.gif-search').focus();
            } else if (!isMobile && gifDropdownDesktop) {
                gifDropdownDesktop.classList.toggle('d-none');
                gifDropdownDesktop.querySelector('.gif-search').focus();
            }
        });

        [gifDropdownDesktop, gifDropdownMobile].forEach(dd => {
            if (dd) dd.addEventListener('click', e => e.stopPropagation());
        });

        gifPreviewClear.addEventListener('click', () => {
            gifPreview.src = '';
            gifPreviewContainer.classList.add('d-none');
            gifUrlInput.value = '';
        });

        function setupGifSearch(dropdown) {
            if (!dropdown) return;
            const searchInput = dropdown.querySelector('.gif-search');
            const resultsDiv = dropdown.querySelector('.gif-results');

            searchInput.addEventListener('keyup', async e => {
                const query = e.target.value.trim();
                if (query.length < 2) return;

                try {
                    const response = await fetch(`/gif/search/${encodeURIComponent(query)}`);
                    const gifs = await response.json();
                    resultsDiv.innerHTML = '';

                    gifs.forEach(url => {
                        const img = document.createElement('img');
                        img.src = url;
                        img.style.width = '100%';
                        img.style.cursor = 'pointer';
                        img.addEventListener('click', () => {
                            if (gifDropdownDesktop) gifDropdownDesktop.classList.add('d-none');
                            if (gifDropdownMobile) gifDropdownMobile.classList.add('d-none');
                            gifPreview.src = url;
                            gifPreviewContainer.classList.remove('d-none');
                            gifPreviewContainer.style.display = 'flex';
                            gifPreviewContainer.style.justifyContent = 'center';
                            gifPreviewContainer.style.alignItems = 'center';
                            gifUrlInput.value = url;
                        });
                        resultsDiv.appendChild(img);
                    });
                } catch (err) {
                    console.error('Error fetching GIFs:', err);
                }
            });
        }

        setupGifSearch(gifDropdownDesktop);
        setupGifSearch(gifDropdownMobile);

        document.addEventListener('click', () => {
            if (gifDropdownDesktop) gifDropdownDesktop.classList.add('d-none');
            if (gifDropdownMobile) gifDropdownMobile.classList.add('d-none');
        });
    });
});