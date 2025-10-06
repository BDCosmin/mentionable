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
    const removeGifBtn = e.target.closest('.remove-gif-btn');
    const gifToggleBtn = e.target.closest('.gif-edit-toggle-btn');
    const gifItem = e.target.closest('.gif-item');

    // ======== UTIL ========
    function isOnlyEmojis(str) {
        const clean = str.trim();
        const emojiRegex = /^(?:[\u2700-\u27BF]|[\uE000-\uF8FF]|\u24C2|[\uD83C-\uDBFF\uDC00-\uDFFF])+$/;
        return emojiRegex.test(clean);
    }

    // ======== EDIT MODE ========
    if (editBtn) {
        e.preventDefault();
        const replyId = editBtn.dataset.replyId;
        const messageEl = document.getElementById('reply-message-' + replyId);
        const gifEl = document.getElementById('reply-gif-' + replyId);

        const currentMessage = messageEl.dataset.originalMessage || '';
        const currentGif = gifEl.dataset.originalGif || '';

        messageEl.innerHTML = `
            <div class="d-flex align-items-center mb-2">
                <textarea id="edit-reply-text-${replyId}" class="form-control mb-2">${currentMessage}</textarea>
                
                <button type="button"
                        class="btn btn-light btn-sm text-white emoji-edit-toggle-btn mb-2"
                        data-reply-id="${replyId}"
                        style="background-color:#252c35; font-weight:bold; height: 2.875rem; font-size: 16px; border-radius: 0;">
                    <i class='bx bx-happy-beaming' style='color:#ffffff; font-size: 24px'></i>
                </button>
                
                <button type="button"
                        class="btn btn-light btn-sm text-white gif-edit-toggle-btn mb-2"
                        data-reply-id="${replyId}"
                        style="background-color:#2A3038; font-weight:bold; height: 2.875rem; font-size: 16px; border-radius: 0;">
                    GIF
                </button>
            </div>

            <div class="gif-edit-preview-container mb-2"
                 id="gif-edit-preview-${replyId}"
                 style="text-align:center;">
                ${currentGif
            ? `<img src="${currentGif}" style="max-width:200px; border-radius:8px;">
               <button type="button" class="btn btn-sm btn-danger remove-gif-btn"
                       data-reply-id="${replyId}" style="margin-top:5px;">Remove GIF</button>`
            : `<p class="text-muted small">No GIF selected</p>`}
            </div>

            <input type="hidden" id="edit-reply-gif-${replyId}" value="${currentGif}">

            <button class="btn btn-sm btn-primary save-reply-btn" data-reply-id="${replyId}" data-csrf="${editBtn.dataset.csrf}">Save</button>
            <button class="btn btn-sm btn-secondary cancel-reply-btn" data-reply-id="${replyId}">Cancel</button>
        `;
    }

    // ======== OPEN GIF PICKER ========
    if (gifToggleBtn) {
        e.preventDefault();
        const replyId = gifToggleBtn.dataset.replyId;
        const isMobile = window.innerWidth < 768;
        const gifDropdown = document.querySelector(isMobile ? '#gif-dropdown-mobile' : '#gif-dropdown-desktop');
        if (!gifDropdown) return;

        gifDropdown.dataset.targetReplyId = replyId;
        const isVisible = gifDropdown.style.display === 'block';
        gifDropdown.style.display = isVisible ? 'none' : 'block';
        gifDropdown.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // ======== REMOVE GIF ========
    if (removeGifBtn) {
        e.preventDefault();
        const replyId = removeGifBtn.dataset.replyId;
        const previewContainer = document.getElementById(`gif-edit-preview-${replyId}`);
        previewContainer.innerHTML = `<p class="text-muted small">No GIF selected</p>`;
        document.getElementById(`edit-reply-gif-${replyId}`).value = '';
    }

    // ======== CANCEL EDIT ========
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
            </small>
        `;

        const originalGif = gifEl.dataset.originalGif;
        gifEl.innerHTML = originalGif
            ? `<img src="${originalGif}" alt="GIF" style="width:100%; max-width:200px; border-radius:8px;">`
            : '';
    }

    // ======== SAVE EDIT ========
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
                    </small>
                `;

                    gifEl.dataset.originalGif = gifUrl;
                    gifEl.innerHTML = gifUrl
                        ? `<img src="${gifUrl}" alt="GIF" style="width:100%; max-width:200px; border-radius:8px;">`
                        : '';
                } else {
                    alert(data.error || 'Error updating reply');
                }
            })
            .catch(() => alert('Error updating reply'));
    }

    // ======== SELECT GIF FROM DROPDOWN ========
    if (gifItem) {
        e.preventDefault();
        const dropdownDesktop = document.querySelector('#gif-dropdown-desktop');
        const dropdownMobile = document.querySelector('#gif-dropdown-mobile');
        const dropdown = dropdownDesktop.style.display === 'block' ? dropdownDesktop : dropdownMobile;

        const targetReplyId = dropdown.dataset.targetReplyId;
        if (!targetReplyId) return;

        const gifUrl = gifItem.dataset.gifUrl;
        const previewContainer = document.getElementById(`gif-edit-preview-${targetReplyId}`);
        const hiddenInput = document.getElementById(`edit-reply-gif-${targetReplyId}`);

        if (previewContainer && hiddenInput) {
            previewContainer.innerHTML = `
                <img src="${gifUrl}" style="max-width:200px; border-radius:8px;">
                <button type="button" class="btn btn-sm btn-danger remove-gif-btn"
                        data-reply-id="${targetReplyId}" style="margin-top:5px;">Remove GIF</button>
            `;
            hiddenInput.value = gifUrl;
        }

        dropdown.style.display = 'none';
        dropdown.dataset.targetReplyId = '';
    }
});




