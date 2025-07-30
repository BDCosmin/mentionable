document.addEventListener("DOMContentLoaded", function () {
    const forms = document.querySelectorAll('.ajax-ticket-reply-form');

    forms.forEach(form => {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const ticketId = form.dataset.ticketId;
            const input = form.querySelector('.ticket-reply-input');
            const message = input.value.trim();

            if (!message) return;

            const url = form.getAttribute('action');
            const formData = new FormData();
            formData.append('message', message);

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();
                if (data.success) {

                    form.closest('.d-flex').classList.add('d-none');

                    const replyContainer = document.createElement('div');

                    replyContainer.className = "d-flex flex-row mt-3";
                    replyContainer.innerHTML = `
                        <div class="m-0 p-0">
                            <div class="d-flex flex-row" style="height: 30px;">
                                <p class="text-white me-2" style="font-size: 18px;">Your reply:</p>
                            </div>
                            <div class="d-inline-flex">
                                <small style="font-size: 16px; color: white; background-color: #282b33; border-radius: 8px; padding: 8px;">${data.reply}</small>
                            </div>                   
                        </div>
                    `;

                    form.parentElement.insertAdjacentElement('afterend', replyContainer);

                    const markContainer = document.getElementById(`markResolvedContainer${ticketId}`);
                    if (markContainer) {
                        markContainer.classList.remove('d-none');
                    }
                } else {
                    alert(data.error || "Something went wrong.");
                }
            } catch (err) {
                console.error(err);
                alert("Could not send reply. Try again.");
            }
        });
    });
});