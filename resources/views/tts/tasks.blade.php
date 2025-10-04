@extends('layouts.app')

@section('css')
<link href="{{ asset('css/tts-custom.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container-fluid py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-list-task me-2"></i>Your TTS Tasks</h2>
                    <a href="{{ url('/') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Create New Task
                    </a>
                </div>

                <!-- Task List -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="tasksTable">
                                <thead>
                                    <tr>
                                        <th>Task ID</th>
                                        <th>Input Text</th>
                                        <th>Voice ID</th>
                                        <th>Model</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Tasks will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <nav aria-label="Task pagination">
                            <ul class="pagination justify-content-center" id="pagination">
                                <!-- Pagination will be loaded here -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Task Details Modal -->
<div class="modal fade" id="taskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Task Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="taskDetails">
                <!-- Task details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" id="deleteTaskBtn" style="display: none;">Delete Task</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    let currentPage = 1;
    let currentTaskId = null;

    // Load tasks on page load
    loadTasks(1);

    // Load tasks function
    function loadTasks(page) {
        $.ajax({
            url: '{{ url("api/tts/tasks") }}',
            method: 'GET',
            data: { page: page, limit: 10 },
            headers: {
                'Authorization': 'Bearer {{ Helper::getSevenLabsApiKey() }}',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success && response.data) {
                    displayTasks(response.data.tasks || []);
                    displayPagination(response.data);
                } else {
                    $('#tasksTable tbody').html('<tr><td colspan="7" class="text-center">No tasks found</td></tr>');
                }
            },
            error: function(xhr) {
                console.error('Error loading tasks:', xhr);
                $('#tasksTable tbody').html('<tr><td colspan="7" class="text-center text-danger">Error loading tasks</td></tr>');
            }
        });
    }

    // Display tasks in table
    function displayTasks(tasks) {
        const tbody = $('#tasksTable tbody');
        tbody.empty();

        if (tasks.length === 0) {
            tbody.html('<tr><td colspan="7" class="text-center">No tasks found</td></tr>');
            return;
        }

        tasks.forEach(function(task) {
            const statusBadge = getStatusBadge(task.status);
            const createdDate = new Date(task.created_at).toLocaleString();
            const shortInput = task.input.length > 50 ? task.input.substring(0, 50) + '...' : task.input;

            const row = `
                <tr>
                    <td><code>${task.id.substring(0, 8)}...</code></td>
                    <td title="${task.input}">${shortInput}</td>
                    <td><code>${task.voice_id}</code></td>
                    <td>${task.model_id}</td>
                    <td>${statusBadge}</td>
                    <td>${createdDate}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="viewTask('${task.id}')">
                            <i class="bi bi-eye"></i>
                        </button>
                        ${task.status === 'completed' && task.result ? 
                            `<a href="${task.result}" class="btn btn-sm btn-outline-success" download>
                                <i class="bi bi-download"></i>
                            </a>` : ''
                        }
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteTask('${task.id}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // Get status badge HTML
    function getStatusBadge(status) {
        const badges = {
            'pending': '<span class="badge bg-warning">Pending</span>',
            'processing': '<span class="badge bg-info">Processing</span>',
            'completed': '<span class="badge bg-success">Completed</span>',
            'failed': '<span class="badge bg-danger">Failed</span>'
        };
        return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
    }

    // Display pagination
    function displayPagination(data) {
        const pagination = $('#pagination');
        pagination.empty();

        if (!data.total || data.total <= data.limit) {
            return;
        }

        const totalPages = Math.ceil(data.total / data.limit);
        const currentPage = parseInt(data.page);

        // Previous button
        if (currentPage > 1) {
            pagination.append(`<li class="page-item"><a class="page-link" href="#" onclick="loadTasks(${currentPage - 1})">Previous</a></li>`);
        }

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            const activeClass = i === currentPage ? 'active' : '';
            pagination.append(`<li class="page-item ${activeClass}"><a class="page-link" href="#" onclick="loadTasks(${i})">${i}</a></li>`);
        }

        // Next button
        if (currentPage < totalPages) {
            pagination.append(`<li class="page-item"><a class="page-link" href="#" onclick="loadTasks(${currentPage + 1})">Next</a></li>`);
        }
    }

    // View task details
    window.viewTask = function(taskId) {
        currentTaskId = taskId;
        
        $.ajax({
            url: '{{ url("api/tts/task") }}/' + taskId,
            method: 'GET',
            headers: {
                'Authorization': 'Bearer {{ Helper::getSevenLabsApiKey() }}',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success && response.task) {
                    displayTaskDetails(response.task);
                    $('#taskModal').modal('show');
                } else {
                    alert('Error loading task details: ' + (response.message || 'Unknown error'));
                }
            },
            error: function(xhr) {
                alert('Error loading task details: ' + (xhr.responseJSON?.message || 'Network error'));
            }
        });
    };

    // Display task details in modal
    function displayTaskDetails(task) {
        const details = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Task Information</h6>
                    <p><strong>ID:</strong> <code>${task.id}</code></p>
                    <p><strong>Status:</strong> ${getStatusBadge(task.status)}</p>
                    <p><strong>Created:</strong> ${new Date(task.created_at).toLocaleString()}</p>
                </div>
                <div class="col-md-6">
                    <h6>Voice Settings</h6>
                    <p><strong>Voice ID:</strong> <code>${task.voice_id}</code></p>
                    <p><strong>Model:</strong> ${task.model_id}</p>
                    <p><strong>Style:</strong> ${task.style}</p>
                    <p><strong>Speed:</strong> ${task.speed}</p>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Input Text</h6>
                    <div class="bg-light p-3 rounded">
                        ${task.input}
                    </div>
                </div>
            </div>
            ${task.result ? `
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Generated Audio</h6>
                        <audio controls class="w-100">
                            <source src="${task.result}" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                        <div class="mt-2">
                            <a href="${task.result}" class="btn btn-success" download>
                                <i class="bi bi-download me-2"></i>Download Audio
                            </a>
                        </div>
                    </div>
                </div>
            ` : ''}
            ${task.subtitle ? `
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Subtitle</h6>
                        <a href="${task.subtitle}" class="btn btn-outline-primary" download>
                            <i class="bi bi-file-text me-2"></i>Download Subtitle
                        </a>
                    </div>
                </div>
            ` : ''}
        `;

        $('#taskDetails').html(details);
        $('#deleteTaskBtn').show();
    }

    // Delete task
    window.deleteTask = function(taskId) {
        if (!confirm('Are you sure you want to delete this task?')) {
            return;
        }

        $.ajax({
            url: '{{ url("api/tts/task") }}/' + taskId,
            method: 'DELETE',
            headers: {
                'Authorization': 'Bearer {{ Helper::getSevenLabsApiKey() }}',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert('Task deleted successfully');
                    loadTasks(currentPage);
                    $('#taskModal').modal('hide');
                } else {
                    alert('Error deleting task: ' + (response.message || 'Unknown error'));
                }
            },
            error: function(xhr) {
                alert('Error deleting task: ' + (xhr.responseJSON?.message || 'Network error'));
            }
        });
    };

    // Delete task from modal
    $('#deleteTaskBtn').on('click', function() {
        if (currentTaskId) {
            deleteTask(currentTaskId);
        }
    });
});
</script>
@endsection
