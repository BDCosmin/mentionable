function isOnlyEmojis(str) {
    const clean = str.trim();
    const emojiRegex = /^(?:[\u2700-\u27BF]|[\uE000-\uF8FF]|\u24C2|[\uD83C-\uDBFF\uDC00-\uDFFF])+$/;
    return emojiRegex.test(clean);
}

/////////////// COMMENT SUBMIT ///////////////////
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[id^="comment-message-"]').forEach(commentEl => {
        const msg = commentEl.dataset.originalMessage || commentEl.textContent || '';
        if (isOnlyEmojis(msg)) {
            const small = commentEl.querySelector('small');
            if (small) {
                small.style.backgroundColor = 'transparent';
                small.style.padding = '0';
                small.style.fontSize = '18px';
                small.style.borderRadius = '0';
                small.style.opacity = '1';
            }
        }
    });
    document.addEventListener('submit', function(e) {
        const form = e.target.closest('.ajax-comment-form');
        if (!form) return;

        e.preventDefault();

        const noteId = form.dataset.noteId;
        const input = form.querySelector('.comment-input');
        const message = input.value.trim();
        const gifUrlInput = form.querySelector('.gif-url-input');
        const gifUrl = gifUrlInput ? gifUrlInput.value.trim() : '';
        const csrfToken = document.querySelector('meta[name="csrf-token-comment"]').content;

        if (!message && !gifUrl) return;

        const body = new URLSearchParams();
        if (message) body.append('message', message);
        if (gifUrl) body.append('gif_url', gifUrl);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            body: body
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const commentsContainer = form.closest('.note-comments').querySelector('.new-comments');
                    commentsContainer.insertAdjacentHTML('afterbegin', data.html);

                    commentsContainer.querySelectorAll('[id^="comment-message-"]').forEach(commentEl => {
                        const msgText = commentEl.textContent.trim();
                        if (isOnlyEmojis(msgText)) {
                            commentEl.style.backgroundColor = 'transparent';
                            commentEl.style.padding = '0';
                            commentEl.style.fontSize = '18px';
                            commentEl.style.borderRadius = '0';
                            commentEl.style.opacity = '1';
                        } else {
                            commentEl.style.backgroundColor = 'white';
                            commentEl.style.padding = '5px';
                            commentEl.style.fontSize = '16px';
                            commentEl.style.borderRadius = '8px';
                            commentEl.style.opacity = '0.9';
                        }
                    });

                    input.value = '';
                    if (gifUrlInput) gifUrlInput.value = '';
                    const previewContainer = form.querySelector('.gif-preview-container');
                    const preview = form.querySelector('.gif-preview');
                    if (preview) preview.src = '';
                    if (previewContainer) previewContainer.classList.add('d-none');

                    const countSpan = document.querySelector(`.toggle-comments[data-note-id="${noteId}"] .comment-count`);
                    if (countSpan) countSpan.textContent = (parseInt(countSpan.textContent) || 0) + 1;

                    hideCommentError(form);
                } else {
                    showCommentError(form, data.message || 'Something went wrong.');
                }
            })
            .catch(err => showCommentError(form, err?.message || 'Something went wrong.'));
    });

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.gif-preview-clear');
        if (!btn) return;
        const container = btn.closest('.gif-preview-container');
        if (!container) return;
        const img = container.querySelector('.gif-preview');
        const input = container.querySelector('.gif-url-input');
        if (img) img.src = '';
        if (input) input.value = '';
        container.classList.add('d-none');
    });

    // Select GIF
    document.addEventListener('click', function(e) {
        const gif = e.target.closest('.gif-result-item');
        if (!gif) return;
        const form = gif.closest('.ajax-comment-form');
        if (!form) return;
        const gifUrlInput = form.querySelector('.gif-url-input');
        const previewContainer = form.querySelector('.gif-preview-container');
        const preview = form.querySelector('.gif-preview');
        if (gifUrlInput && preview && previewContainer) {
            const url = gif.dataset.url;
            gifUrlInput.value = url;
            preview.src = url;
            previewContainer.classList.remove('d-none');
            previewContainer.style.display = 'flex';
            previewContainer.style.justifyContent = 'center';
            previewContainer.style.alignItems = 'center';
        }
    });


    function showCommentError(form, message) {
        const errorDiv = form.querySelector('.comment-error');
        if (!errorDiv) return;
        const span = errorDiv.querySelector('.error-message');
        if (span) span.textContent = message;
        errorDiv.classList.remove('d-none');
        errorDiv.classList.add('show');
    }

    function hideCommentError(form) {
        const errorDiv = form.querySelector('.comment-error');
        if (!errorDiv) return;
        errorDiv.classList.add('d-none');
        errorDiv.classList.remove('show');
    }

/////////////// COMMENT DELETE ///////////////////
    document.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.delete-comment-btn');
        if (!deleteBtn) return;

        e.preventDefault();

        const commentElement = deleteBtn.closest('.d-flex.flex-row.mb-3');
        if (!commentElement) return;

        const url = deleteBtn.getAttribute('href');
        if (!url) return;

        const noteContainer = commentElement.closest('[data-note-id]');
        if (!noteContainer) return;
        const noteId = noteContainer.dataset.noteId;

        const csrfToken = document.querySelector('meta[name="csrf-token-comment"]').content;

        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            }
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    commentElement.remove();

                    const countSpan = document.querySelector(`.toggle-comments[data-note-id="${noteId}"] .comment-count`);
                    if (countSpan) {
                        const current = parseInt(countSpan.textContent || '0');
                        countSpan.textContent = Math.max(0, current - 1).toString();
                    }
                } else {
                    alert('An error occurred while deleting the comment.');
                }
            })
            .catch(err => console.error('Deleting error:', err));
    });

/////////////// COMMENT UPVOTE ///////////////////
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.comment-upvote-btn');
        if (!btn) return;

        e.preventDefault();

        const noteId = btn.dataset.noteId;
        const commentId = btn.dataset.commentId;
        const csrfToken = btn.dataset.csrf;

        fetch(`/note/comment/${noteId}-${commentId}/upvote`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            }
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.querySelector(`#comment-upvotes-${commentId}`).textContent = data.upvotes;
                    btn.classList.toggle('btn-light');
                    btn.classList.toggle('btn-outline-light');
                } else {
                    console.error(data.message || 'Unknown error');
                }
            })
            .catch(err => console.error('Upvote fetch error:', err));
    });

});

