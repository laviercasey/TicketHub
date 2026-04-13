/**
 * Task Kanban Board JavaScript
 * Phase 3: Drag-and-drop kanban with Sortable.js
 */

var TaskKanban = {

    sortables: [],

    /**
     * Initialize kanban board - create Sortable instances for each column
     */
    init: function() {
        var columns = document.querySelectorAll('.kanban-column-body');
        for (var i = 0; i < columns.length; i++) {
            var sortable = new Sortable(columns[i], {
                group: 'kanban-tasks',
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                handle: '.kanban-card',
                onEnd: function(evt) {
                    TaskKanban.onCardMoved(evt);
                }
            });
            TaskKanban.sortables.push(sortable);
        }
    },

    /**
     * Handle card moved between columns or reordered
     */
    onCardMoved: function(evt) {
        var taskId = parseInt(evt.item.getAttribute('data-task-id'));
        var newListId = parseInt(evt.to.getAttribute('data-list-id'));
        var newPosition = evt.newIndex;

        // Update via AJAX
        $.ajax({
            url: 'dispatch.php',
            data: {
                api: 'tasks',
                f: 'moveTask',
                task_id: taskId,
                list_id: newListId,
                position: newPosition
            },
            dataType: 'json',
            success: function(data) {
                // Update column counters
                TaskKanban.updateColumnCounts();
            },
            error: function() {
                // Revert on error - reload page
                alert('Ошибка перемещения задачи');
                location.reload();
            }
        });
    },

    /**
     * Update task count badges in column headers
     */
    updateColumnCounts: function() {
        $('.kanban-column').each(function() {
            var count = $(this).find('.kanban-card').length;
            $(this).find('.kanban-column-header .badge').text(count);
        });
    },

    /**
     * Quick create task in a specific list
     */
    quickCreate: function(input) {
        var title = $.trim($(input).val());
        if (!title) return;

        var listId = $(input).data('list-id');
        var boardId = $(input).data('board-id');

        $(input).prop('disabled', true);

        $.ajax({
            url: 'dispatch.php',
            data: {
                api: 'tasks',
                f: 'quickCreate',
                title: title,
                board_id: boardId,
                list_id: listId
            },
            dataType: 'json',
            success: function(data) {
                if (data && data.success) {
                    // Add card to column
                    var card = TaskKanban.createCardHtml(data.task_id, title, 'normal', 'open');
                    $(input).closest('.kanban-column').find('.kanban-column-body').append(card);
                    $(input).val('');
                    TaskKanban.updateColumnCounts();
                    // Re-init sortable for new elements
                }
            },
            error: function() {
                alert('Ошибка создания задачи');
            },
            complete: function() {
                $(input).prop('disabled', false);
                $(input).focus();
            }
        });
    },

    /**
     * Create card HTML for a new task
     */
    createCardHtml: function(taskId, title, priority, status) {
        return '<div class="kanban-card priority-' + priority + '" data-task-id="' + taskId + '">'
             + '<div class="kanban-card-title">'
             + '<a href="tasks.php?id=' + taskId + '&view=1">' + $('<span>').text(title).html() + '</a>'
             + '</div>'
             + '<div class="kanban-card-meta">'
             + '<span class="label label-info">Обычный</span>'
             + '<span class="label label-default">Открыта</span>'
             + '</div>'
             + '</div>';
    },

    /**
     * Quick status change from kanban card
     */
    updateStatus: function(taskId, status) {
        TaskManager.updateStatus(taskId, status, function(success) {
            if (success) {
                location.reload();
            }
        });
    },

    /**
     * Load board data via AJAX
     */
    loadBoard: function(boardId) {
        if (!boardId) return;

        window.location = 'tasks.php?display=kanban&board_id=' + boardId;
    }
};

// Init on document ready
$(document).ready(function() {
    // Initialize if kanban board exists on page
    if ($('.kanban-board').length > 0) {
        TaskKanban.init();
    }

    // Quick add - Enter key
    $(document).on('keypress', '.kanban-quick-add input', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            TaskKanban.quickCreate(this);
        }
    });
});
