
jQuery(document).ready(function($) {
    // Make tabs work without page refresh
    $('.dashboard-nav .nav-item').click(function(e) {
        e.preventDefault();
        
        var view = $(this).attr('href').split('dashboard_view=')[1];
        
        // Update URL without reloading
        history.pushState(null, null, $(this).attr('href'));
        
        // Add active class
        $('.dashboard-nav .nav-item').removeClass('active');
        $(this).addClass('active');
        
        // Load content via AJAX
        loadDashboardContent(view);
    });
    
    function loadDashboardContent(view) {
        $.ajax({
            url: dashboardData.ajaxurl,
            type: 'POST',
            data: {
                action: 'load_dashboard_view',
                view: view,
                nonce: dashboardData.nonce
            },
            beforeSend: function() {
                $('.dashboard-content').html('<div class="loading-spinner"></div>');
            },
            success: function(response) {
                $('.dashboard-content').html(response);
            },
            error: function() {
                $('.dashboard-content').html('<div class="error-message">Error loading content. Please refresh the page.</div>');
            }
        });
    }
});