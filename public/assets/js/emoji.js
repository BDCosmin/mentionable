// emoji listing
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.emoji-toggle-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();

            const form = btn.closest('form');
            if (!form) return;

            const emojiListDiv = form.querySelector('.emoji-list');
            if (!emojiListDiv) return;

            emojiListDiv.style.display = (emojiListDiv.style.display === 'flex') ? 'none' : 'flex';
            emojiListDiv.style.flexWrap = 'wrap';
            emojiListDiv.style.gap = '5px';

            if (!emojiListDiv.dataset.loaded) {
                fetch('/api/emojis')
                    .then(res => res.json())
                    .then(emojis => {
                        emojis.slice(0, 100).forEach(emoji => {
                            const eBtn = document.createElement('button');
                            eBtn.type = 'button';
                            eBtn.textContent = emoji.character;
                            eBtn.style.fontSize = '20px';
                            eBtn.style.background = 'transparent';
                            eBtn.style.border = 'none';
                            eBtn.style.cursor = 'pointer';
                            eBtn.addEventListener('click', () => {
                                const input = form.querySelector('.comment-input');
                                if (input) input.value += emoji.character;
                                input.focus();
                            });
                            emojiListDiv.appendChild(eBtn);
                        });
                        emojiListDiv.dataset.loaded = 'true';
                    })
                    .catch(err => console.error('Emoji fetch error:', err));
            }
        });
    });

    document.addEventListener('click', (e) => {
        document.querySelectorAll('.emoji-list').forEach(list => {
            const form = list.closest('form');
            const btn = form.querySelector('.emoji-toggle-btn');
            if (!list.contains(e.target) && !btn.contains(e.target)) {
                list.style.display = 'none';
            }
        });
    });
});
