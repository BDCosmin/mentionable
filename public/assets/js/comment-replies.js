document.getElementById('reply-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const actionUrl = form.action;
    const formData = new FormData(form);

    fetch(actionUrl, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Adaugă reply-ul nou instant în modal
                document.getElementById('replies-container').insertAdjacentHTML('afterbegin', data.html);

                // Adaugă reply-ul și în pagina principală, dacă există containerul
                const commentId = form.querySelector('#parent-comment-id').value;
                const commentRepliesContainer = document.querySelector(`#comment-${commentId}-replies`);
                if (commentRepliesContainer) {
                    commentRepliesContainer.insertAdjacentHTML('afterbegin', data.html);
                }

                const counterSpan = document.getElementById(`reply-count-${data.commentId}`);
                if (counterSpan) {
                    counterSpan.textContent = data.repliesCount;
                }

                form.querySelector('textarea').value = '';
            } else {
                alert(data.message || 'Something went wrong.');
            }
        })
        .catch(err => console.error(err));
});

document.addEventListener('click', function(e) {
    const deleteBtn = e.target.closest('.delete-reply-btn');
    if (!deleteBtn) return;

    e.preventDefault();

    const replyEl = deleteBtn.closest('[data-reply-id]');
    const replyId = replyEl.dataset.replyId;
    const commentId = replyEl.dataset.commentId;
    const csrfToken = deleteBtn.dataset.csrf; // token corect generat în Twig

    fetch(`/comment/reply/${replyId}/delete`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        }
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Șterge reply-ul din DOM
                replyEl.remove();

                // Actualizează counter-ul
                const counterSpan = document.getElementById(`reply-count-${data.commentId}`);
                if (counterSpan) counterSpan.textContent = data.repliesCount;
            } else {
                alert(data.message || 'Failed to delete reply.');
            }
        })
        .catch(err => console.error(err));
});



