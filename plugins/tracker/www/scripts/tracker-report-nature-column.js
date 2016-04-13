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

(function ($, window, document) {
    var popovers = [];

    function initNatureColumnEditor() {
        $('body').popover({
            html: true,
            trigger: 'click',
            container: 'body',
            selector: '.nature-column-editor',
            placement: 'bottom',
            title: codendi.getText('nature_column_editor', 'title'),
            content: getPopoverContent()
        });
    }

    function cancelNatureColumnEditor() {
        $('.nature-column-editor').popover('destroy');
    }

    function getPopoverContent() {
        var content = '<div class="nature-column-popover">';
        content += '<form action="#" method="post">';

        content += '<p>' + codendi.getText('nature_column_editor', 'how_to') + '</p>';

        content += '<label for="nature-column-editor">' + codendi.getText('nature_column_editor', 'column_format_label') + '</label>';
        content += '<input type="text" id="nature-column-editor" placeholder="' + codendi.getText('nature_column_editor', 'column_format_placeholder') + '">';

        content += '<div class="nature-column-popover-actions">';
        content += '<button type="button" class="btn cancel-nature-column-editor">' + codendi.getText('nature_column_editor', 'cancel') + '</button>';
        content += '<button type="button" class="btn btn-primary">' + codendi.getText('nature_column_editor', 'save') + '</button>';
        content += '</div>';

        content += '</form>';
        content += '</div>';

        return content;
    }

    $(function () {
        initNatureColumnEditor();

        $('body').on('click', function(event) {
            if ($(event.target).parents('.nature-column-editor').length === 0 && $(event.target).parents('.popover.in').length === 0) {
                cancelNatureColumnEditor();
                return;
            }

            if ($(event.target).parents('.nature-column-editor').length === 1) {
                var clicked = $(event.target).parents('.nature-column-editor')[0];
                $('.nature-column-editor').each(function (index, element) {
                    if (element !== clicked) {
                        $(element).popover('destroy');
                    }
                })
            }
        });

        $('body').on('click', '.cancel-nature-column-editor', cancelNatureColumnEditor);
    });

}(jQuery, window, document));
