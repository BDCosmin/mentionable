 /////////// NOTE VOTES /////////////
    const voteHandler = (noteId, type) => {
        const csrfVote = document.querySelector('meta[name="csrf-token-vote"]').content;

        fetch(`/note/${noteId}/${type}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfVote
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




