/**
 * Task Calendar JavaScript
 * Phase 4: FullCalendar integration for week/month views
 */

var TaskCalendar = {

    calendar: null,
    tooltip: null,
    currentBoardId: 0,
    currentView: 'month',

    /**
     * Initialize FullCalendar
     */
    init: function(options) {
        var self = this;
        var defaultView = options.view || 'month';
        self.currentBoardId = options.boardId || 0;

        // Map our view names to FullCalendar views
        var fcView = defaultView === 'week' ? 'agendaWeek' : 'month';

        $('#task-calendar').fullCalendar({
            defaultView: fcView,
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaWeek'
            },
            editable: true,
            droppable: false,
            eventLimit: 4,
            firstDay: 1, // Monday
            height: 'auto',
            // Time slot configuration for week view
            slotDuration: '00:30:00',     // 30-minute slots
            slotLabelInterval: '01:00',   // Hour labels
            minTime: '08:00:00',          // Start at 8 AM
            maxTime: '20:00:00',          // End at 8 PM
            scrollTime: '09:00:00',       // Scroll to 9 AM
            allDaySlot: true,             // Show all-day row
            displayEventTime: true,       // Show event times
            timeFormat: 'H:mm',
            slotLabelFormat: 'H:mm',
            allDayText: 'Весь день',
            noEventsMessage: 'Нет задач',

            // Load events via AJAX
            events: function(start, end, timezone, callback) {
                $.ajax({
                    url: 'dispatch.php',
                    data: {
                        api: 'tasks',
                        f: 'calendarEvents',
                        start: start.format('YYYY-MM-DD'),
                        end: end.format('YYYY-MM-DD'),
                        board_id: self.currentBoardId
                    },
                    dataType: 'json',
                    success: function(data) {
                        callback(data || []);
                    },
                    error: function() {
                        callback([]);
                    }
                });
            },

            // Click on event - go to task (view mode)
            eventClick: function(event) {
                window.location = 'tasks.php?id=' + event.id + '&view=1';
            },

            // Drag event to change dates
            eventDrop: function(event, delta, revertFunc) {
                self.updateTaskDates(event, revertFunc);
            },

            // Resize event to change end date
            eventResize: function(event, delta, revertFunc) {
                self.updateTaskDates(event, revertFunc);
            },

            // Click on empty day - create task
            dayClick: function(date) {
                var dateStr = date.format('YYYY-MM-DD');
                var url = 'tasks.php?a=add&start_date=' + dateStr + '&deadline=' + dateStr;
                if (self.currentBoardId) {
                    url += '&board_id=' + self.currentBoardId;
                }
                window.location = url;
            },

            // Event hover tooltip
            eventMouseover: function(event, jsEvent) {
                self.showTooltip(event, jsEvent);
            },

            eventMouseout: function() {
                self.hideTooltip();
            },

            // View change callback
            viewRender: function(view) {
                self.currentView = (view.name === 'agendaWeek') ? 'week' : 'month';
            }
        });

        self.calendar = $('#task-calendar');
    },

    /**
     * Update task dates after drag/resize
     */
    updateTaskDates: function(event, revertFunc) {
        var startDate = event.start ? event.start.format('YYYY-MM-DD') : '';
        var endDate = event.end ? event.end.format('YYYY-MM-DD') : startDate;

        $.ajax({
            url: 'dispatch.php',
            data: {
                api: 'tasks',
                f: 'updateDates',
                id: event.id,
                start_date: startDate,
                end_date: endDate,
                deadline: endDate
            },
            dataType: 'json',
            success: function(data) {
                if (!data || !data.success) {
                    revertFunc();
                }
            },
            error: function() {
                revertFunc();
            }
        });
    },

    /**
     * Show tooltip on event hover
     */
    showTooltip: function(event, jsEvent) {
        this.hideTooltip();

        var html = '<div class="calendar-tooltip">'
                 + '<div class="tooltip-title">' + $('<span>').text(event.title).html() + '</div>';

        // Add description if present
        if (event.description) {
            var desc = event.description;
            if (desc.length > 100) {
                desc = desc.substring(0, 100) + '...';
            }
            html += '<div class="tooltip-description">' + $('<span>').text(desc).html() + '</div>';
        }

        if (event.assignees) {
            html += '<div class="tooltip-meta"><i class="fa fa-user"></i> ' + $('<span>').text(event.assignees).html() + '</div>';
        }
        if (event.priorityLabel) {
            html += '<div class="tooltip-meta"><i class="fa fa-flag"></i> ' + $('<span>').text(event.priorityLabel).html() + '</div>';
        }
        if (event.statusLabel) {
            html += '<div class="tooltip-meta"><i class="fa fa-circle-o"></i> ' + $('<span>').text(event.statusLabel).html() + '</div>';
        }
        if (event.boardName) {
            html += '<div class="tooltip-meta"><i class="fa fa-columns"></i> ' + $('<span>').text(event.boardName).html() + '</div>';
        }

        html += '</div>';

        this.tooltip = $(html).appendTo('body');
        this.tooltip.css({
            top: jsEvent.pageY + 10,
            left: jsEvent.pageX + 10
        });
    },

    /**
     * Hide tooltip
     */
    hideTooltip: function() {
        if (this.tooltip) {
            this.tooltip.remove();
            this.tooltip = null;
        }
    },

    /**
     * Switch board filter
     */
    setBoard: function(boardId) {
        this.currentBoardId = boardId;
        $('#task-calendar').fullCalendar('refetchEvents');
    },

    /**
     * Refresh calendar events
     */
    refresh: function() {
        $('#task-calendar').fullCalendar('refetchEvents');
    }
};
