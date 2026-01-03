/**
 * Al-Huffaz Portal - Public JavaScript
 */

(function($) {
    'use strict';

    const AlHuffazPublic = {
        init: function() {
            this.initStudentFilters();
            this.initSponsorshipForm();
            this.initPaymentForm();
            this.initStudentSelect();
            this.initFlatpickr();
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

        // Student filters
        initStudentFilters: function() {
            let filterTimeout;

            $(document).on('keyup change', '.alhuffaz-filters-bar input, .alhuffaz-filters-bar select', function() {
                clearTimeout(filterTimeout);
                filterTimeout = setTimeout(function() {
                    AlHuffazPublic.filterStudents();
                }, 300);
            });

            // Pagination
            $(document).on('click', '.alhuffaz-pagination button', function() {
                const page = $(this).data('page');
                AlHuffazPublic.filterStudents(page);
            });
        },

        filterStudents: function(page) {
            page = page || 1;

            const $container = $('.alhuffaz-students-grid');
            const $pagination = $('.alhuffaz-pagination');

            // Get filter values
            const search = $('.alhuffaz-search-box input').val();
            const grade = $('[name="grade"]').val();
            const category = $('[name="category"]').val();
            const gender = $('[name="gender"]').val();

            // Show loading
            $container.html('<div class="alhuffaz-loading"><div class="alhuffaz-spinner"></div><p>Loading...</p></div>');

            $.ajax({
                url: alhuffazPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'alhuffaz_filter_students',
                    nonce: alhuffazPublic.nonce,
                    page: page,
                    per_page: 12,
                    search: search,
                    grade: grade,
                    category: category,
                    gender: gender
                },
                success: function(response) {
                    if (response.success) {
                        AlHuffazPublic.renderStudents(response.data.students, $container);
                        AlHuffazPublic.renderPagination(response.data, $pagination);
                    }
                },
                error: function() {
                    $container.html('<div class="alhuffaz-empty"><p>Failed to load students. Please try again.</p></div>');
                }
            });
        },

        renderStudents: function(students, $container) {
            if (!students.length) {
                $container.html('<div class="alhuffaz-empty"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg><h3 class="alhuffaz-empty-title">No students found</h3><p>Try adjusting your search or filters</p></div>');
                return;
            }

            let html = '';
            students.forEach(function(student) {
                html += AlHuffazPublic.getStudentCardHtml(student);
            });

            $container.html(html);
        },

        getStudentCardHtml: function(student) {
            const badgeClass = student.is_sponsored ? 'sponsored' : 'available';
            const badgeText = student.is_sponsored ? 'Sponsored' : 'Available';

            return `
                <div class="alhuffaz-student-card alhuffaz-fade-in">
                    <div class="alhuffaz-student-card-image">
                        <img src="${student.photo}" alt="${student.name}">
                        <span class="alhuffaz-student-card-badge ${badgeClass}">${badgeText}</span>
                    </div>
                    <div class="alhuffaz-student-card-body">
                        <h3 class="alhuffaz-student-card-name">${student.name}</h3>
                        <div class="alhuffaz-student-card-meta">
                            <span><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg> ${student.grade}</span>
                            <span><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg> ${student.category}</span>
                        </div>
                        <p class="alhuffaz-student-card-description">${student.description}</p>
                        <div class="alhuffaz-student-card-footer">
                            <div class="alhuffaz-student-card-amount">
                                ${student.monthly_fee}<span>/month</span>
                            </div>
                            ${!student.is_sponsored ? `<a href="?sponsor=${student.id}" class="alhuffaz-btn alhuffaz-btn-primary">Sponsor</a>` : ''}
                        </div>
                    </div>
                </div>
            `;
        },

        renderPagination: function(data, $container) {
            if (data.total_pages <= 1) {
                $container.empty();
                return;
            }

            let html = '';

            // Previous button
            html += `<button ${data.page <= 1 ? 'disabled' : ''} data-page="${data.page - 1}">Previous</button>`;

            // Page numbers
            for (let i = 1; i <= data.total_pages; i++) {
                if (i === data.page) {
                    html += `<button class="active" data-page="${i}">${i}</button>`;
                } else if (i <= 3 || i > data.total_pages - 2 || Math.abs(i - data.page) <= 1) {
                    html += `<button data-page="${i}">${i}</button>`;
                } else if (i === 4 && data.page > 5) {
                    html += '<span>...</span>';
                } else if (i === data.total_pages - 2 && data.page < data.total_pages - 4) {
                    html += '<span>...</span>';
                }
            }

            // Next button
            html += `<button ${data.page >= data.total_pages ? 'disabled' : ''} data-page="${data.page + 1}">Next</button>`;

            $container.html(html);
        },

        // Student selection for sponsorship
        initStudentSelect: function() {
            $(document).on('click', '.alhuffaz-student-select-card', function() {
                const $this = $(this);
                const studentId = $this.data('student-id');

                // Deselect others
                $('.alhuffaz-student-select-card').removeClass('selected');
                $this.addClass('selected');

                // Update hidden input
                $('[name="student_id"]').val(studentId);

                // Update selected student display
                const name = $this.find('h4').text();
                const details = $this.find('p').text();
                const photo = $this.find('img').attr('src');

                $('.alhuffaz-selected-student img').attr('src', photo);
                $('.alhuffaz-selected-student h4').text(name);
                $('.alhuffaz-selected-student p').text(details);
                $('.alhuffaz-selected-student').show();
            });
        },

        // Sponsorship form
        initSponsorshipForm: function() {
            $(document).on('submit', '#alhuffaz-sponsorship-form', function(e) {
                e.preventDefault();
                AlHuffazPublic.submitSponsorship($(this));
            });
        },

        submitSponsorship: function($form) {
            const $btn = $form.find('[type="submit"]');
            const originalText = $btn.text();

            // Validate
            if (!$form.find('[name="student_id"]').val()) {
                AlHuffazPublic.showNotice('error', 'Please select a student to sponsor.');
                return;
            }

            $btn.prop('disabled', true).text(alhuffazPublic.strings.submitting);

            const formData = new FormData($form[0]);
            formData.append('action', 'alhuffaz_submit_sponsorship');
            formData.append('nonce', alhuffazPublic.nonce);

            // Build data object
            const data = {};
            $form.find('input, select, textarea').each(function() {
                const name = $(this).attr('name');
                if (name && $(this).attr('type') !== 'file') {
                    data[name] = $(this).val();
                }
            });

            $.ajax({
                url: alhuffazPublic.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        AlHuffazPublic.showNotice('success', response.data.message);
                        $form[0].reset();
                        $('.alhuffaz-selected-student').hide();
                        $('.alhuffaz-student-select-card').removeClass('selected');
                    } else {
                        AlHuffazPublic.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    AlHuffazPublic.showNotice('error', alhuffazPublic.strings.error);
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },

        // Payment form
        initPaymentForm: function() {
            $(document).on('submit', '#alhuffaz-payment-form', function(e) {
                e.preventDefault();
                AlHuffazPublic.submitPayment($(this));
            });

            // Load sponsorships for payment form
            if ($('#alhuffaz-payment-form').length) {
                AlHuffazPublic.loadSponsorships();
            }
        },

        loadSponsorships: function() {
            $.ajax({
                url: alhuffazPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'alhuffaz_get_sponsor_sponsorships',
                    nonce: alhuffazPublic.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const $select = $('[name="sponsorship_id"]');
                        $select.empty().append('<option value="">Select a sponsorship</option>');

                        response.data.sponsorships.forEach(function(s) {
                            $select.append(`<option value="${s.id}" data-amount="${s.amount}">${s.student_name} - ${alhuffazPublic.currency} ${s.amount}</option>`);
                        });
                    }
                }
            });
        },

        submitPayment: function($form) {
            const $btn = $form.find('[type="submit"]');
            const originalText = $btn.text();

            $btn.prop('disabled', true).text(alhuffazPublic.strings.submitting);

            const formData = new FormData($form[0]);
            formData.append('action', 'alhuffaz_submit_payment');
            formData.append('nonce', alhuffazPublic.nonce);

            $.ajax({
                url: alhuffazPublic.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        AlHuffazPublic.showNotice('success', response.data.message);
                        $form[0].reset();
                    } else {
                        AlHuffazPublic.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    AlHuffazPublic.showNotice('error', alhuffazPublic.strings.error);
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },

        // Show notification
        showNotice: function(type, message) {
            // Remove existing notices
            $('.alhuffaz-notice').remove();

            const $notice = $(`<div class="alhuffaz-notice notice-${type}">${message}</div>`);
            $('.alhuffaz-container').prepend($notice);

            // Scroll to notice
            $('html, body').animate({
                scrollTop: $notice.offset().top - 100
            }, 300);

            // Auto remove after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        AlHuffazPublic.init();
    });

    // Export for global access
    window.AlHuffazPublic = AlHuffazPublic;

})(jQuery);
