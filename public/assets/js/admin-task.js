document.addEventListener("DOMContentLoaded", () => {

    // PRIORITY BUTTONS
    const priorityButtons = document.querySelectorAll('.priority-btn');
    const priorityInput = document.getElementById('task-priority');

    priorityButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            priorityInput.value = btn.dataset.value;
            priorityButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });

    // ASSIGN BUTTONS
    const assignButtons = document.querySelectorAll('.assign-btn');
    const assignInput = document.getElementById('task-assignedTo');

    assignButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            assignInput.value = btn.dataset.value;
            assignButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });

    // DRAG & DROP
    const csrfToken = document.getElementById("delete-task-token")?.value;
    let draggedCard = null;

    document.querySelectorAll(".draggable-card").forEach(card => {
        card.addEventListener("dragstart", e => {
            draggedCard = card;
            card.classList.add("dragging");
            e.dataTransfer.effectAllowed = "move";
        });

        card.addEventListener("dragend", () => {
            card.classList.remove("dragging");
            draggedCard = null;
        });
    });

    document.querySelectorAll(".task-column:not(.trash-column)").forEach(column => {
        column.addEventListener("dragover", e => e.preventDefault());

        column.addEventListener("drop", async e => {
            e.preventDefault();
            if (!draggedCard) return;

            const taskId = draggedCard.dataset.taskId;
            const assignedUser = column.dataset.user ?? '';

            column.appendChild(draggedCard);

            try {
                const response = await fetch(`/admin/task/${taskId}/reassign`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken
                    },
                    body: JSON.stringify({
                        user: assignedUser
                    })
                });

                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.error || 'Assign failed');
                }

            } catch (err) {
                console.error(err);
                alert("Assignment failed. Reloading.");
                location.reload();
            }

            draggedCard = null;
        });
    });


    const trashColumn = document.querySelector(".trash-column");
    if (trashColumn) {
        trashColumn.addEventListener("dragover", e => e.preventDefault());
        trashColumn.addEventListener("drop", () => {
            if (!draggedCard) return;

            const taskId = draggedCard.dataset.taskId;
            draggedCard.remove();
            draggedCard = null;

            fetch('/admin/delete-task/' + taskId, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `_token=${csrfToken}`
            })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) alert('Error deleting task!');
                });
        });
    }

    // EDIT TASK
    document.addEventListener("DOMContentLoaded", () => {
        const modalEl = document.getElementById("editTaskModal");
        const modalInstance = new bootstrap.Modal(modalEl, {backdrop: true});

        // EDIT TASK BUTTONS
        document.querySelectorAll(".edit-task-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                // Populate inputs
                document.getElementById("edit-task-title").value = btn.dataset.title || "";
                document.getElementById("edit-task-description").value = btn.dataset.description || "";
                document.getElementById("edit-task-priority").value = btn.dataset.priority || "LOW";
                document.getElementById("edit-task-assignedTo").value = btn.dataset.assigned || "";
                document.getElementById("task-id").value = btn.dataset.id;

                // Set active button states
                modalEl.querySelectorAll(".priority-btn").forEach(p => {
                    p.classList.toggle("active", p.dataset.value === btn.dataset.priority);
                });
                modalEl.querySelectorAll(".assign-btn").forEach(a => {
                    a.classList.toggle("active", a.dataset.value === btn.dataset.assigned);
                });

                // Show modal
                modalInstance.show();
            });
        });

        // Priority buttons
        modalEl.querySelectorAll(".priority-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                document.getElementById("edit-task-priority").value = btn.dataset.value;
                modalEl.querySelectorAll(".priority-btn").forEach(p => p.classList.remove("active"));
                btn.classList.add("active");
            });
        });

        // Assignment buttons
        modalEl.querySelectorAll(".assign-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                document.getElementById("edit-task-assignedTo").value = btn.dataset.value;
                modalEl.querySelectorAll(".assign-btn").forEach(a => a.classList.remove("active"));
                btn.classList.add("active");
            });
        });

        // Close modal safely (also handles the Close button)
        modalEl.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
            btn.addEventListener("click", () => {
                modalInstance.hide();
            });
        });

        // Form submission via AJAX
        document.getElementById("edit-task-form").addEventListener("submit", e => {
            e.preventDefault();
            const form = e.target;
            const taskId = document.getElementById("task-id").value;
            const formData = new FormData(form);

            fetch(`/admin/edit-task/${taskId}`, {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        modalInstance.hide(); // close modal safely

                        // Cleanup any leftover backdrop
                        document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());

                        console.log("Task updated successfully");
                    } else {
                        console.error("Error updating task");
                    }
                });
        });
    });
});
