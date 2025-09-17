// Submit reply
document.getElementById('reply-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(res => res.json())
        .then(data => {
            if (!data.success) return alert(data.message || 'Something went wrong.');

            document.getElementById('replies-container').insertAdjacentHTML('afterbegin', data.html);

            const commentId = form.querySelector('#parent-comment-id').value;
            const commentRepliesContainer = document.querySelector(`#comment-${commentId}-replies`);
            if (commentRepliesContainer) commentRepliesContainer.insertAdjacentHTML('afterbegin', data.html);

            const counterSpan = document.getElementById(`reply-count-${data.commentId}`);
            if (counterSpan) counterSpan.textContent = data.repliesCount;

            form.querySelector('textarea').value = '';
        })
        .catch(err => console.error(err));
});

// Delete reply (delegare pe document)
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






