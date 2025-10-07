function isOnlyEmojis(str) {
    const clean = str.trim();
    const emojiRegex = /^(?:[\u2700-\u27BF]|[\uE000-\uF8FF]|\u24C2|[\uD83C-\uDBFF\uDC00-\uDFFF])+$/;

    return emojiRegex.test(clean);
}

document.addEventListener('DOMContentLoaded', function() {
    const replyModal = document.getElementById('newCommentReplyModal');
    if (!replyModal) return;

    const replyForm = replyModal.querySelector('#reply-form');
    const gifButtons = replyModal.querySelectorAll('.gif-reply-toggle-btn');
    const gifPreviewContainer = replyModal.querySelector('.gif-reply-preview-container');
    const gifPreview = gifPreviewContainer ? gifPreviewContainer.querySelector('.gif-reply-preview') : null;
    const gifPreviewClear = gifPreviewContainer ? gifPreviewContainer.querySelector('.gif-reply-preview-clear') : null;
    const gifUrlInput = replyModal.querySelector('.gif-reply-url-input');
    const repliesContainer = replyModal.querySelector('#replies-container');

////////////// EMOJI TOGGLE REPLY //////////////////
    replyForm?.addEventListener('click', e => {
        const btn = e.target.closest('.emoji-reply-toggle-btn');
        if (!btn) return;

        e.preventDefault();
        const form = btn.closest('form');
        if (!form) return;

        const emojiListDiv = form.querySelector('.emoji-reply-list');
        if (!emojiListDiv) return;

        emojiListDiv.style.display = emojiListDiv.style.display === 'flex' ? 'none' : 'flex';
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

////////////// MODAL SHOW REPLY //////////////////
    replyModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        if (!button) return;

        const username = button.getAttribute('data-username');
        const commentId = button.getAttribute('data-comment-id');
        const commentMessage = button.getAttribute('data-comment-message');
        const commentImg = button.getAttribute('data-comment-img');

        replyModal.querySelector('#reply-username').textContent = username;
        replyForm.querySelector('#parent-comment-id').value = commentId;

        const parentMessageEl = replyModal.querySelector('#parent-comment-message');
        if (parentMessageEl) {
            parentMessageEl.textContent = commentMessage || '';
            if (isOnlyEmojis(commentMessage || '')) {
                parentMessageEl.style.backgroundColor = 'transparent';
                parentMessageEl.style.display = commentMessage ? 'inline-block' : 'none';
                parentMessageEl.style.padding = '';
                parentMessageEl.style.borderRadius = '';
                parentMessageEl.style.fontSize = '20px';
            } else {
                parentMessageEl.style.backgroundColor = 'white';
                parentMessageEl.style.display = commentMessage ? 'inline-block' : 'none';
                parentMessageEl.style.padding = '5px';
                parentMessageEl.style.borderRadius = '8px';
                parentMessageEl.style.fontSize = '20px';
            }
        }

        const parentImgEl = replyModal.querySelector('#parent-comment-img');
        if (parentImgEl) {
            if (commentImg) {
                parentImgEl.src = commentImg.startsWith('http') ? commentImg : window.location.origin + commentImg;
                parentImgEl.style.display = 'block';
            } else {
                parentImgEl.removeAttribute('src');
                parentImgEl.style.display = 'none';
            }
        }

        if (gifPreview) gifPreview.src = '';
        if (gifPreviewContainer) gifPreviewContainer.classList.add('d-none');
        if (gifUrlInput) gifUrlInput.value = '';
        replyForm.action = `/comment/${commentId}/reply`;

        fetch(`/comment/${commentId}/replies`)
            .then(res => res.text())
            .then(html => {
                if (repliesContainer) {
                    repliesContainer.innerHTML = html;
                    repliesContainer.querySelectorAll('[id^="reply-message-"]').forEach(replyEl => {
                        const msg = replyEl.dataset.originalMessage || '';
                        if (isOnlyEmojis(msg)) {
                            const small = replyEl.querySelector('small');
                            if (small) {
                                small.style.backgroundColor = 'transparent';
                                small.style.padding = '0';
                                small.style.fontSize = '18px';
                                small.style.borderRadius = '0';
                                small.style.opacity = '1';
                            }
                        }
                    });
                }
            })
            .catch(err => console.error('Error loading replies:', err));
    });

////////////// GIF TOGGLE REPLY //////////////////
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
            const results = dropdown.querySelector('.gif-reply-results');

            if (!search || !results) return;

            setTimeout(() => search.focus(), 50);

            search.onkeyup = function(ev) {
                const query = ev.target.value.trim();
                if (query.length < 2) return;

                clearTimeout(gifTimeout);
                gifTimeout = setTimeout(async () => {
                    try {
                        const res = await fetch(`/gif/search/${encodeURIComponent(query)}`);
                        const gifs = await res.json();
                        results.innerHTML = '';

                        gifs.forEach(url => {
                            const img = document.createElement('img');
                            img.src = url;
                            img.style.width = '100%';
                            img.style.cursor = 'pointer';
                            img.addEventListener('click', () => {
                                if (gifUrlInput) gifUrlInput.value = url;
                                if (gifPreview) gifPreview.src = url;
                                if (gifPreviewContainer) {
                                    gifPreviewContainer.classList.remove('d-none');
                                    gifPreviewContainer.style.display = 'flex';
                                    gifPreviewContainer.style.justifyContent = 'center';
                                    gifPreviewContainer.style.alignItems = 'center';
                                }
                                dropdown.style.display = 'none';
                            });
                            results.appendChild(img);
                        });
                    } catch (err) {
                        console.error('Error fetching GIFs:', err);
                    }
                }, 300);
            };
        });
    });

    gifPreviewClear?.addEventListener('click', () => {
        if (gifPreview) gifPreview.src = '';
        if (gifPreviewContainer) gifPreviewContainer.classList.add('d-none');
        if (gifUrlInput) gifUrlInput.value = '';
    });

////////////// SUBMIT REPLY //////////////////
    replyForm?.addEventListener('submit', e => {
        e.preventDefault();
        const desktopTextarea = replyForm.querySelector('#reply-message-desktop');
        const mobileTextarea = replyForm.querySelector('#reply-message-mobile');
        const message = (desktopTextarea?.value.trim() || mobileTextarea?.value.trim());
        const commentId = replyForm.querySelector('#parent-comment-id')?.value;
        const gifUrl = gifUrlInput?.value || '';

        if (!commentId) return;

        fetch(`/comment/${commentId}/reply`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({message, gif_url: gifUrl})
        })
            .then(async res => {
                if (!res.ok) throw new Error(await res.text());
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    if (desktopTextarea) desktopTextarea.value = '';
                    if (mobileTextarea) mobileTextarea.value = '';
                    if (gifPreview) gifPreview.src = '';
                    if (gifPreviewContainer) {
                        gifPreviewContainer.classList.add('d-none');
                        gifPreviewContainer.style.display = 'none';
                    }
                    replyModal.querySelectorAll('.gif-reply-dropdown').forEach(dd => dd.style.display = 'none');

                    if (repliesContainer && data.html) {
                        repliesContainer.insertAdjacentHTML('afterbegin', data.html);

                        repliesContainer.querySelectorAll('[id^="reply-message-"]').forEach(replyEl => {
                            const msg = replyEl.dataset.originalMessage || '';
                            if (isOnlyEmojis(msg)) {
                                const small = replyEl.querySelector('small');
                                if (small) {
                                    small.style.backgroundColor = 'transparent';
                                    small.style.padding = '0';
                                    small.style.fontSize = '18px';
                                    small.style.borderRadius = '0';
                                    small.style.opacity = '1';
                                }
                            }
                        });
                    }

                    const counterSpan = document.getElementById(`reply-count-${data.commentId}`);
                    if (counterSpan) counterSpan.textContent = data.repliesCount;
                } else {
                    alert(data.error || 'Something went wrong.');
                }
            })
            .catch(err => console.error('Error submitting reply:', err));
    });

    document.addEventListener('click', e => {
        replyModal.querySelectorAll('.gif-reply-dropdown').forEach(dd => {
            if (!dd.contains(e.target) && !e.target.closest('.gif-reply-toggle-btn')) {
                dd.style.display = 'none';
            }
        });
    });
});