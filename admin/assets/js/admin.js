/**
 * n8n Automation Connector - Admin JavaScript
 *
 * Handles admin panel interactions
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        // Auto-refresh dashboard every 30 seconds
        if ($('.n8n-dashboard').length > 0) {
            setTimeout(function () {
                location.reload();
            }, 30000);
        }

        // Add smooth transitions to stat cards
        $('.n8n-stat-card').each(function () {
            $(this).on('mouseenter', function () {
                $(this).css('transform', 'translateY(-2px)');
            }).on('mouseleave', function () {
                $(this).css('transform', 'translateY(0)');
            });
        });

        // Handle details/summary for log expansion
        $('details').on('toggle', function () {
            if (this.open) {
                $(this).parent().css('background-color', '#f9f9f9');
            } else {
                $(this).parent().css('background-color', 'transparent');
            }
        });

        // Confirm actions
        $('button[onclick*="confirm"]').on('click', function (e) {
            if (!confirm('Are you sure?')) {
                e.preventDefault();
                return false;
            }
        });
    });

})(jQuery);
