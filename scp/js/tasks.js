/**
 * Task Manager JavaScript
 * Phase 1: Basic task operations
 */

var TaskManager = {
    /**
     * Update task status via AJAX
     */
    updateStatus: function(taskId, status, callback) {
        $.ajax({
            url: 'dispatch.php',
            data: { api: 'tasks', f: 'updateStatus', id: taskId, status: status },
            dataType: 'json',
            success: function(data) {
                if (callback) callback(true, data);
            },
            error: function() {
                if (callback) callback(false);
            }
        });
    },

    /**
     * Move task to a different list
     */
    moveTask: function(taskId, listId, position, callback) {
        $.ajax({
            url: 'dispatch.php',
            data: { api: 'tasks', f: 'moveTask', task_id: taskId, list_id: listId, position: position },
            dataType: 'json',
            success: function(data) {
                if (callback) callback(true, data);
            },
            error: function() {
                if (callback) callback(false);
            }
        });
    },

    /**
     * Quick create task
     */
    quickCreate: function(data, callback) {
        $.ajax({
            url: 'dispatch.php',
            data: $.extend({ api: 'tasks', f: 'quickCreate' }, data),
            dataType: 'json',
            success: function(resp) {
                if (callback) callback(true, resp);
            },
            error: function() {
                if (callback) callback(false);
            }
        });
    },

    /**
     * Search tasks
     */
    search: function(query, callback) {
        $.ajax({
            url: 'dispatch.php',
            data: { api: 'tasks', f: 'search', q: query },
            dataType: 'json',
            success: function(results) {
                if (callback) callback(results);
            }
        });
    },

    /**
     * Preview task in modal
     */
    preview: function(taskId) {
        $.ajax({
            url: 'dispatch.php',
            data: { api: 'tasks', f: 'preview', id: taskId },
            dataType: 'html',
            success: function(html) {
                $('#taskPreviewBody').html(html);
                $('#taskPreviewModal').modal('show');
            },
            error: function() {
                alert('Ошибка загрузки задачи');
            }
        });
    }
};

// Select all / reset all helpers
function select_all(formId) {
    $('#' + formId + ' input[type="checkbox"]').prop('checked', true);
    return false;
}

function reset_all(formId) {
    $('#' + formId + ' input[type="checkbox"]').prop('checked', false);
    return false;
}

// Loading state helper
function setLoading($btn) {
    $btn.addClass('loading').prop('disabled', true);
    var origText = $btn.html();
    $btn.data('orig-text', origText);
    $btn.html('<i class="fa fa-spinner fa-spin"></i>');
    return origText;
}
function clearLoading($btn) {
    $btn.removeClass('loading').prop('disabled', false);
    var origText = $btn.data('orig-text');
    if (origText) $btn.html(origText);
}
