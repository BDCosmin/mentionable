document.addEventListener('DOMContentLoaded', () => {

    // =========================
    // NEW NOTE MODAL GIF HANDLER
    // =========================
    const newNoteModalEl = document.getElementById('newNoteModal');
    let newNoteModal;

    if (newNoteModalEl) {
        newNoteModal = new bootstrap.Modal(newNoteModalEl);

        const gifButton = newNoteModalEl.querySelector('.gif-toggle-btn');
        const gifDropdown = newNoteModalEl.querySelector('#gifDropdown');
        const gifSearch = gifDropdown.querySelector('.gif-search');
        const gifResults = gifDropdown.querySelector('.gif-results');
        const gifPreviewContainer = newNoteModalEl.querySelector('.gif-preview-container');
        const gifPreview = gifPreviewContainer.querySelector('.gif-preview');
        const gifPreviewClear = gifPreviewContainer.querySelector('.gif-preview-clear');
        const noteImageContainer = newNoteModalEl.querySelector('#noteImageContainer');
        const gifUrlInput = newNoteModalEl.querySelector('.gif-url-input');

        // Toggle dropdown
        gifButton.addEventListener('click', e => {
            e.stopPropagation();
            gifDropdown.style.display = gifDropdown.style.display === 'block' ? 'none' : 'block';
            gifDropdown.style.width = window.innerWidth < 768 ? '100%' : '850px';
            setTimeout(() => gifSearch.focus(), 50);
        });

        gifDropdown.addEventListener('click', e => e.stopPropagation());

        gifPreviewClear.addEventListener('click', () => {
            gifPreview.src = '';
            gifPreviewContainer.style.display = 'none';
            noteImageContainer.style.display = 'block';
            gifUrlInput.value = '';
        });

        // Search GIFs
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

        // Submit new note
        const newNoteForm = document.getElementById('new-note-form');
        if (newNoteForm) {
            newNoteForm.addEventListener('submit', async e => {
                e.preventDefault();
                const formData = new FormData(newNoteForm);

                try {
                    const response = await fetch(newNoteForm.action || "/note/new", {
                        method: 'POST',
                        body: formData
                    });
                    if (!response.ok) throw new Error("Error while posting note");

                    newNoteForm.reset();
                    if (newNoteModal) newNoteModal.hide();
                    setTimeout(() => location.reload(), 800);

                } catch (err) {
                    console.error(err);
                }
            });
        }
    }

    // =========================
    // COMMENT GIF HANDLER
    // =========================
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
                        img.classList.add('gif-result-item');
                        img.dataset.url = url;

                        resultsDiv.appendChild(img);
                    });
                } catch (err) {
                    console.error('Error fetching GIFs:', err);
                }
            });
        }

        setupGifSearch(gifDropdownDesktop);
        setupGifSearch(gifDropdownMobile);
    });

    // =========================
    // GLOBAL CLICK LISTENER
    // =========================
    document.addEventListener('click', e => {
        // Close modal dropdown
        if (newNoteModalEl) {
            const gifDropdown = newNoteModalEl.querySelector('#gifDropdown');
            if (gifDropdown && !gifDropdown.contains(e.target) && e.target !== newNoteModalEl.querySelector('.gif-toggle-btn')) {
                gifDropdown.style.display = 'none';
            }
        }

        // Close comment dropdowns
        document.querySelectorAll('.gif-dropdown, .gif-dropdown-mobile').forEach(dd => {
            if (!dd.contains(e.target)) dd.classList.add('d-none');
        });

        // Handle selecting a GIF in comments
        const gif = e.target.closest('.gif-result-item');
        if (gif) {
            const form = gif.closest('.ajax-comment-form');
            if (!form) return;
            const gifUrlInput = form.querySelector('.gif-url-input');
            const previewContainer = form.querySelector('.gif-preview-container');
            const preview = form.querySelector('.gif-preview');
            const url = gif.dataset.url;
            if (gifUrlInput && preview && previewContainer) {
                gifUrlInput.value = url;
                preview.src = url;
                previewContainer.classList.remove('d-none');
                previewContainer.style.display = 'flex';
                previewContainer.style.justifyContent = 'center';
                previewContainer.style.alignItems = 'center';
            }
        }
    });

    // =========================
    // OPEN NEW NOTE MODAL METHOD
    // =========================
    window.openNewNoteModal = () => {
        if (newNoteModal) newNoteModal.show();
    };

});

document.addEventListener('DOMContentLoaded', () => {
    const gifModalEl = document.getElementById('gifCommentModal');
    const gifSearch = gifModalEl.querySelector('.gif-search');
    const gifResults = gifModalEl.querySelector('.gif-results');
    let currentForm = null;

    gifModalEl.addEventListener('show.bs.modal', e => {
        const btn = e.relatedTarget;
        const noteId = btn.dataset.noteId;
        currentForm = document.querySelector(`.ajax-comment-form[data-note-id="${noteId}"]`);
        gifSearch.value = '';
        gifResults.innerHTML = '';
        gifSearch.focus();
    });

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
                    img.style.cursor = 'pointer';
                    img.style.width = '100%';
                    img.addEventListener('click', () => {
                        if (!currentForm) return;
                        const previewContainer = currentForm.querySelector('.gif-preview-container');
                        const preview = currentForm.querySelector('.gif-preview');
                        const input = currentForm.querySelector('.gif-url-input');

                        if (previewContainer && preview && input) {
                            preview.src = url;
                            input.value = url;
                            previewContainer.classList.remove('d-none');
                        }

                        bootstrap.Modal.getInstance(gifModalEl).hide();
                    });
                    gifResults.appendChild(img);
                });
            } catch (err) {
                console.error('Error fetching GIFs:', err);
            }
        }, 300);
    });
});

