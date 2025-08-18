document.addEventListener('DOMContentLoaded', function() {
    // Toggle task completion
    document.querySelectorAll('.task-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const taskId = this.dataset.taskId;
            const isCompleted = this.checked;
            
            fetch(`/ticket/task/${taskId}/toggle`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ completed: isCompleted })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    const taskItem = this.closest('.list-group-item');
                    const taskText = taskItem.querySelector('.flex-grow-1');
                    
                    if (isCompleted) {
                        taskText.classList.add('text-muted', 'text-decoration-line-through');
                        // Add completed time if not present
                        if (!taskText.querySelector('.completed-time')) {
                            const timeElement = document.createElement('div');
                            timeElement.className = 'completed-time text-muted small';
                            timeElement.textContent = `• Completada el ${new Date().toLocaleString('es-AR')}`;
                            taskText.appendChild(timeElement);
                        }
                    } else {
                        taskText.classList.remove('text-muted', 'text-decoration-line-through');
                        // Remove completed time if present
                        const completedTime = taskText.querySelector('.completed-time');
                        if (completedTime) {
                            completedTime.remove();
                        }
                    }
                    
                    // Update pending tasks count
                    updatePendingTasksCount();
                    
                    // Show toast notification
                    showToast(isCompleted ? 'Tarea marcada como completada' : 'Tarea marcada como pendiente');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.checked = !isCompleted; // Revert checkbox on error
                showToast('Error al actualizar la tarea', 'danger');
            });
        });
    });
    
    // Delete task
    let taskToDelete = null;
    const deleteButtons = document.querySelectorAll('.delete-task');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteTaskModal'));
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            taskToDelete = this.dataset.taskId;
            deleteModal.show();
        });
    });
    
    const confirmDeleteBtn = document.getElementById('confirmDeleteTask');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            if (!taskToDelete) return;
            
            fetch(`/ticket/task/${taskToDelete}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the task from the UI
                    const taskElement = document.querySelector(`.delete-task[data-task-id="${taskToDelete}"]`);
                    if (taskElement) {
                        taskElement.closest('.list-group-item').remove();
                        
                        // Update pending tasks count
                        updatePendingTasksCount();
                        
                        // Show success message
                        showToast('Tarea eliminada correctamente');
                        
                        // If no tasks left, show empty state
                        const taskList = document.querySelector('.list-group');
                        if (taskList && taskList.children.length === 0) {
                            taskList.innerHTML = `
                                <div class="text-center py-3 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No hay tareas pendientes
                                </div>
                            `;
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error al eliminar la tarea', 'danger');
            })
            .finally(() => {
                deleteModal.hide();
                taskToDelete = null;
            });
        });
    }
    
    // Update pending tasks count in the badge
    function updatePendingTasksCount() {
        const pendingTasks = document.querySelectorAll('.task-checkbox:not(:checked)').length;
        const badge = document.querySelector('.badge.bg-warning, .badge.bg-success');
        if (badge) {
            badge.textContent = `${pendingTasks} pendiente(s)`;
            badge.className = `badge bg-${pendingTasks > 0 ? 'warning' : 'success'}`;
        }
    }
    
    // Show toast notification
    function showToast(message, type = 'success') {
        const toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) return;
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // Remove toast after it's hidden
        toast.addEventListener('hidden.bs.toast', function() {
            toast.remove();
        });
    }
});
