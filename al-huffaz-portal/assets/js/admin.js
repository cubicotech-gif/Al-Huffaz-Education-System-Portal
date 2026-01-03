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

/**
 * Enhanced Student Form Wizard
 */
(function($) {
    'use strict';

    // Only run on student form page
    if ($('#ahpStudentForm').length === 0) return;

    let currentStep = 1;
    const totalSteps = 5;
    let subjectIndex = $('.ahp-subject-box').length;
    let monthlyExamCounters = {};

    // Initialize existing subject counters
    $('.ahp-subject-box').each(function() {
        const idx = $(this).data('index');
        monthlyExamCounters[idx] = $(this).find('.ahp-monthly-exam').length;
    });

    // Show specific step
    function showStep(step) {
        $('.ahp-form-step').removeClass('active');
        $(`.ahp-form-step[data-step="${step}"]`).addClass('active');

        $('.ahp-progress-step').removeClass('active completed');
        $('.ahp-progress-step').each(function() {
            const s = $(this).data('step');
            if (s < step) $(this).addClass('completed');
            if (s === step) $(this).addClass('active');
        });

        $('#prevStepBtn').toggle(step > 1);
        $('#nextStepBtn').toggle(step < totalSteps);
        $('#submitFormBtn').toggle(step === totalSteps);

        $('html, body').animate({ scrollTop: $('.ahp-student-form-wrapper').offset().top - 20 }, 300);
    }

    // Validate current step
    function validateStep(step) {
        let valid = true;
        const stepEl = $(`.ahp-form-step[data-step="${step}"]`);
        stepEl.find('[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('ahp-input-error').focus();
                valid = false;
                return false;
            }
        });
        return valid;
    }

    // Calculate grade from percentage
    function calculateGrade(p) {
        if (p >= 90) return 'A+';
        if (p >= 80) return 'A';
        if (p >= 70) return 'B';
        if (p >= 60) return 'C';
        if (p >= 50) return 'D';
        return 'F';
    }

    // Show notification
    function ahpShowNotification(type, message) {
        const notification = $(`<div class="ahp-notification ${type}"><i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}</div>`);
        $('body').append(notification);
        setTimeout(() => notification.remove(), 5000);
    }

    // Next step button
    $('#nextStepBtn').on('click', function() {
        if (validateStep(currentStep) && currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);
        }
    });

    // Previous step button
    $('#prevStepBtn').on('click', function() {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
        }
    });

    // Click on progress step
    $(document).on('click', '.ahp-progress-step', function() {
        const targetStep = $(this).data('step');
        if (targetStep < currentStep || validateStep(currentStep)) {
            currentStep = targetStep;
            showStep(currentStep);
        }
    });

    // Remove input error on change
    $(document).on('input change', '.ahp-input-error', function() {
        $(this).removeClass('ahp-input-error');
    });

    // Add Subject
    $('#addSubjectBtn').on('click', function() {
        const template = $('#subjectTemplate').html().replace(/SUBJECT_INDEX/g, subjectIndex);
        $('#subjectsContainer').append(template);
        $('#noSubjectsMessage').hide();
        monthlyExamCounters[subjectIndex] = 0;
        subjectIndex++;
    });

    // Remove Subject
    $(document).on('click', '.ahp-remove-subject', function() {
        if (confirm('Remove this subject and all its data?')) {
            $(this).closest('.ahp-subject-box').remove();
            if ($('.ahp-subject-box').length === 0) {
                $('#noSubjectsMessage').show();
            }
        }
    });

    // Add Monthly Exam
    $(document).on('click', '.ahp-add-monthly', function() {
        const subjIdx = $(this).data('subject-index');
        if (!monthlyExamCounters[subjIdx]) monthlyExamCounters[subjIdx] = 0;
        const monthIdx = monthlyExamCounters[subjIdx];

        const template = $('#monthlyExamTemplate').html()
            .replace(/SUBJECT_INDEX/g, subjIdx)
            .replace(/MONTH_INDEX/g, monthIdx);

        $(`.ahp-monthly-container[data-subject="${subjIdx}"]`).append(template);
        monthlyExamCounters[subjIdx]++;
    });

    // Remove Monthly Exam
    $(document).on('click', '.ahp-remove-monthly', function() {
        $(this).closest('.ahp-monthly-exam').remove();
    });

    // Auto-calculate marks
    $(document).on('input', '.marks-input', function() {
        const container = $(this).closest('.ahp-marks-row');
        const oralTotal = parseFloat(container.find('.oral-total').val()) || 0;
        const oralObtained = parseFloat(container.find('.oral-obtained').val()) || 0;
        const writtenTotal = parseFloat(container.find('.written-total').val()) || 0;
        const writtenObtained = parseFloat(container.find('.written-obtained').val()) || 0;

        const total = oralTotal + writtenTotal;
        const obtained = oralObtained + writtenObtained;
        const percentage = total > 0 ? ((obtained / total) * 100).toFixed(1) : 0;
        const grade = calculateGrade(percentage);

        const resultEl = container.siblings('.ahp-marks-result');
        if (total > 0) {
            const statusClass = percentage >= 80 ? 'excellent' : (percentage >= 60 ? 'good' : 'poor');
            resultEl.html(`
                <div class="ahp-result-box ahp-result-${statusClass}">
                    <span><strong>${obtained}</strong>/${total}</span>
                    <span><strong>${percentage}%</strong></span>
                    <span class="ahp-grade-badge">${grade}</span>
                </div>
            `).show();
        } else {
            resultEl.hide();
        }
    });

    // Fee calculations
    $('.fee-input').on('input', function() {
        const monthly = parseFloat($('[name="monthly_tuition_fee"]').val()) || 0;
        const course = parseFloat($('[name="course_fee"]').val()) || 0;
        const uniform = parseFloat($('[name="uniform_fee"]').val()) || 0;
        const annual = parseFloat($('[name="annual_fee"]').val()) || 0;
        const admission = parseFloat($('[name="admission_fee"]').val()) || 0;

        const oneTime = course + uniform + annual + admission;
        const total = monthly + oneTime;

        $('#monthlyTotal').text('PKR ' + monthly.toLocaleString());
        $('#oneTimeTotal').text('PKR ' + oneTime.toLocaleString());
        $('#grandTotal').text('PKR ' + total.toLocaleString());
    }).trigger('input');

    // Attendance calculation
    $('#totalSchoolDays, #presentDays').on('input', function() {
        const total = parseFloat($('#totalSchoolDays').val()) || 0;
        const present = parseFloat($('#presentDays').val()) || 0;
        const display = $('#attendanceDisplay');

        if (total > 0 && present >= 0) {
            const pct = ((present / total) * 100).toFixed(1);
            const statusClass = pct >= 85 ? 'excellent' : (pct >= 75 ? 'good' : 'poor');
            display.removeClass('excellent good poor').addClass(statusClass);
            display.find('.ahp-attendance-value').text(pct);
        } else {
            display.removeClass('excellent good poor');
            display.find('.ahp-attendance-value').text('--');
        }
    }).trigger('input');

    // Star ratings
    $(document).on('click', '.ahp-star', function() {
        const value = $(this).data('value');
        const container = $(this).closest('.ahp-rating-stars');
        container.find('.ahp-star').removeClass('active');
        container.find('.ahp-star').each(function() {
            if ($(this).data('value') <= value) $(this).addClass('active');
        });
        container.find('input').val(value);
    });

    // Form submission
    $('#ahpStudentForm').on('submit', function(e) {
        e.preventDefault();

        const btn = $('#submitFormBtn');
        const originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        const formData = new FormData(this);

        $.ajax({
            url: typeof alhuffazAdmin !== 'undefined' ? alhuffazAdmin.ajaxUrl : ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    ahpShowNotification('success', response.data.message || 'Student saved successfully!');
                    setTimeout(() => {
                        window.location.href = response.data.redirect || '?page=alhuffaz-students';
                    }, 1000);
                } else {
                    ahpShowNotification('error', response.data || 'An error occurred');
                    btn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                ahpShowNotification('error', 'Connection error. Please try again.');
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Export notification function globally
    window.ahpShowNotification = ahpShowNotification;

})(jQuery);
