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
            if (!data.success) return;

            document.querySelector(`#upvotes-${noteId}`).textContent = data.upvotes;
            document.querySelector(`#downvotes-${noteId}`).textContent = data.downvotes;

            const upBtn = document.querySelector(`.upvote-btn[data-id="${noteId}"]`);
            const downBtn = document.querySelector(`.downvote-btn[data-id="${noteId}"]`);

            if (type === 'upvote') {
                if (upBtn.classList.contains('btn-light')) {
                    upBtn.classList.remove('btn-light');
                    upBtn.classList.add('btn-outline-light');
                } else {
                    upBtn.classList.add('btn-light');
                    upBtn.classList.remove('btn-outline-light');
                    downBtn.classList.remove('btn-light');
                    downBtn.classList.add('btn-outline-light');
                }
            } else if (type === 'downvote') {
                if (downBtn.classList.contains('btn-light')) {
                    downBtn.classList.remove('btn-light');
                    downBtn.classList.add('btn-outline-light');
                } else {
                    downBtn.classList.add('btn-light');
                    downBtn.classList.remove('btn-outline-light');
                    upBtn.classList.remove('btn-light');
                    upBtn.classList.add('btn-outline-light');
                }
            }
        })
        .catch(err => console.error('Eroare la vot:', err));
};

document.addEventListener('click', (e) => {
    const upBtn = e.target.closest('.upvote-btn');
    const downBtn = e.target.closest('.downvote-btn');

    if (upBtn) {
        voteHandler(upBtn.dataset.id, 'upvote');
    } else if (downBtn) {
        voteHandler(downBtn.dataset.id, 'downvote');
    }
});


document.addEventListener('submit', function (e) {
    if (e.target.matches('.ajax-comment-form')) {
        e.preventDefault();
        const form = e.target;
        const noteId = form.dataset.noteId;
        const input = form.querySelector('.comment-input');
        const message = input.value.trim();
        if (!message) return;

        fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: new URLSearchParams({ message })
        })
            .then(response => {
                if (!response.ok) return response.json().then(errorData => { throw errorData; });
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const commentsContainer = form.closest('.note-comments').querySelector('.new-comments');
                    commentsContainer.insertAdjacentHTML('afterbegin', data.html);
                    input.value = '';

                    const countSpan = document.querySelector(`.toggle-comments[data-note-id="${noteId}"] .comment-count`);
                    if (countSpan) countSpan.textContent = (parseInt(countSpan.textContent) || 0) + 1;
                }
            })
            .catch(error => {
                const errorMessage = error?.message || 'Something went wrong.';
                const errorDiv = form.querySelector('.comment-error');
                if (errorDiv) {
                    const span = errorDiv.querySelector('.error-message');
                    if (span) span.textContent = errorMessage;
                    errorDiv.classList.remove('d-none');
                    errorDiv.classList.add('show');
                }
            });
    }
});

document.addEventListener('close.bs.alert', function (e) {
    const alertDiv = e.target.closest('.comment-error');
    if (!alertDiv) return;

    e.preventDefault();
    alertDiv.classList.add('d-none');
    alertDiv.classList.remove('show');
});

function handleDeleteComment(deleteBtn, event) {
    const commentElement = deleteBtn.closest('.d-flex.flex-row.mb-3');
    if (!commentElement) return;

    const url = deleteBtn.getAttribute('href');
    if (!url) return;

    const noteContainer = commentElement.closest('[data-note-id]');
    if (!noteContainer) return;

    const noteId = noteContainer.dataset.noteId;

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
                const countSpan = document.querySelector(`.toggle-comments[data-note-id="${noteId}"] .comment-count`);
                if (countSpan) {
                    const current = parseInt(countSpan.textContent || '0');
                    countSpan.textContent = Math.max(0, current - 1).toString();
                }
            } else {
                alert('An error has occurred while deleting the comment.');
            }
        })
        .catch(err => console.error('Deleting error:', err));
}

document.addEventListener('click', function (e) {
    const deleteBtn = e.target.closest('.delete-comment-btn');
    if (!deleteBtn) return;
    e.preventDefault();
    handleDeleteComment(deleteBtn, e);
});

document.addEventListener('click', function (e) {
    if (e.target.closest('.comment-upvote-btn')) {
        e.preventDefault();
        const btn = e.target.closest('.comment-upvote-btn');
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
            .catch(err => console.error('Fetch error:', err));
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const input = document.getElementById('searchNametag');
    const suggestionsBox = document.getElementById('suggestions');
    const clearBtn = document.getElementById('clearDesktopInput');

    if (!input || !suggestionsBox) return;

    input.addEventListener('input', function () {
        const query = this.value.trim();

        if (query.length >= 2) {
            fetch(`/nametag-suggestions?query=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    suggestionsBox.innerHTML = '';
                    if (data.length === 0) {
                        suggestionsBox.style.display = 'none';
                        return;
                    }
                    data.forEach(nametag => {
                        const item = document.createElement('a');
                        item.classList.add('list-group-item', 'list-group-item-action');
                        item.textContent = nametag;
                        item.href = `/search?nametag=${encodeURIComponent(nametag)}`;
                        suggestionsBox.appendChild(item);
                    });
                    suggestionsBox.style.display = 'block';
                })
                .catch(() => {
                    suggestionsBox.innerHTML = '';
                    suggestionsBox.style.display = 'none';
                });
        } else {
            suggestionsBox.innerHTML = '';
            suggestionsBox.style.display = 'none';
        }
    });

    clearBtn.addEventListener('click', function () {
        input.value = '';
        suggestionsBox.innerHTML = '';
        suggestionsBox.style.display = 'none';
        input.focus();
    });

    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.style.display = 'none';
        }
    });
});

const mobileInput = document.getElementById('mobileSearchNametag');
const mobileSuggestions = document.getElementById('mobileSuggestions');

mobileInput.addEventListener('input', function () {
    const query = this.value.trim();

    if (query.length >= 2) {
        fetch(`/nametag-suggestions?query=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                mobileSuggestions.innerHTML = '';
                if (data.length === 0) {
                    mobileSuggestions.style.display = 'none';
                    return;
                }
                data.forEach(nametag => {
                    const item = document.createElement('a');
                    item.classList.add('list-group-item', 'list-group-item-action', 'pt-2', 'ps-2', 'pb-2');
                    item.textContent = nametag;
                    item.href = `/search?nametag=${encodeURIComponent(nametag)}`;
                    mobileSuggestions.appendChild(item);
                });
                mobileSuggestions.style.display = 'block';
            });
    } else {
        mobileSuggestions.innerHTML = '';
        mobileSuggestions.style.display = 'none';
    }
});




