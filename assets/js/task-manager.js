document.addEventListener('DOMContentLoaded', function() {
    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    // Mark task as complete
    document.querySelectorAll('.mark-complete').forEach(btn => {
        btn.addEventListener('click', async () => {
            const taskId = btn.dataset.taskId;
            const taskItem = btn.closest('.task-item');
            const taskTitle = taskItem.querySelector('.flex-grow-1 span');
            
            try {
                const response = await fetch(`/task/${taskId}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ completed: true })
                });
                
                if (!response.ok) {
                    throw new Error('Error al actualizar la tarea');
                }
                
                const data = await response.json();
                
                if (data.ok) {
                    // Update UI
                    taskTitle.classList.add('text-muted', 'text-decoration-line-through');
                    
                    // Update completion time if available
                    const timeContainer = taskItem.querySelector('.small');
                    if (timeContainer) {
                        const completionTime = data.completedAt || new Date().toLocaleString('es-AR');
                        timeContainer.innerHTML += ` • Completada el ${completionTime}`;
                    }
                    
                    // Remove the complete button
                    btn.remove();
                    
                    // Show success message
                    showToast('Tarea marcada como completada');
                    
                    // Update pending tasks count
                    updatePendingTasksCount();
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error al actualizar la tarea', 'danger');
            }
        });
    });
    
    // Delete task
    document.querySelectorAll('.delete-task').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            
            if (!confirm('¿Estás seguro de que deseas eliminar esta tarea?')) {
                return;
            }
            
            const taskId = btn.dataset.taskId;
            const taskItem = btn.closest('.task-item');
            
            try {
                const response = await fetch(`/task/${taskId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Error al eliminar la tarea');
                }
                
                // Remove task from UI
                taskItem.remove();
                
                // Show success message
                showToast('Tarea eliminada correctamente');
                
                // Update pending tasks count
                updatePendingTasksCount();
                
            } catch (error) {
                console.error('Error:', error);
                showToast('Error al eliminar la tarea', 'danger');
            }
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
            
            fetch(`/task/${taskToDelete}`, {
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
