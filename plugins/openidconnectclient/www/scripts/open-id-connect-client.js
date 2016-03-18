/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

!(function ($) {

    function formatOptionIcon(option) {
        return '<i class="icon-' + option.id + '"></i>';
    }

    function initIconSelector() {
        $(".provider-icon-selector").select2({
            minimumResultsForSearch: -1,
            formatResult           : formatOptionIcon,
            formatSelection        : formatOptionIcon,
            containerCssClass      : 'provider-select2-select',
            dropdownCssClass       : 'provider-select2-dropdown'
        });
    }

    function formatOptionColor(option) {
        return '<span class="' + option.id + '"></span>';
    }

    function initColorSelector() {
        $(".provider-color-selector").select2({
            minimumResultsForSearch: -1,
            formatResult           : formatOptionColor,
            formatSelection        : formatOptionColor,
            containerCssClass      : 'provider-select2-select',
            dropdownCssClass       : 'provider-select2-dropdown'
        });
    }

    function syncPreviewButton() {
        $('.provider-name').keyup(function() {
            $(this).parents('.modal-body').find('.provider-button-preview > a > span').html($(this).val());
        });

        $('.provider-icon-selector').change(function(event) {
            var icon = $(this).parents('.modal-body').find('.provider-button-preview > a > i');
            icon.removeClass();
            icon.addClass('icon-' + event.val);
        });

        $('.provider-color-selector').change(function(event) {
            var button = $(this).parents('.modal-body').find('.provider-button-preview > a');
            button.removeClass();
            button.addClass('btn btn-large provider-button');

            if (event.val) {
                button.addClass('btn-primary ' + event.val);
            }
        });
    }

    $(document).ready(function() {
        initIconSelector();
        initColorSelector();

        syncPreviewButton();
    });

})(window.jQuery);
