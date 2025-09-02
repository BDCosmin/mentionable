document.querySelectorAll('.ajax-comment-form').forEach(form => {
    const gifButton = form.querySelector('.gif-toggle-btn');
    const gifDropdownDesktop = form.querySelector('.gif-dropdown');
    const gifDropdownMobile = form.querySelector('.gif-dropdown-mobile');
    const gifPreviewContainer = form.querySelector('.gif-preview-container');
    const gifPreview = form.querySelector('.gif-preview');
    const gifPreviewClear = form.querySelector('.gif-preview-clear');
    const gifUrlInput = form.querySelector('.gif-url-input');

    // Click pe buton GIF
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

    // Stop propagation pentru dropdown-uri
    [gifDropdownDesktop, gifDropdownMobile].forEach(dd => {
        if (dd) dd.addEventListener('click', e => e.stopPropagation());
    });

    // Clear GIF preview
    gifPreviewClear.addEventListener('click', () => {
        gifPreview.src = '';
        gifPreviewContainer.classList.add('d-none');
        gifUrlInput.value = '';
    });

    // Funcție pentru căutare și afișare rezultate
    function setupGifSearch(dropdown, resultsContainer) {
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

    // Aplica pentru desktop și mobil
    setupGifSearch(gifDropdownDesktop, gifDropdownDesktop?.querySelector('.gif-results'));
    setupGifSearch(gifDropdownMobile, gifDropdownMobile?.querySelector('.gif-results'));

    // Click în afara dropdown-urilor
    document.addEventListener('click', () => {
        if (gifDropdownDesktop) gifDropdownDesktop.classList.add('d-none');
        if (gifDropdownMobile) gifDropdownMobile.classList.add('d-none');
    });
});
