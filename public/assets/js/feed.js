const voteHandler = (noteId, type) => {
    fetch(`/note/${noteId}/${type}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.querySelector(`#upvotes-${noteId}`).textContent = data.upvotes;
                document.querySelector(`#downvotes-${noteId}`).textContent = data.downvotes;
            }
        });
};

document.querySelectorAll('.upvote-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        voteHandler(id, 'upvote');
    });
});

document.querySelectorAll('.downvote-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        voteHandler(id, 'downvote');
    });
});

document.querySelectorAll('.ajax-comment-form').forEach(form => {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const noteId = this.dataset.noteId;
        const input = this.querySelector('.comment-input');
        const message = input.value.trim();
        if (!message) return;
        fetch(this.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: new URLSearchParams({ message: message })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const commentsContainer = form.closest('.note-comments').querySelector('.new-comments');
                    commentsContainer.insertAdjacentHTML('afterbegin', data.html);
                    input.value = '';
                    const countSpan = document.querySelector(`.toggle-comments[data-note-id="${noteId}"] .comment-count`);
                    if (countSpan) {
                        countSpan.textContent = parseInt(countSpan.textContent) + 1;
                    }
                }
            })
            .catch(err => console.error('Comment error:', err));
    });
});

document.addEventListener('click', function (e) {
    if (e.target.classList.contains('delete-comment-btn')) {
        e.preventDefault();
        const commentElement = e.target.closest('.d-flex.flex-row.mb-3');
        const url = e.target.getAttribute('href');
        if (!url || !commentElement) return;
        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    commentElement.remove();
                } else {
                    alert('A apărut o eroare la ștergerea comentariului.');
                }
            })
            .catch(err => console.error('Eroare ștergere:', err));
    }
});

document.querySelectorAll('.comment-upvote-btn').forEach(btn => {
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        const noteId = this.dataset.noteId;
        const commentId = this.dataset.commentId;
        const csrfToken = this.dataset.csrf;
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
                } else {
                    console.error(data.message || 'Eroare necunoscută');
                }
            })
            .catch(err => console.error('Fetch error:', err));
    });
});