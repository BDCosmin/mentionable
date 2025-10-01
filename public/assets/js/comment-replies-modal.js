document.addEventListener('DOMContentLoaded', function() {
    const replyModal = document.getElementById('newCommentReplyModal');
    const replyForm = document.getElementById('reply-form');
    const gifButtons = replyModal.querySelectorAll('.gif-reply-toggle-btn');
    const gifPreviewContainer = replyModal.querySelector('.gif-reply-preview-container');
    const gifPreview = gifPreviewContainer.querySelector('.gif-reply-preview');
    const gifPreviewClear = gifPreviewContainer.querySelector('.gif-reply-preview-clear');
    const gifUrlInput = replyModal.querySelector('.gif-reply-url-input');

    replyForm.addEventListener('click', e => {
        const btn = e.target.closest('.emoji-reply-toggle-btn');
        if (!btn) return;

        e.preventDefault();
        const form = btn.closest('form');
        if (!form) return;

        const emojiListDiv = form.querySelector('.emoji-reply-list');
        if (!emojiListDiv) return;

        emojiListDiv.style.display = (emojiListDiv.style.display === 'flex') ? 'none' : 'flex';
        emojiListDiv.style.flexWrap = 'wrap';
        emojiListDiv.style.gap = '5px';

        if (!emojiListDiv.dataset.loaded) {
            fetch('/api/emojis')
                .then(res => res.json())
                .then(emojis => {
                    emojis.slice(0, 100).forEach(emoji => {
                        const eBtn = document.createElement('button');
                        eBtn.type = 'button';
                        eBtn.textContent = emoji.character;
                        eBtn.style.fontSize = '20px';
                        eBtn.style.background = 'transparent';
                        eBtn.style.border = 'none';
                        eBtn.style.cursor = 'pointer';
                        eBtn.addEventListener('click', () => {
                            const textarea = form.querySelector('textarea[name="message"]');
                            if (textarea) {
                                textarea.value += emoji.character;
                                textarea.focus();
                            }
                        });
                        emojiListDiv.appendChild(eBtn);
                    });
                    emojiListDiv.dataset.loaded = 'true';
                })
                .catch(err => console.error('Emoji fetch error:', err));
        }
    });

    document.addEventListener('click', e => {
        document.querySelectorAll('.emoji-reply-list').forEach(list => {
            if (!list.contains(e.target) && !e.target.closest('.emoji-reply-toggle-btn')) {
                list.style.display = 'none';
            }
        });
    });

    replyModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const username = button.getAttribute('data-username');
        const comment = button.getAttribute('data-comment-id');
        const commentMessage = button.getAttribute('data-comment-message');
        const commentImg = button.getAttribute('data-comment-img');

        document.getElementById('reply-username').textContent = username;
        document.getElementById('parent-comment-id').value = comment;

        const parentMessageEl = document.getElementById('parent-comment-message');
        parentMessageEl.textContent = commentMessage || '';
        parentMessageEl.style.display = commentMessage ? 'inline-block' : 'none';

        const parentImgEl = document.getElementById('parent-comment-img');
        if (commentImg) {
            parentImgEl.src = commentImg.startsWith('http') ? commentImg : window.location.origin + commentImg;
            parentImgEl.style.display = 'block';
        } else {
            parentImgEl.removeAttribute('src');
            parentImgEl.style.display = 'none';
        }

        gifUrlInput.value = '';
        gifPreview.src = '';
        gifPreviewContainer.classList.add('d-none');
        replyForm.action = `/comment/${comment}/reply`;

        fetch(`/comment/${comment}/replies`)
            .then(res => res.text())
            .then(html => {
                document.getElementById('replies-container').innerHTML = html;
            });
    });

    // --- GIF și submit ---
    let gifTimeout;
    gifButtons.forEach(button => {
        button.addEventListener('click', e => {
            e.stopPropagation();
            const targetSelector = button.getAttribute('data-target');
            const dropdown = replyModal.querySelector(targetSelector);
            if (!dropdown) return;

            replyModal.querySelectorAll('.gif-reply-dropdown').forEach(dd => {
                if (dd !== dropdown) dd.style.display = 'none';
            });

            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            const search = dropdown.querySelector('.gif-reply-search');
            if (search) setTimeout(() => search.focus(), 50);

            const results = dropdown.querySelector('.gif-reply-results');
            search.addEventListener('keyup', e => {
                const query = e.target.value.trim();
                if (query.length < 2) return;

                clearTimeout(gifTimeout);
                gifTimeout = setTimeout(async () => {
                    try {
                        const response = await fetch(`/gif/search/${encodeURIComponent(query)}`);
                        const gifs = await response.json();
                        results.innerHTML = '';
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
                                dropdown.style.display = 'none';
                            });
                            results.appendChild(img);
                        });
                    } catch (err) {
                        console.error('Error fetching GIFs:', err);
                    }
                }, 300);
            });
        });
    });

    gifPreviewClear.addEventListener('click', () => {
        gifPreview.src = '';
        gifPreviewContainer.classList.add('d-none');
        gifUrlInput.value = '';
    });

    replyForm.addEventListener('submit', e => {
        e.preventDefault();
        const textarea = replyForm.querySelector('textarea[name="message"]');
        console.log('Message before submit:', textarea.value);
        const message = textarea.value;
        const commentId = replyForm.querySelector('#parent-comment-id').value;
        const gifUrl = replyForm.querySelector('.gif-reply-url-input').value;

        console.log("Message before submit:", message);

        fetch(`/comment/${commentId}/reply`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({message, gif_url: gifUrl})
        })
            .then(async res => {
                if (!res.ok) {
                    const text = await res.text();
                    console.error("Server responded with error:", res.status, text);
                    throw new Error(text);
                }
                return res.json();
            })
            .then(data => {
                console.log("Server response:", data);
                if (data.success) {
                    replyForm.reset();
                    gifPreview.src = '';
                    gifPreviewContainer.classList.add('d-none');
                    gifPreviewContainer.style.display = 'none';
                    replyModal.querySelectorAll('.gif-reply-dropdown').forEach(dd => dd.style.display = 'none');

                    const repliesContainer = document.getElementById('replies-container');
                    if (repliesContainer && data.html) {
                        repliesContainer.insertAdjacentHTML('afterbegin', data.html);
                    }

                    const counterSpan = document.getElementById(`reply-count-${data.commentId}`);
                    if (counterSpan) counterSpan.textContent = data.repliesCount;

                } else {
                    alert(data.error || 'Something went wrong.');
                }
            })
            .catch(err => console.error('Error submitting reply:', err));
    });
});
