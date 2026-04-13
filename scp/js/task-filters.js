/**
 * task-filters.js
 * Управление сохранёнными фильтрами задач
 */
var TaskFilters = {

    showSaveDialog: function() {
        $('#btnSaveFilter').hide();
        $('#saveFilterForm').show();
        $('#saveFilterName').focus();
    },

    hideSaveDialog: function() {
        $('#saveFilterForm').hide();
        $('#saveFilterName').val('');
        $('#btnSaveFilter').show();
    },

    /**
     * Собрать текущие параметры фильтра из формы и URL
     */
    collectCurrentParams: function() {
        var params = {};
        var form = $('form.form-inline');
        var boardId = form.find('select[name="board_id"]').val();
        var status = form.find('select[name="status"]').val();
        var priority = form.find('select[name="priority"]').val();
        var assignee = form.find('select[name="assignee"]').val();
        var q = form.find('input[name="q"]').val();
        var view = form.find('input[name="view"]').val();
        var tags = form.find('select[name="tags[]"]').val();

        if (boardId) params.board_id = boardId;
        if (status) params.status = status;
        if (priority) params.priority = priority;
        if (assignee) params.assignee = assignee;
        if (q) params.search_text = q;
        if (view) params.view = view;
        if (tags && tags.length) params.tags = tags;

        return params;
    },

    /**
     * Сохранить текущий фильтр через AJAX
     */
    saveCurrentFilter: function() {
        var name = $.trim($('#saveFilterName').val());
        if (!name) {
            alert('Введите название фильтра');
            $('#saveFilterName').focus();
            return;
        }

        var params = this.collectCurrentParams();
        params.filter_name = name;

        $.ajax({
            url: 'dispatch.php?api=tasks&f=saveFilter',
            type: 'POST',
            data: params,
            dataType: 'json',
            success: function(data) {
                if (data && data.success) {
                    // Reload page to show updated filters list
                    window.location.reload();
                } else {
                    alert(data && data.error ? data.error : 'Ошибка сохранения фильтра');
                }
            },
            error: function(xhr) {
                var msg = 'Ошибка сохранения фильтра';
                try {
                    var resp = $.parseJSON(xhr.responseText);
                    if (resp && resp.error) msg = resp.error;
                } catch(e) {}
                alert(msg);
            }
        });
    },

    /**
     * Загрузить сохранённый фильтр - перенаправить с параметрами
     */
    loadSavedFilter: function(id) {
        if (!id) return;
        var config = savedFilterConfigs[id];
        if (!config) {
            alert('Конфигурация фильтра не найдена');
            return;
        }

        var params = [];
        if (config.view) params.push('view=' + encodeURIComponent(config.view));
        if (config.board_id) params.push('board_id=' + encodeURIComponent(config.board_id));
        if (config.status) {
            if (typeof config.status === 'object') {
                for (var i = 0; i < config.status.length; i++) {
                    params.push('status=' + encodeURIComponent(config.status[i]));
                }
            } else {
                params.push('status=' + encodeURIComponent(config.status));
            }
        }
        if (config.priority) {
            if (typeof config.priority === 'object') {
                for (var i = 0; i < config.priority.length; i++) {
                    params.push('priority=' + encodeURIComponent(config.priority[i]));
                }
            } else {
                params.push('priority=' + encodeURIComponent(config.priority));
            }
        }
        if (config.assignee) {
            if (typeof config.assignee === 'object') {
                for (var i = 0; i < config.assignee.length; i++) {
                    params.push('assignee=' + encodeURIComponent(config.assignee[i]));
                }
            } else {
                params.push('assignee=' + encodeURIComponent(config.assignee));
            }
        }
        if (config.search_text) params.push('q=' + encodeURIComponent(config.search_text));
        if (config.tags) {
            if (typeof config.tags === 'object') {
                for (var i = 0; i < config.tags.length; i++) {
                    params.push('tags[]=' + encodeURIComponent(config.tags[i]));
                }
            }
        }

        window.location.href = 'tasks.php' + (params.length ? '?' + params.join('&') : '');
    },

    /**
     * Удалить сохранённый фильтр
     */
    deleteFilter: function(id) {
        if (!id) return;
        if (!confirm('Удалить этот сохранённый фильтр?')) return;

        $.ajax({
            url: 'dispatch.php?api=tasks&f=deleteFilter',
            type: 'POST',
            data: { filter_id: id },
            dataType: 'json',
            success: function(data) {
                if (data && data.success) {
                    window.location.reload();
                } else {
                    alert(data && data.error ? data.error : 'Ошибка удаления');
                }
            },
            error: function(xhr) {
                alert('Ошибка удаления фильтра');
            }
        });
    },

    /**
     * Переключить фильтр по умолчанию
     */
    toggleDefault: function(id, currentDefault) {
        if (!id) return;

        var data = { filter_id: id };
        if (currentDefault) {
            data.unset = 1;
        }

        $.ajax({
            url: 'dispatch.php?api=tasks&f=setDefaultFilter',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(resp) {
                if (resp && resp.success) {
                    window.location.reload();
                } else {
                    alert(resp && resp.error ? resp.error : 'Ошибка обновления');
                }
            },
            error: function(xhr) {
                alert('Ошибка обновления фильтра');
            }
        });
    }
};
