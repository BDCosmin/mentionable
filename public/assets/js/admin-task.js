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
        column.addEventListener("drop", e => {
            if (!draggedCard) return;
            column.appendChild(draggedCard);
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
        document.querySelectorAll(".edit-task-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            const modalEl = document.getElementById("editTaskModal");

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

            // Open modal
            const bootstrapModal = new bootstrap.Modal(modalEl);
            bootstrapModal.show();
        });
    });

        // Priority buttons
        document.querySelectorAll("#editTaskModal .priority-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            const hidden = document.getElementById("edit-task-priority");
            hidden.value = btn.dataset.value;

            document.querySelectorAll("#editTaskModal .priority-btn").forEach(p => p.classList.remove("active"));
            btn.classList.add("active");
        });
    });

        // Assignment buttons
        document.querySelectorAll("#editTaskModal .assign-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            const hidden = document.getElementById("edit-task-assignedTo");
            hidden.value = btn.dataset.value;

            document.querySelectorAll("#editTaskModal .assign-btn").forEach(a => a.classList.remove("active"));
            btn.classList.add("active");
        });
    });

        // Form submission via AJAX (optional)
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
                // Close modal safely
                const modalEl = document.getElementById("editTaskModal");
                let modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (!modalInstance) modalInstance = new bootstrap.Modal(modalEl);
                modalInstance.hide();

                // Cleanup any leftover backdrop
                document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());

                // Optional: update the task row in your table dynamically
                console.log("Task updated successfully");
            } else {
                console.error("Error updating task");
            }
        });
    });
});