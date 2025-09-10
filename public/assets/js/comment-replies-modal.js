document.addEventListener('DOMContentLoaded', function() {
    const replyModal = document.getElementById('newCommentReplyModal');
    replyModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;

        document.getElementById('reply-username').textContent = '';
        document.getElementById('parent-comment-id').value = '';
        document.getElementById('parent-comment-message').textContent = '';

        const parentMessageEl = document.getElementById('parent-comment-message');
        const parentMessageWrapper = parentMessageEl.closest('div');
        parentMessageWrapper.style.display = 'none';

        const parentImgEl = document.getElementById('parent-comment-img');
        parentImgEl.style.display = 'none';

        const username = button.getAttribute('data-username');
        const comment = button.getAttribute('data-comment-id');
        const commentMessage = button.getAttribute('data-comment-message');
        const commentImg = button.getAttribute('data-comment-img');

        document.getElementById('reply-username').textContent = username;
        document.getElementById('parent-comment-id').value = comment;

        if (commentMessage && commentMessage.trim() !== "") {
            parentMessageEl.textContent = commentMessage;
            parentMessageEl.style.display = 'inline-block';
            parentMessageEl.style.backgroundColor = '#ffffff';
            parentMessageEl.style.opacity = '0.9';
        } else {
            parentMessageEl.textContent = '';
            parentMessageEl.style.display = 'none';
            parentMessageEl.style.backgroundColor = 'transparent';
            parentMessageEl.style.opacity = '0';
        }

        if (commentImg) {
            parentImgEl.src = commentImg.startsWith('http')
                ? commentImg
                : window.location.origin + commentImg;
            parentImgEl.style.display = 'block';
        } else {
            parentImgEl.removeAttribute('src');
            parentImgEl.style.display = 'none';
        }


        document.getElementById('reply-form').action = `/comment/${comment}/reply`;

        fetch(`/comment/${comment}/replies`)
            .then(res => res.text())
            .then(html => {
                document.getElementById('replies-container').innerHTML = html;
            });
    });
});