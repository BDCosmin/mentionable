////////////// DELETE REPLY //////////////////
document.addEventListener('click', function(e) {
    const deleteBtn = e.target.closest('.delete-reply-btn');
    if (!deleteBtn) return;

    e.preventDefault();

    if (deleteBtn.disabled) return;
    deleteBtn.disabled = true;

    const replyEl = deleteBtn.closest('[data-reply-id]');
    if (!replyEl) return console.error('Could not find reply element!');

    const csrfToken = deleteBtn.dataset.csrf;
    const url = deleteBtn.dataset.url;

    const formData = new FormData();
    formData.append('_token', csrfToken);

    fetch(url, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                deleteBtn.disabled = false;
                return alert(data.message || 'Failed to delete reply.');
            }

            document.querySelectorAll(`#reply-${replyEl.dataset.replyId}`).forEach(el => el.remove());

            const counterSpan = document.getElementById(`reply-count-${data.commentId}`);
            if (counterSpan) counterSpan.textContent = data.repliesCount;
        })
        .catch(err => {
            console.error(err);
            deleteBtn.disabled = false;
        });
});

////////////// REPLY UPVOTE //////////////////
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.reply-upvote-btn');
    if (!btn) return;

    e.preventDefault();

    const replyId = btn.dataset.replyId;

    fetch(`/comment/reply/${replyId}/toggle-upvote`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': btn.dataset.csrf
        }
    })
        .then(res => res.json())
        .then(data => {
            if (!data.success) return console.error('Upvote failed');

            const span = document.getElementById(`reply-upvotes-${data.replyId}`);
            span.textContent = data.upvotes;

            btn.classList.toggle('btn-light', data.userVoted);
            btn.classList.toggle('btn-outline-light', !data.userVoted);
        })
        .catch(err => console.error(err));
});

////////////// EDIT REPLY //////////////////
document.addEventListener('click', function(e) {
    const editBtn = e.target.closest('.edit-reply-btn');
    const saveBtn = e.target.closest('.save-reply-btn');
    const cancelBtn = e.target.closest('.cancel-reply-btn');

    function isOnlyEmojis(str) {
        const clean = str.trim();
        const emojiRegex = /^(?:[\u2700-\u27BF]|[\uE000-\uF8FF]|\u24C2|[\uD83C-\uDBFF\uDC00-\uDFFF])+$/;

        return emojiRegex.test(clean);
    }

    if (editBtn) {
        e.preventDefault();
        const replyId = editBtn.dataset.replyId;
        const messageEl = document.getElementById('reply-message-' + replyId);
        const gifEl = document.getElementById('reply-gif-' + replyId);
        const currentMessage = messageEl.dataset.originalMessage || '';
        const currentGif = gifEl.dataset.originalGif || '';

        messageEl.innerHTML = `
            <textarea id="edit-reply-text-${replyId}" class="form-control mb-1">${currentMessage}</textarea>
            <input type="hidden" id="edit-reply-gif-${replyId}" class="form-control mb-1" placeholder="GIF URL" value="${currentGif}">
            <button class="btn btn-sm btn-primary save-reply-btn" data-reply-id="${replyId}" data-csrf="${editBtn.dataset.csrf}">Save</button>
            <button class="btn btn-sm btn-secondary cancel-reply-btn" data-reply-id="${replyId}">Cancel</button>
        `;
    }

    // CANCEL
    if (cancelBtn) {
        e.preventDefault();
        const replyId = cancelBtn.dataset.replyId;
        const messageEl = document.getElementById('reply-message-' + replyId);
        const gifEl = document.getElementById('reply-gif-' + replyId);
        const onlyEmojis = isOnlyEmojis(messageEl.dataset.originalMessage);
        messageEl.innerHTML = `
                    <small class="mb-2"
                        style="                     
                            align-self:flex-start;
                            max-width:300px;
                            color:#404040;
                            ${onlyEmojis ? 'background-color:transparent; padding:0; font-size:18px;'
            : 'background-color:#fff; border-radius:8px; opacity:0.9; padding:5px; font-size:16px;'}
                        ">
                        ${messageEl.dataset.originalMessage}
                    </small>`;
        const originalGif = gifEl.dataset.originalGif;
        if (originalGif) {
            gifEl.innerHTML = `<img src="${originalGif}" alt="GIF" style="width:100%; max-width:200px; border-radius:8px;">`;
        } else {
            gifEl.innerHTML = '';
        }
    }

    // SAVE
    if (saveBtn) {
        e.preventDefault();
        const replyId = saveBtn.dataset.replyId;
        const message = document.getElementById('edit-reply-text-' + replyId).value;
        const gifUrl = document.getElementById('edit-reply-gif-' + replyId).value;
        const onlyEmojis = isOnlyEmojis(message);
        fetch(`/comment/${replyId}/reply/edit`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': saveBtn.dataset.csrf
            },
            body: JSON.stringify({ message: message, gifUrl: gifUrl })
        })
            .then(res => res.json())
            .then(data => {
                const messageEl = document.getElementById('reply-message-' + replyId);
                const gifEl = document.getElementById('reply-gif-' + replyId);
                if (data.success) {
                    messageEl.dataset.originalMessage = message;
                    messageEl.innerHTML = `
                    <small class="mb-2"
                        style="                     
                            align-self:flex-start;
                            max-width:300px;
                            color:#404040;
                            ${onlyEmojis ? 'background-color:transparent; padding:0; font-size:18px;'
                                    : 'background-color:#fff; border-radius:8px; opacity:0.9; padding:5px; font-size:16px;'}
                        ">
                        ${message}
                    </small>`;

                    gifEl.dataset.originalGif = gifUrl;
                    if (gifUrl) {
                        gifEl.innerHTML = `<img src="${gifUrl}" alt="GIF" style="width:100%; max-width:200px; border-radius:8px;">`;
                    } else {
                        gifEl.innerHTML = '';
                    }
                } else {
                    alert(data.error || 'Error updating reply');
                }
            });
    }
});