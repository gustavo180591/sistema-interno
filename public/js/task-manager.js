// Function to show toast notifications
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) return;

    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
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
    
    // Initialize and show the toast
    const bsToast = new bootstrap.Toast(toast, { autohide: true, delay: 3000 });
    bsToast.show();
    
    // Remove the toast after it's hidden
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Function to update the pending tasks count
function updatePendingTasksCount() {
    const pendingCount = document.querySelectorAll('.task-item:not(.completed)').length;
    const countElement = document.getElementById('pending-tasks-count');
    if (countElement) {
        countElement.textContent = `${pendingCount} pendiente(s)`;
        countElement.className = `badge bg-${pendingCount > 0 ? 'warning' : 'success'}`;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    // Mark task as complete
    document.addEventListener('click', async function(e) {
        // Complete task button
        if (e.target.closest('.mark-complete')) {
            e.preventDefault();
            const button = e.target.closest('.mark-complete');
            const taskItem = button.closest('.task-item');
            const taskId = taskItem.dataset.taskId;
            const taskTitle = taskItem.querySelector('span');
            
            try {
                // Get CSRF token from meta tag
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                if (!csrfToken) {
                    console.error('CSRF token not found in meta tag');
                    throw new Error('CSRF token not found');
                }
                
                console.log('CSRF Token:', csrfToken);
                
                const response = await fetch(`/task/${taskId}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        _token: csrfToken
                    }),
                    credentials: 'same-origin'
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
                    button.remove();
                    
                    // Show success message
                    showToast('Tarea marcada como completada');
                    
                    // Update pending tasks count
                    updatePendingTasksCount();
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error al actualizar la tarea', 'danger');
            }
        }
        
        // Delete task button
        else if (e.target.closest('.delete-task')) {
            e.preventDefault();
            const button = e.target.closest('.delete-task');
            const taskItem = button.closest('.task-item');
            const taskId = taskItem.dataset.taskId;
            
            if (!confirm('¿Estás seguro de que deseas eliminar esta tarea?')) {
                return;
            }
            
            try {
                // Get CSRF token from meta tag
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                if (!csrfToken) {
                    console.error('CSRF token not found in meta tag');
                    throw new Error('CSRF token not found');
                }
                
                console.log('Deleting task with CSRF Token:', csrfToken);
                
                const response = await fetch(`/task/${taskId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        _token: csrfToken,
                        _method: 'DELETE'
                    }),
                    credentials: 'same-origin'
                });
                
                if (!response.ok) {
                    throw new Error('Error al eliminar la tarea');
                }
                
                // Remove task from UI
                taskItem.remove();
                
                // Update pending tasks count
                updatePendingTasksCount();
                
                // Show success message
                showToast('Tarea eliminada correctamente');
                
            } catch (error) {
                console.error('Error:', error);
                showToast('Error al eliminar la tarea', 'danger');
            }
        }
    });
    
    // Initialize pending tasks count
    updatePendingTasksCount();
});
