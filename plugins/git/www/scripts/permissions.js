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

(function ($) {

    function bindAddPermission() {
        $('.add-fine-grained-permission').click(function(event) {
            event.preventDefault();

            $(this).blur();

            var type                 = $(this).attr('data-type'),
                table                = $('.git-fine-grained-permissions-' + type),
                tbody                = table.children('tbody'),
                permission_template  = $('#add-fine-grained-permission-template'),
                index                = getNewIndex(type)

                new_row              = '',
                input_tag            = '<input type="text" name="add-' + type + '-name['+index+']" placeholder="' + codendi.getText('git', 'add_' + type + '_permission_placeholder') + '">',
                write_permission_tag = permission_template
                    .clone()
                    .removeAttr('id')
                    .attr('name', 'add-' + type + '-write['+index+'][]')
                    [0].outerHTML,
                rewind_permission_tag = permission_template
                    .clone()
                    .removeAttr('id')
                    .attr('name', 'add-' + type + '-rewind['+index+'][]')
                    [0].outerHTML;

            new_row += '<tr>';
            new_row += '<td>' + input_tag + '</td>';
            new_row += '<td>' + write_permission_tag + '</td>';
            new_row += '<td>' + rewind_permission_tag + '</td>';
            new_row += '</tr>';

            tbody.append($(new_row));
        });
    }

    function getNewIndex(type) {
        return $('input[name^="add-'+type+'-name"]').length;
    }

    $(function() {
        bindAddPermission();
    });

}(jQuery));
