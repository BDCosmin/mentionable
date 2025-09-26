document.addEventListener('DOMContentLoaded', () => {

    // NEW NOTE MODAL GIF
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

        gifButton.addEventListener('click', e => {
            e.stopPropagation();
            gifDropdown.style.display = gifDropdown.style.display === 'block' ? 'none' : 'block';
            gifDropdown.style.width = window.innerWidth < 768 ? '100%' : '850px';
            setTimeout(() => gifSearch.focus(), 50);
        });

        gifDropdown.addEventListener('click', e => e.stopPropagation());

        gifPreviewClear.addEventListener('click', () => {
            gifPreview.src = '';

            gifPreviewContainer.classList.add('d-none');
            gifPreviewContainer.style.display = 'none';

            noteImageContainer.style.display = 'block';
            gifUrlInput.value = '';
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
                        img.style.width = '100%';
                        img.style.cursor = 'pointer';

                        img.addEventListener('click', () => {
                            gifDropdown.style.display = 'none';

                            gifPreview.src = url;

                            gifPreviewContainer.classList.remove('d-none');
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
    }

    // COMMENT REPLY MODAL GIF
    const newCommentReplyModalEl = document.getElementById('newCommentReplyModal');
    let newCommentReplyModal;

    if (newCommentReplyModalEl) {
        newCommentReplyModal = new bootstrap.Modal(newCommentReplyModalEl);

        let currentCommentId = null;

        newCommentReplyModalEl.addEventListener('show.bs.modal', e => {
            const btn = e.relatedTarget;
            currentCommentId = btn.dataset.commentId;

            const replyForm = newCommentReplyModalEl.querySelector('.ajax-reply-form');
            replyForm.dataset.commentId = currentCommentId;
            replyForm.querySelector('#parent-comment-id').value = currentCommentId;

            const parentMessageEl = newCommentReplyModalEl.querySelector('#parent-comment-message');
            parentMessageEl.style.display = btn.dataset.commentMessage ? 'block' : 'none';
            parentMessageEl.textContent = btn.dataset.commentMessage || '';

            const parentImgEl = newCommentReplyModalEl.querySelector('#parent-comment-img');
            if (btn.dataset.commentImg) {
                parentImgEl.src = btn.dataset.commentImg;
                parentImgEl.style.display = 'block';
            } else {
                parentImgEl.style.display = 'none';
            }

            const gifInput = replyForm.querySelector('.gif-url-input');
            const gifPreviewContainer = replyForm.querySelector('.gif-preview-container');
            const gifPreview = replyForm.querySelector('.gif-preview');
            gifInput.value = '';
            gifPreview.src = '';
            gifPreviewContainer.classList.add('d-none');
        });

        const gifReplyButton = newCommentReplyModalEl.querySelector('.gif-toggle-btn');
        const gifReplyDropdown = newCommentReplyModalEl.querySelector('#gifDropdown');
        const gifReplySearch = gifReplyDropdown.querySelector('.gif-search');
        const gifReplyResults = gifReplyDropdown.querySelector('.gif-results');
        const gifReplyPreviewContainer = newCommentReplyModalEl.querySelector('.gif-preview-container');
        const gifReplyPreview = gifReplyPreviewContainer.querySelector('.gif-preview');
        const gifReplyPreviewClear = gifReplyPreviewContainer.querySelector('.gif-preview-clear');
        const gifReplyUrlInput = newCommentReplyModalEl.querySelector('.gif-url-input');

        gifReplyButton.addEventListener('click', e => {
            e.stopPropagation();
            gifReplyDropdown.style.display = gifReplyDropdown.style.display === 'block' ? 'none' : 'block';
            gifReplyDropdown.style.width = window.innerWidth < 768 ? '100%' : '850px';
            setTimeout(() => gifReplySearch.focus(), 50);
        });

        gifReplyDropdown.addEventListener('click', e => e.stopPropagation());

        gifReplyPreviewClear.addEventListener('click', () => {
            gifReplyPreview.src = '';

            gifReplyPreviewContainer.classList.add('d-none');
            gifReplyPreviewContainer.style.display = 'none';

            gifReplyUrlInput.value = '';
        });


        let gifTimeout;
        gifReplySearch.addEventListener('keyup', e => {
            const query = e.target.value.trim();
            if (query.length < 2) return;

            clearTimeout(gifTimeout);
            gifTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`/gif/search/${encodeURIComponent(query)}`);
                    const gifs = await response.json();
                    gifReplyResults.innerHTML = '';

                    gifs.forEach(url => {
                        const img = document.createElement('img');
                        img.src = url;
                        img.style.width = '100%';
                        img.style.cursor = 'pointer';

                        img.addEventListener('click', () => {
                            const replyForm = newCommentReplyModalEl.querySelector('.ajax-reply-form'); // always the modal form
                            const gifInput = replyForm.querySelector('.gif-url-input');
                            const gifPreview = replyForm.querySelector('.gif-preview');
                            const gifPreviewContainer = replyForm.querySelector('.gif-preview-container');

                            gifInput.value = url;
                            gifPreview.src = url;
                            gifPreviewContainer.classList.remove('d-none');
                            gifPreviewContainer.style.display = 'flex';
                            gifPreviewContainer.style.justifyContent = 'center';
                            gifPreviewContainer.style.alignItems = 'center';

                            gifReplyDropdown.style.display = 'none';
                        });

                        gifReplyResults.appendChild(img);
                    });
                } catch (err) {
                    console.error('Error fetching GIFs:', err);
                }
            }, 300);
        });
        const replyForm = newCommentReplyModalEl.querySelector('.ajax-reply-form');
        replyForm.addEventListener('submit', e => {
            e.preventDefault();
            const commentId = replyForm.querySelector('#parent-comment-id').value;
            const gifUrl = replyForm.querySelector('.gif-url-input').value;
            const message = replyForm.querySelector('textarea[name="message"]').value;

            fetch(`/comment-reply/${commentId}`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({message, gif_url: gifUrl})
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        replyForm.reset();
                        replyForm.querySelector('.gif-preview').src = '';
                        replyForm.querySelector('.gif-preview-container').classList.add('d-none');
                        newCommentReplyModal.hide();
                    } else {
                        alert(data.error || 'Something went wrong.');
                    }
                })
                .catch(err => console.error('Error submitting reply:', err));
        });
    }

    // COMMENT GIF
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

    // GIF DROPDOWN NOTE & COMMENT
    document.addEventListener('click', e => {
        if (newNoteModalEl) {
            const gifDropdown = newNoteModalEl.querySelector('#gifDropdown');
            if (gifDropdown && !gifDropdown.contains(e.target) && e.target !== newNoteModalEl.querySelector('.gif-toggle-btn')) {
                gifDropdown.style.display = 'none';
            }
        }

        document.querySelectorAll('.gif-dropdown, .gif-dropdown-mobile').forEach(dd => {
            if (!dd.contains(e.target)) dd.classList.add('d-none');
        });

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

