/**
 * CPHT Plugin Public JavaScript
 *
 * Handles AJAX post filtering and other interactive features.
 *
 * @package CPHT_Plugin
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // CPHT Posts Filter Object
    var CPHTFilter = {
        // Initialize the filter functionality
        init: function() {
            console.log('CPHTFilter init started');

            // Cache DOM elements
            this.$filterSelect = $('#cpht-category-filter');
            this.$postsGrid = $('.cpht-grid');
            this.$contentArea = $('.cpht-content-area');
            this.$paginationArea = $('.cpht-pagination');
            this.$wrapper = $('.cpht-posts-wrapper');

            // Only proceed if we have the filter on the page
            if (this.$filterSelect.length === 0) {
                console.log('Filter not found on page, exiting');
                return;
            }

            console.log('Filter found, binding events');

            // Bind events
            this.bindEvents();
        },

        // Bind all necessary events
        bindEvents: function() {
            console.log('Binding events');

            // Category filter change
            this.$filterSelect.on('change', this.handleFilterChange.bind(this));
        },

        // Handle filter dropdown change
        handleFilterChange: function(e) {
            var selectedCategory = this.$filterSelect.val();
            console.log('Filter changed to:', selectedCategory);

            try {
                // Use a simpler approach with page reloads instead of AJAX
                if (selectedCategory === '') {
                    // If "Filter by Category" option is selected, remove filter parameter
                    window.location.href = window.location.pathname;
                } else {
                    // Otherwise, add the category filter to URL and reload
                    window.location.href = window.location.pathname + '?cpht_category=' + encodeURIComponent(selectedCategory);
                }
            } catch (error) {
                console.error('Error handling filter change:', error);
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        console.log('Document ready, initializing CPHT Filter');
        CPHTFilter.init();
    });

})(jQuery);