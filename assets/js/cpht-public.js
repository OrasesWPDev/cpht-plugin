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
            // Cache DOM elements
            this.$filterSelect = $('#cpht-category-filter');
            this.$postsGrid = $('.cpht-grid');
            this.$contentArea = $('.cpht-content-area');
            this.$paginationArea = $('.cpht-pagination');
            this.$wrapper = $('.cpht-posts-wrapper');

            // Only proceed if we have the filter on the page
            if (this.$filterSelect.length === 0) {
                return;
            }

            // Bind events
            this.bindEvents();
        },

        // Bind all necessary events
        bindEvents: function() {
            // Category filter change
            this.$filterSelect.on('change', this.handleFilterChange.bind(this));

            // Handle browser back/forward buttons
            $(window).on('popstate', this.handlePopState.bind(this));

            // Handle pagination clicks for AJAX
            $(document).on('click', '.cpht-pagination a', this.handlePaginationClick.bind(this));
        },

        // Handle filter dropdown change
        handleFilterChange: function(e) {
            var selectedCategory = this.$filterSelect.val();
            // Ensure empty string handling is consistent for "Filter by Category" option
            if (selectedCategory === null || selectedCategory === undefined) {
                selectedCategory = '';
            }
            this.loadPosts(selectedCategory, 1); // Reset to page 1 when changing category
        },

        // Handle browser back/forward buttons
        handlePopState: function(e) {
            if (e.originalEvent && e.originalEvent.state) {
                var state = e.originalEvent.state;
                // Ensure category is defined and provide fallback
                var category = state.category !== undefined ? state.category : '';
                var page = state.page || 1;

                this.loadPosts(category, page, false); // Don't push state again

                // Update filter dropdown to match state
                this.$filterSelect.val(category);
            }
        },

        // Handle pagination clicks
        handlePaginationClick: function(e) {
            e.preventDefault();

            // Extract page number from the URL
            var href = $(e.currentTarget).attr('href');
            var page = this.getParameterByName('paged', href) || this.getParameterByName('page', href) || 1;

            // Get current category
            var category = this.$filterSelect.val() || '';

            // Load the posts for this page
            this.loadPosts(category, page);

            // Scroll to top of posts section
            $('html, body').animate({
                scrollTop: this.$wrapper.offset().top - 50
            }, 300);

            return false;
        },

        // Load posts via AJAX
        loadPosts: function(category, page, updateHistory = true) {
            var self = this;
            var nonce = this.$filterSelect.data('nonce');

            // Show loading state
            this.showLoader();

            // Prepare the data
            var data = {
                action: 'cpht_filter_posts',
                category: category, // Will be empty string for "Filter by Category"
                paged: page,
                columns: this.$postsGrid.attr('class').match(/cpht-columns-(\d)/)[1] || 3,
                nonce: nonce
            };

            // Make the AJAX request
            $.ajax({
                url: cpht_params.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        // Update the posts grid
                        self.$contentArea.html(response.data.content);

                        // Update browser history
                        if (updateHistory) {
                            self.updateHistory(category, page);
                        }
                    } else {
                        console.error('AJAX Error:', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                },
                complete: function() {
                    // Hide loading state
                    self.hideLoader();
                }
            });
        },

        // Update browser history
        updateHistory: function(category, page) {
            var url = window.location.pathname;
            var queryParams = [];

            // Add category to URL if selected
            if (category) {
                queryParams.push('cpht_category=' + encodeURIComponent(category));
            }

            // Add page to URL if not the first page
            if (page > 1) {
                queryParams.push('paged=' + page);
            }

            // Construct the new URL
            if (queryParams.length > 0) {
                url += '?' + queryParams.join('&');
            }

            // Push the new state
            window.history.pushState(
                { category: category, page: page },
                '',
                url
            );
        },

        // Show loading indicator
        showLoader: function() {
            // Remove any existing loader
            this.hideLoader();

            // Add new loader
            this.$contentArea.append('<div class="cpht-loader"><span></span></div>');
            this.$contentArea.addClass('cpht-loading');
        },

        // Hide loading indicator
        hideLoader: function() {
            this.$contentArea.removeClass('cpht-loading');
            $('.cpht-loader').remove();
        },

        // Helper: Get parameter from URL
        getParameterByName: function(name, url) {
            if (!url) url = window.location.href;
            name = name.replace(/[\[\]]/g, '\\$&');
            var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
                results = regex.exec(url);
            if (!results) return null;
            if (!results[2]) return '';
            return decodeURIComponent(results[2].replace(/\+/g, ' '));
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        CPHTFilter.init();
    });

})(jQuery);