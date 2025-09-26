document.addEventListener('DOMContentLoaded', function() {
    const replyModal = document.getElementById('newCommentReplyModal');
    const replyForm = document.getElementById('reply-form');

    const gifButton = replyModal.querySelector('.gif-reply-toggle-btn');
    const gifDropdown = replyModal.querySelector('.gif-reply-dropdown');
    const gifSearch = gifDropdown.querySelector('.gif-reply-search');
    const gifResults = gifDropdown.querySelector('.gif-reply-results');
    const gifPreviewContainer = replyModal.querySelector('.gif-reply-preview-container');
    const gifPreview = gifPreviewContainer.querySelector('.gif-reply-preview');
    const gifPreviewClear = gifPreviewContainer.querySelector('.gif-reply-preview-clear');
    const gifUrlInput = replyModal.querySelector('.gif-reply-url-input');

    replyModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;

        document.getElementById('reply-username').textContent = '';
        document.getElementById('parent-comment-id').value = '';
        document.getElementById('parent-comment-message').textContent = '';

        const parentMessageEl = document.getElementById('parent-comment-message');
        const parentMessageWrapper = parentMessageEl.closest('div');
        parentMessageWrapper.style.display = 'none';

        const parentImgEl = document.getElementById('parent-comment-img');
        parentImgEl.style.display = 'none';

        const username = button.getAttribute('data-username');
        const comment = button.getAttribute('data-comment-id');
        const commentMessage = button.getAttribute('data-comment-message');
        const commentImg = button.getAttribute('data-comment-img');

        document.getElementById('reply-username').textContent = username;
        document.getElementById('parent-comment-id').value = comment;

        if (commentMessage && commentMessage.trim() !== "") {
            parentMessageEl.textContent = commentMessage;
            parentMessageEl.style.display = 'inline-block';
            parentMessageEl.style.backgroundColor = '#ffffff';
            parentMessageEl.style.opacity = '0.9';
        } else {
            parentMessageEl.textContent = '';
            parentMessageEl.style.display = 'none';
            parentMessageEl.style.backgroundColor = 'transparent';
            parentMessageEl.style.opacity = '0';
        }

        if (commentImg) {
            parentImgEl.src = commentImg.startsWith('http')
                ? commentImg
                : window.location.origin + commentImg;
            parentImgEl.style.display = 'block';
        } else {
            parentImgEl.removeAttribute('src');
            parentImgEl.style.display = 'none';
        }

        gifUrlInput.value = '';
        gifPreview.src = '';
        gifPreviewContainer.classList.add('d-none');

        document.getElementById('reply-form').action = `/comment/${comment}/reply`;

        fetch(`/comment/${comment}/replies`)
            .then(res => res.text())
            .then(html => {
                document.getElementById('replies-container').innerHTML = html;
            });
    });

    gifButton.addEventListener('click', e => {
        e.stopPropagation();
        gifDropdown.style.display = gifDropdown.style.display === 'block' ? 'none' : 'block';
        setTimeout(() => gifSearch.focus(), 50);
    });

    gifDropdown.addEventListener('click', e => e.stopPropagation());

    gifPreviewClear.addEventListener('click', () => {
        gifPreview.src = '';
        gifPreviewContainer.classList.add('d-none');
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
                        gifUrlInput.value = url;
                        gifPreview.src = url;
                        gifPreviewContainer.classList.remove('d-none');
                        gifPreviewContainer.style.display = 'flex';
                        gifPreviewContainer.style.justifyContent = 'center';
                        gifPreviewContainer.style.alignItems = 'center';
                        gifDropdown.style.display = 'none';
                    });
                    gifResults.appendChild(img);
                });
            } catch (err) {
                console.error('Error fetching GIFs:', err);
            }
        }, 300);
    });

    replyForm.addEventListener('submit', e => {
        e.preventDefault();
        const commentId = replyForm.querySelector('#parent-comment-id').value;
        const gifUrl = replyForm.querySelector('.gif-url-input').value;
        const message = replyForm.querySelector('textarea[name="message"]').value;

        fetch(`/comment/${commentId}/reply`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({message, gif_url: gifUrl})
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    replyForm.reset();
                    gifPreview.src = '';
                    gifPreviewContainer.classList.add('d-none');
                    gifPreviewContainer.style.display = 'none';
                    gifDropdown.style.display = 'none';
                    bootstrap.Modal.getInstance(replyModal).hide();
                } else {
                    alert(data.error || 'Something went wrong.');
                }
            })
            .catch(err => console.error('Error submitting reply:', err));
    });
});