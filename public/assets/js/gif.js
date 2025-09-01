const newNoteModal = document.getElementById('newNoteModal');
newNoteModal.addEventListener('shown.bs.modal', () => {
    const gifButton = document.getElementById('gifButton');
    const gifDropdown = document.getElementById('gifDropdown');
    const gifSearch = document.getElementById('gifSearch');
    const gifResults = document.getElementById('gifResults');
    const postContent = document.getElementById('post-content');
    const gifPreviewContainer = document.getElementById('gifPreviewContainer');
    const gifPreview = document.getElementById('gifPreview');
    const gifPreviewClear = document.getElementById('gifPreviewClear');
    const noteImageContainer = document.getElementById('noteImageContainer');

    gifButton.addEventListener('click', (e) => {
        e.stopPropagation();
        gifDropdown.style.display = gifDropdown.style.display === 'none' ? 'block' : 'none';
        gifSearch.focus();
    });

    gifDropdown.addEventListener('click', (e) => e.stopPropagation());

    gifPreviewClear.addEventListener('click', () => {
        gifPreview.src = '';
        gifPreviewContainer.style.display = 'none';

        noteImageContainer.style.display = 'block';

        document.getElementById('gifUrlInput').value = '';
        console.log(document.getElementById('gifUrlInput').value);
    });

    gifSearch.addEventListener('keyup', async (e) => {
        const query = e.target.value;
        if (query.length < 2) return;

        try {
            const response = await fetch(`/gif/search/${query}`);
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
                    gifPreviewContainer.style.display = 'block';

                    noteImageContainer.style.display = 'none';

                    document.getElementById('gifUrlInput').value = url;
                    console.log(document.getElementById('gifUrlInput').value);

                });

                gifResults.appendChild(img);
            });
        } catch (err) {
            console.error('Error fetching GIFs:', err);
        }
    });
});

document.querySelectorAll('.ajax-comment-form').forEach(form => {
    const gifButton = form.querySelector('.gif-toggle-btn');
    const gifDropdown = form.querySelector('.gif-dropdown');
    const gifSearch = form.querySelector('.gif-search');
    const gifResults = form.querySelector('.gif-results');
    const gifPreviewContainer = form.querySelector('.gif-preview-container');
    const gifPreview = form.querySelector('.gif-preview');
    const gifPreviewClear = form.querySelector('.gif-preview-clear');
    const gifUrlInput = form.querySelector('.gif-url-input');

    gifButton.addEventListener('click', e => {
        e.stopPropagation();
        gifDropdown.classList.toggle('d-none');
        gifSearch.focus();
    });

    gifDropdown.addEventListener('click', e => e.stopPropagation());

    gifPreviewClear.addEventListener('click', () => {
        gifPreview.src = '';
        gifPreviewContainer.classList.add('d-none');
        gifUrlInput.value = '';
    });

    gifSearch.addEventListener('keyup', async e => {
        const query = e.target.value.trim();
        if (query.length < 2) return;

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
                    gifDropdown.classList.add('d-none');
                    gifPreview.src = url;
                    gifPreviewContainer.classList.remove('d-none');
                    gifUrlInput.value = url;
                });
                gifResults.appendChild(img);
            });
        } catch (err) {
            console.error('Error fetching GIFs:', err);
        }
    });

    document.addEventListener('click', () => {
        gifDropdown.classList.add('d-none');
    });
});

