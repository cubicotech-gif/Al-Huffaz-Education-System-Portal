/**
 * Al-Huffaz Portal - Admin JavaScript
 */

(function($) {
    'use strict';

    // Global state
    const AlHuffazAdmin = {
        init: function() {
            this.initFlatpickr();
            this.initTabs();
            this.initModals();
            this.initForms();
            this.initTables();
            this.initCharts();
            this.initImageUpload();
            this.initSearch();
        },

        // Initialize date pickers
        initFlatpickr: function() {
            if (typeof flatpickr !== 'undefined') {
                flatpickr('.alhuffaz-datepicker', {
                    dateFormat: 'Y-m-d',
                    allowInput: true
                });
            }
        },

        // Tab functionality
        initTabs: function() {
            $(document).on('click', '.alhuffaz-tab', function(e) {
                e.preventDefault();
                const $this = $(this);
                const target = $this.data('tab');

                // Update tab buttons
                $this.siblings().removeClass('active');
                $this.addClass('active');

                // Update tab content
                const $container = $this.closest('.alhuffaz-tabs-container');
                $container.find('.alhuffaz-tab-content').removeClass('active');
                $container.find('[data-tab-content="' + target + '"]').addClass('active');
            });
        },

        // Modal functionality
        initModals: function() {
            // Open modal
            $(document).on('click', '[data-modal]', function(e) {
                e.preventDefault();
                const modalId = $(this).data('modal');
                $('#' + modalId).addClass('active');
                $('body').addClass('alhuffaz-modal-open');
            });

            // Close modal
            $(document).on('click', '.alhuffaz-modal-close, .alhuffaz-modal-overlay', function(e) {
                if (e.target === this) {
                    $(this).closest('.alhuffaz-modal-overlay').removeClass('active');
                    $('body').removeClass('alhuffaz-modal-open');
                }
            });

            // Close on escape
            $(document).on('keyup', function(e) {
                if (e.key === 'Escape') {
                    $('.alhuffaz-modal-overlay.active').removeClass('active');
                    $('body').removeClass('alhuffaz-modal-open');
                }
            });
        },

        // Form handling
        initForms: function() {
            // Student form submission
            $(document).on('submit', '#alhuffaz-student-form', function(e) {
                e.preventDefault();
                AlHuffazAdmin.saveStudent($(this));
            });

            // Settings form submission
            $(document).on('submit', '#alhuffaz-settings-form', function(e) {
                e.preventDefault();
                AlHuffazAdmin.saveSettings($(this));
            });

            // Delete student
            $(document).on('click', '.alhuffaz-delete-student', function(e) {
                e.preventDefault();
                if (confirm(alhuffazAdmin.strings.confirm_delete)) {
                    AlHuffazAdmin.deleteStudent($(this).data('id'));
                }
            });

            // Approve sponsorship
            $(document).on('click', '.alhuffaz-approve-sponsorship', function(e) {
                e.preventDefault();
                AlHuffazAdmin.approveSponsorship($(this).data('id'));
            });

            // Reject sponsorship
            $(document).on('click', '.alhuffaz-reject-sponsorship', function(e) {
                e.preventDefault();
                const reason = prompt('Reason for rejection (optional):');
                AlHuffazAdmin.rejectSponsorship($(this).data('id'), reason);
            });

            // Verify payment
            $(document).on('click', '.alhuffaz-verify-payment', function(e) {
                e.preventDefault();
                AlHuffazAdmin.verifyPayment($(this).data('id'));
            });
        },

        // Save student
        saveStudent: function($form) {
            const $btn = $form.find('[type="submit"]');
            const originalText = $btn.text();

            $btn.prop('disabled', true).text(alhuffazAdmin.strings.saving);

            const formData = new FormData($form[0]);
            formData.append('action', 'alhuffaz_save_student');
            formData.append('nonce', alhuffazAdmin.nonce);

            // Convert form data to object
            const data = {};
            $form.find('input, select, textarea').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    data[name] = $(this).val();
                }
            });

            $.ajax({
                url: alhuffazAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'alhuffaz_save_student',
                    nonce: alhuffazAdmin.nonce,
                    student_id: $form.find('[name="student_id"]').val(),
                    data: data
                },
                success: function(response) {
                    if (response.success) {
                        AlHuffazAdmin.showNotice('success', response.data.message);
                        if (!$form.find('[name="student_id"]').val()) {
                            // New student - redirect or reset form
                            window.location.href = window.location.href.replace('alhuffaz-add-student', 'alhuffaz-students');
                        }
                    } else {
                        AlHuffazAdmin.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    AlHuffazAdmin.showNotice('error', alhuffazAdmin.strings.error);
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },

        // Delete student
        deleteStudent: function(studentId) {
            $.ajax({
                url: alhuffazAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'alhuffaz_delete_student',
                    nonce: alhuffazAdmin.nonce,
                    student_id: studentId
                },
                success: function(response) {
                    if (response.success) {
                        AlHuffazAdmin.showNotice('success', response.data.message);
                        $('[data-student-id="' + studentId + '"]').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        AlHuffazAdmin.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    AlHuffazAdmin.showNotice('error', alhuffazAdmin.strings.error);
                }
            });
        },

        // Approve sponsorship
        approveSponsorship: function(sponsorshipId) {
            $.ajax({
                url: alhuffazAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'alhuffaz_approve_sponsorship',
                    nonce: alhuffazAdmin.nonce,
                    sponsorship_id: sponsorshipId
                },
                success: function(response) {
                    if (response.success) {
                        AlHuffazAdmin.showNotice('success', response.data.message);
                        location.reload();
                    } else {
                        AlHuffazAdmin.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    AlHuffazAdmin.showNotice('error', alhuffazAdmin.strings.error);
                }
            });
        },

        // Reject sponsorship
        rejectSponsorship: function(sponsorshipId, reason) {
            $.ajax({
                url: alhuffazAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'alhuffaz_reject_sponsorship',
                    nonce: alhuffazAdmin.nonce,
                    sponsorship_id: sponsorshipId,
                    reason: reason
                },
                success: function(response) {
                    if (response.success) {
                        AlHuffazAdmin.showNotice('success', response.data.message);
                        location.reload();
                    } else {
                        AlHuffazAdmin.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    AlHuffazAdmin.showNotice('error', alhuffazAdmin.strings.error);
                }
            });
        },

        // Verify payment
        verifyPayment: function(paymentId) {
            $.ajax({
                url: alhuffazAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'alhuffaz_verify_payment',
                    nonce: alhuffazAdmin.nonce,
                    payment_id: paymentId,
                    status: 'approved'
                },
                success: function(response) {
                    if (response.success) {
                        AlHuffazAdmin.showNotice('success', response.data.message);
                        location.reload();
                    } else {
                        AlHuffazAdmin.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    AlHuffazAdmin.showNotice('error', alhuffazAdmin.strings.error);
                }
            });
        },

        // Save settings
        saveSettings: function($form) {
            const $btn = $form.find('[type="submit"]');
            const originalText = $btn.text();

            $btn.prop('disabled', true).text(alhuffazAdmin.strings.saving);

            const settings = {};
            $form.find('input, select, textarea').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    settings[name] = $(this).val();
                }
            });

            $.ajax({
                url: alhuffazAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'alhuffaz_save_settings',
                    nonce: alhuffazAdmin.nonce,
                    settings: settings
                },
                success: function(response) {
                    if (response.success) {
                        AlHuffazAdmin.showNotice('success', response.data.message);
                    } else {
                        AlHuffazAdmin.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    AlHuffazAdmin.showNotice('error', alhuffazAdmin.strings.error);
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },

        // Table functionality
        initTables: function() {
            // Pagination
            $(document).on('click', '.alhuffaz-pagination button', function() {
                const page = $(this).data('page');
                const $table = $(this).closest('.alhuffaz-table-wrapper');
                AlHuffazAdmin.loadTablePage($table, page);
            });

            // Sort
            $(document).on('click', '.alhuffaz-table th[data-sort]', function() {
                const $table = $(this).closest('.alhuffaz-table-wrapper');
                const column = $(this).data('sort');
                const order = $(this).hasClass('asc') ? 'DESC' : 'ASC';

                $(this).siblings().removeClass('asc desc');
                $(this).removeClass('asc desc').addClass(order.toLowerCase());

                AlHuffazAdmin.loadTablePage($table, 1, column, order);
            });
        },

        loadTablePage: function($table, page, orderby, order) {
            // Implementation for table pagination
        },

        // Charts
        initCharts: function() {
            if (typeof Chart === 'undefined') return;

            // Revenue chart
            const revenueCanvas = document.getElementById('alhuffaz-revenue-chart');
            if (revenueCanvas) {
                AlHuffazAdmin.loadRevenueChart(revenueCanvas);
            }

            // Students by grade chart
            const gradeCanvas = document.getElementById('alhuffaz-grade-chart');
            if (gradeCanvas) {
                AlHuffazAdmin.loadGradeChart(gradeCanvas);
            }

            // Sponsorship chart
            const sponsorshipCanvas = document.getElementById('alhuffaz-sponsorship-chart');
            if (sponsorshipCanvas) {
                AlHuffazAdmin.loadSponsorshipChart(sponsorshipCanvas);
            }
        },

        loadRevenueChart: function(canvas) {
            $.ajax({
                url: alhuffazAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'alhuffaz_get_chart_data',
                    nonce: alhuffazAdmin.nonce,
                    chart_type: 'revenue'
                },
                success: function(response) {
                    if (response.success) {
                        new Chart(canvas, {
                            type: 'line',
                            data: {
                                labels: response.data.labels,
                                datasets: [{
                                    label: 'Revenue',
                                    data: response.data.data,
                                    borderColor: '#10b981',
                                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                    tension: 0.4,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false }
                                },
                                scales: {
                                    y: { beginAtZero: true }
                                }
                            }
                        });
                    }
                }
            });
        },

        loadGradeChart: function(canvas) {
            $.ajax({
                url: alhuffazAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'alhuffaz_get_chart_data',
                    nonce: alhuffazAdmin.nonce,
                    chart_type: 'students'
                },
                success: function(response) {
                    if (response.success) {
                        new Chart(canvas, {
                            type: 'bar',
                            data: {
                                labels: response.data.labels,
                                datasets: [{
                                    label: 'Students',
                                    data: response.data.data,
                                    backgroundColor: '#3b82f6'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false }
                                }
                            }
                        });
                    }
                }
            });
        },

        loadSponsorshipChart: function(canvas) {
            $.ajax({
                url: alhuffazAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'alhuffaz_get_chart_data',
                    nonce: alhuffazAdmin.nonce,
                    chart_type: 'sponsorships'
                },
                success: function(response) {
                    if (response.success) {
                        new Chart(canvas, {
                            type: 'doughnut',
                            data: {
                                labels: response.data.labels,
                                datasets: [{
                                    data: response.data.data,
                                    backgroundColor: ['#10b981', '#e5e7eb']
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { position: 'bottom' }
                                }
                            }
                        });
                    }
                }
            });
        },

        // Image upload
        initImageUpload: function() {
            $(document).on('click', '.alhuffaz-photo-upload', function(e) {
                if ($(e.target).is('input')) return;

                const $container = $(this);
                const frame = wp.media({
                    title: 'Select or Upload Image',
                    button: { text: 'Use this image' },
                    multiple: false
                });

                frame.on('select', function() {
                    const attachment = frame.state().get('selection').first().toJSON();
                    $container.find('img').attr('src', attachment.url);
                    $container.find('input[type="hidden"]').val(attachment.id);
                });

                frame.open();
            });
        },

        // Search
        initSearch: function() {
            let searchTimeout;

            $(document).on('keyup', '.alhuffaz-search input', function() {
                clearTimeout(searchTimeout);
                const $this = $(this);
                const $container = $this.closest('.alhuffaz-search').siblings('.alhuffaz-table-wrapper');

                searchTimeout = setTimeout(function() {
                    AlHuffazAdmin.performSearch($this.val(), $container);
                }, 300);
            });
        },

        performSearch: function(query, $container) {
            // Implementation for search
        },

        // Show notification
        showNotice: function(type, message) {
            const $notice = $('<div class="alhuffaz-notice alhuffaz-notice-' + type + '">' + message + '</div>');
            $('.alhuffaz-wrap').prepend($notice);

            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        AlHuffazAdmin.init();
    });

    // Export for global access
    window.AlHuffazAdmin = AlHuffazAdmin;

})(jQuery);
