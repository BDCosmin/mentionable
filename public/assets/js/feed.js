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

document.addEventListener('click', function (e) {
    const link = e.target.closest('.see-all-comments');
    if (!link) return;
    e.preventDefault();

    const noteId = link.dataset.noteId;
    const url = `/note/${noteId}/comments`;
    const commentsContainer = document.getElementById(`comments-list-${noteId}`);

    fetch(url)
        .then(response => response.json())
        .then(data => {
            commentsContainer.innerHTML = '';
            data.comments.forEach(comment => {
                const html = `
                <div class="d-flex flex-row mb-3" data-note-id="${noteId}">
                    <a class="nav-link me-2" href="#">
                        <img class="img-xs rounded-circle" src="/uploads/avatars/${comment.user.avatar}" alt="avatarComment" onerror="showDefaultIcon(this)">
                    </a>
                    <div class="ms-2 p-0">
                        <div class="d-flex flex-row" style="height: 30px;">
                            <p class="text-white me-2" style="font-size: 18px;">@${comment.user.nametag}</p>
                            ${comment.isEdited ? `<i class="fa fa-clock-o text-gray me-1" style="margin-top: 6px"></i>` : `<p class="text-gray mt-1 me-2">•</p>`}
                            <p class="text-gray mt-1">${comment.humanTime}</p>
                        </div>
                        <div class="d-inline-flex">
                            <small style="font-size: 16px; color: #404040; background-color: #ffffff; border-radius: 8px; opacity: 0.9; padding: 5px;">${comment.message}</small>
                            <button class="btn btn-sm ms-2 d-flex align-items-center border-0 comment-upvote-btn ${commentVotesMap[comment.id] === 'upvote' ? 'btn-light' : 'btn-outline-light'}"
                                    type="button"
                                    data-note-id="${comment.note.id}"
                                    data-comment-id="${comment.id}"
                                    data-csrf="${csrfToken}">
                                <i class='bx bx-arrow-up' style="font-size: 20px;"></i>
                                <span id="comment-upvotes-${comment.id}" class="ms-1">${comment.upVote || 0}</span>
                            </button>
                        </div>
                    </div>
                    <div class="dropdown ms-auto me-1 d-inline-flex justify-content-end">
                        <a href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="height: 30px;">
                            <i class='bx bx-dots-horizontal-rounded' style='color:#ffffff'></i>
                        </a>
                        <ul class="dropdown-menu m-0">
                            ${currentUserNametag === comment.user.nametag
                                ? `
                                <li><a class="dropdown-item" href="/note/comment/update/${comment.id}?noteId=${comment.note.id}">Edit...</a></li>
                                <li><a class="dropdown-item delete-comment-btn" href="/note/comment/delete/${comment.id}?noteId=${comment.note.id}">Delete comment</a></li>
                                `
                                : `
                                <li><a class="dropdown-item" href="/note/comment/report/${comment.id}?noteId=${comment.note.id}">Report</a></li>
                                `}
                        </ul>
                    </div>
                </div>`;
                commentsContainer.insertAdjacentHTML('beforeend', html);
            });
            link.style.display = 'none';
        })
        .catch(error => console.error('Error loading comments:', error));
});
