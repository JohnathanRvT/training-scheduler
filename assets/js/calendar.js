/**
 * Calendar Component & Admin Interactions for Training Scheduler
 */
(function($) {
    'use strict';

    class TrainingCalendar {
        constructor(containerSelector, options = {}) {
            this.$container = $(containerSelector);
            if (!this.$container.length) return;

            this.currentDate = new Date();
            this.events = [];
            this.options = Object.assign({
                editable: true,
                onEventClick: null,
                onDateClick: null
            }, options);

            this.init();
        }

        init() {
            this.bindNavigation();
            this.loadEvents();
        }

        bindNavigation() {
            const self = this;
            $('#cal-prev').on('click', function() {
                self.currentDate.setMonth(self.currentDate.getMonth() - 1);
                self.render();
            });

            $('#cal-next').on('click', function() {
                self.currentDate.setMonth(self.currentDate.getMonth() + 1);
                self.render();
            });

            $('#cal-today').on('click', function() {
                self.currentDate = new Date();
                self.render();
            });
        }

        loadEvents() {
            const self = this;
            if (typeof TrainingAdminData !== 'undefined') {
                $.post(TrainingAdminData.ajax_url, {
                    action: 'training_get_calendar_events',
                    nonce: TrainingAdminData.nonce
                }, function(response) {
                    if (response.success && response.data.events) {
                        self.events = response.data.events;
                        self.render();
                    }
                });
            } else if (typeof TrainingPublicData !== 'undefined') {
                $.post(TrainingPublicData.ajax_url, {
                    action: 'training_get_public_calendar_events',
                    nonce: TrainingPublicData.nonce
                }, function(response) {
                    if (response.success && response.data.events) {
                        self.events = response.data.events;
                        self.render();
                    }
                });
            } else {
                self.render();
            }
        }

        render() {
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();
            const monthNames = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];

            $('#calendar-title').text(`${monthNames[month]} ${year}`);

            // First day of month
            const firstDayOfMonth = new Date(year, month, 1);
            const startingDayOfWeek = firstDayOfMonth.getDay(); // 0 is Sunday
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();

            let html = '<table class="cal-month-table">';
            html += '<thead><tr>';
            const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            days.forEach(d => {
                html += `<th>${d}</th>`;
            });
            html += '</tr></thead><tbody><tr>';

            let dayCounter = 1;
            let nextMonthDay = 1;

            const today = new Date();
            const isThisMonth = today.getFullYear() === year && today.getMonth() === month;

            // First week leading empty or previous month days
            for (let i = 0; i < 7; i++) {
                if (i < startingDayOfWeek) {
                    const prevDate = daysInPrevMonth - (startingDayOfWeek - 1 - i);
                    html += `<td class="other-month"><div class="cal-day-header"><span class="cal-day-number">${prevDate}</span></div></td>`;
                } else {
                    html += this.renderDayCell(year, month, dayCounter, isThisMonth && today.getDate() === dayCounter);
                    dayCounter++;
                }
            }
            html += '</tr>';

            // Remaining weeks
            while (dayCounter <= daysInMonth) {
                html += '<tr>';
                for (let i = 0; i < 7; i++) {
                    if (dayCounter <= daysInMonth) {
                        html += this.renderDayCell(year, month, dayCounter, isThisMonth && today.getDate() === dayCounter);
                        dayCounter++;
                    } else {
                        html += `<td class="other-month"><div class="cal-day-header"><span class="cal-day-number">${nextMonthDay}</span></div></td>`;
                        nextMonthDay++;
                    }
                }
                html += '</tr>';
                if (nextMonthDay > 1) break;
            }

            html += '</tbody></table>';
            this.$container.html(html);

            this.bindCellEvents();
        }

        renderDayCell(year, month, day, isToday) {
            const formattedMonth = String(month + 1).padStart(2, '0');
            const formattedDay = String(day).padStart(2, '0');
            const dateStr = `${year}-${formattedMonth}-${formattedDay}`;

            const dayEvents = this.events.filter(e => e.date === dateStr);

            let cell = `<td class="${isToday ? 'today' : ''}" data-date="${dateStr}">`;
            cell += `<div class="cal-day-header">`;
            cell += `<span class="cal-day-number">${day}</span>`;
            if (this.options.editable) {
                cell += `<span class="cal-add-btn" title="Add Session" data-date="${dateStr}">+</span>`;
            }
            cell += `</div>`;
            cell += `<div class="cal-events-list">`;

            dayEvents.forEach(evt => {
                const title = evt.title || (evt.client_name ? `${evt.client_name} (${evt.start_time})` : 'Session');
                const timeLabel = evt.start_time ? `${evt.start_time} ` : '';
                cell += `<div class="cal-event-pill status-${evt.status || 'confirmed'}" data-id="${evt.id}" title="${timeLabel}${title}">`;
                cell += `<strong>${timeLabel}</strong>${title}`;
                cell += `</div>`;
            });

            cell += `</div></td>`;
            return cell;
        }

        bindCellEvents() {
            const self = this;

            this.$container.find('.cal-event-pill').on('click', function(e) {
                e.stopPropagation();
                const eventId = $(this).data('id');
                const eventData = self.events.find(ev => ev.id == eventId);
                if (self.options.onEventClick) {
                    self.options.onEventClick(eventData);
                }
            });

            this.$container.find('.cal-add-btn').on('click', function(e) {
                e.stopPropagation();
                const dateStr = $(this).data('date');
                if (self.options.onDateClick) {
                    self.options.onDateClick(dateStr);
                }
            });
        }
    }

    // Admin Page Interactivity
    $(document).ready(function() {
        let calendar = null;

        if ($('#training-admin-calendar-root').length) {
            calendar = new TrainingCalendar('#training-admin-calendar-root', {
                editable: true,
                onEventClick: function(event) {
                    openSessionModal(event);
                },
                onDateClick: function(date) {
                    openSessionModal({ date: date, start_time: '09:00', end_time: '10:00', status: 'confirmed' });
                }
            });
        }

        // Session Modal Handlers
        $('#open-new-session-modal').on('click', function() {
            const today = new Date().toISOString().split('T')[0];
            openSessionModal({ date: today, start_time: '09:00', end_time: '10:00', status: 'confirmed' });
        });

        $('.close-modal').on('click', function() {
            $('#session-modal').fadeOut(150);
            $('#package-modal').fadeOut(150);
        });

        function openSessionModal(data = {}) {
            $('#session-form')[0].reset();
            $('#session-id').val(data.id || '');
            $('#session-client-name').val(data.client_name || '');
            $('#session-client-email').val(data.client_email || '');
            $('#session-client-phone').val(data.client_phone || '');
            $('#session-package').val(data.package_id || '0');
            $('#session-date').val(data.date || '');
            $('#session-start-time').val(data.start_time || '09:00');
            $('#session-end-time').val(data.end_time || '10:00');
            $('#session-status').val(data.status || 'confirmed');
            $('#session-notes').val(data.notes || '');

            if (data.id) {
                $('#session-modal-title').text('Edit Training Session #' + data.id);
                $('#delete-session-btn').show();
            } else {
                $('#session-modal-title').text('Schedule New Training Session');
                $('#delete-session-btn').hide();
            }

            $('#session-modal').fadeIn(150);
        }

        $('#session-form').on('submit', function(e) {
            e.preventDefault();
            const formData = $(this).serialize() + '&action=training_save_booking&nonce=' + TrainingAdminData.nonce;

            $.post(TrainingAdminData.ajax_url, formData, function(res) {
                if (res.success) {
                    $('#session-modal').fadeOut(150);
                    if (calendar) {
                        calendar.loadEvents();
                    } else {
                        location.reload();
                    }
                } else {
                    alert(res.data.message || 'Error saving session');
                }
            });
        });

        $('#delete-session-btn').on('click', function() {
            const id = $('#session-id').val();
            if (!id || !confirm('Are you sure you want to delete this session?')) return;

            $.post(TrainingAdminData.ajax_url, {
                action: 'training_delete_booking',
                id: id,
                nonce: TrainingAdminData.nonce
            }, function(res) {
                if (res.success) {
                    $('#session-modal').fadeOut(150);
                    if (calendar) {
                        calendar.loadEvents();
                    } else {
                        location.reload();
                    }
                } else {
                    alert(res.data.message || 'Error deleting session');
                }
            });
        });

        // Table Edit/Delete Buttons in Bookings screen
        $('.edit-booking-btn').on('click', function() {
            const $btn = $(this);
            openSessionModal({
                id: $btn.data('id'),
                client_name: $btn.data('name'),
                client_email: $btn.data('email'),
                client_phone: $btn.data('phone'),
                package_id: $btn.data('package'),
                date: $btn.data('date'),
                start_time: $btn.data('start'),
                end_time: $btn.data('end'),
                status: $btn.data('status'),
                notes: $btn.data('notes')
            });
        });

        $('.delete-booking-btn').on('click', function() {
            const id = $(this).data('id');
            if (!confirm('Are you sure you want to remove this booking?')) return;

            $.post(TrainingAdminData.ajax_url, {
                action: 'training_delete_booking',
                id: id,
                nonce: TrainingAdminData.nonce
            }, function(res) {
                if (res.success) {
                    location.reload();
                } else {
                    alert(res.data.message || 'Error removing booking');
                }
            });
        });

        // Bookings filtering & search
        $('#filter-status, #search-bookings').on('input change', function() {
            const statusFilter = $('#filter-status').val().toLowerCase();
            const searchTerm = $('#search-bookings').val().toLowerCase();

            $('#bookings-table-body tr').each(function() {
                const $row = $(this);
                if ($row.hasClass('no-items')) return;

                const rowStatus = ($row.data('status') || '').toLowerCase();
                const rowSearch = ($row.data('search') || '').toLowerCase();

                const matchStatus = !statusFilter || rowStatus === statusFilter;
                const matchSearch = !searchTerm || rowSearch.indexOf(searchTerm) !== -1;

                if (matchStatus && matchSearch) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });
        });

        // Package Modal Handlers
        $('#open-new-package-modal').on('click', function() {
            $('#package-form')[0].reset();
            $('#pkg-id').val('');
            $('#package-modal-title').text('Create Training Package');
            $('#package-modal').fadeIn(150);
        });

        $('.edit-package-btn').on('click', function() {
            const $btn = $(this);
            $('#pkg-id').val($btn.data('id'));
            $('#pkg-title').val($btn.data('title'));
            $('#pkg-price').val($btn.data('price'));
            $('#pkg-sessions').val($btn.data('sessions'));
            $('#pkg-duration').val($btn.data('duration'));
            $('#pkg-badge').val($btn.data('badge'));
            $('#pkg-desc').val($btn.data('desc'));
            $('#pkg-features').val($btn.data('features'));

            $('#package-modal-title').text('Edit Training Package');
            $('#package-modal').fadeIn(150);
        });

        $('#package-form').on('submit', function(e) {
            e.preventDefault();
            const formData = $(this).serialize() + '&action=training_save_package&nonce=' + TrainingAdminData.nonce;

            $.post(TrainingAdminData.ajax_url, formData, function(res) {
                if (res.success) {
                    location.reload();
                } else {
                    alert(res.data.message || 'Error saving package');
                }
            });
        });

        $('.delete-package-btn').on('click', function() {
            const id = $(this).data('id');
            if (!confirm('Are you sure you want to delete this package?')) return;

            $.post(TrainingAdminData.ajax_url, {
                action: 'training_delete_package',
                id: id,
                nonce: TrainingAdminData.nonce
            }, function(res) {
                if (res.success) {
                    location.reload();
                } else {
                    alert(res.data.message || 'Error deleting package');
                }
            });
        });

        // Settings Day Toggles
        $('.day-toggle').on('change', function() {
            const $row = $(this).closest('tr');
            const isChecked = $(this).is(':checked');
            $row.find('.time-input').prop('disabled', !isChecked);
            $row.find('.toggle-status-label').text(isChecked ? 'Open' : 'Closed');
        });
    });

    // Expose TrainingCalendar globally for frontend calendar reuse
    window.TrainingCalendar = TrainingCalendar;

})(jQuery);
