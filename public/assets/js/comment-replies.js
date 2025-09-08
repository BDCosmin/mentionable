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

                form.querySelector('textarea').value = '';
            } else {
                alert(data.message || 'Something went wrong.');
            }
        })
        .catch(err => console.error(err));
});
