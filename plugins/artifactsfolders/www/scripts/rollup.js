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

(function () {

    $$('td.artifacts-folders-rollup > a.direct-link-to-artifact').each(
        function (link) {
            initRollupViewOfLink(link, 1);
        }
    );

    function initRollupViewOfLink(link, depth) {
        var cell        = link.parentNode,
            row         = cell.parentNode,
            row_id      = row.identify(),
            next_row    = row.nextElementSibling,
            tbody       = row.parentNode,
            icon        = document.createElement('i'),
            artifact_id = link.dataset.artifactId,
            limit       = 50,
            children    = [];

        cell.classList.add('artifacts-folders-rollup');
        icon.classList.add('artifacts-folders-rollup-icon');
        cell.insertBefore(icon, link);

        loadChildrenRecursively(0);

        function loadChildrenRecursively(offset) {
            new Ajax.Request(
                '/api/artifacts/'+ artifact_id +'/linked_artifacts',
                {
                    method: 'GET',
                    requestHeaders: {
                        Accept: 'application/json'
                    },
                    parameters: {
                        direction: 'forward',
                        nature   : '_is_child',
                        offset   : offset,
                        limit    : limit
                    },
                    onSuccess: function (transport) {
                        children = children.concat(transport.responseJSON.collection);

                        if (offset + limit < transport.getResponseHeader('X-Pagination-Size')) {
                            loadChildrenRecursively(offset + limit);
                        } else if (children.length > 0) {
                            injectChildrenInTable(children);
                        }
                    }
                }
            );
        }

        function injectChildrenInTable(children_to_inject) {
            icon.classList.add('icon-caret-right');

            icon.addEventListener('click', function () {
                simpleExpandCollapse(this, children_to_inject);
            });
        }

        function simpleExpandCollapse(icon_clicked, children_to_inject) {
            icon_clicked.classList.toggle('icon-caret-right');
            icon_clicked.classList.toggle('icon-caret-down');

            var subrows = icon_clicked.closest('tbody')
                .querySelectorAll('[data-child-of="'+ icon_clicked.closest('tr').id +'"]');

            if (subrows.length <= 0) {
                subrows = children_to_inject.map(injectChildInTable);
                subrows.forEach(function (row) {
                    initRollupViewOfLink(row.querySelector('a.direct-link-to-artifact'), depth + 1);
                });
            } else {
                if (icon_clicked.classList.contains('icon-caret-right')) {
                    subrows.forEach(collapseRow);
                } else {
                    subrows.forEach(expandRow);
                }
            }
        }

        function injectChildInTable(child) {
            var additional_row = document.createElement('tr'),
                modified_date  = new Date(child.last_modified_date);

            additional_row.dataset.childOf = row_id;
            additional_row.innerHTML = ' \
                    <td class="artifacts-folders-rollup" style="padding-left: '+ (depth * 20) +'px;"> \
                        <a class="direct-link-to-artifact" \
                            href="'+ child.html_url +'" \
                            data-artifact-id="'+ child.id +'" \
                        >'+ child.xref +'</a> \
                    </td> \
                    <td>'+ child.project.label +'</td> \
                    <td>'+ child.tracker.label +'</td> \
                    <td>'+ child.title +'</td> \
                    <td>'+ (child.status || '') +'</td> \
                    <td>'+ formatDate(modified_date) +'</td> \
                    <td>'+ formatUser(child.submitted_by_user) +'</td> \
                    <td>'+ child.assignees.map(formatUser).join(', ') +'</td> \
                    <td>?</td>';

            if (next_row) {
                tbody.insertBefore(additional_row, next_row);
            } else {
                tbody.appendChild(additional_row);
            }

            return additional_row;
        }
    }

    function collapseRow(row) {
        var subrows = row.parentNode.querySelectorAll('[data-child-of="'+ row.id +'"]');
        row.style.display = 'none';
        [].forEach.call(subrows, collapseRow);
    }

    function expandRow(row) {
        var tr_rollup_view = row.querySelector('.artifacts-folders-rollup');
        var icon_down      = tr_rollup_view.querySelector('.icon-caret-down');
        var icon_right     = tr_rollup_view.querySelector('.icon-caret-right');

        if (icon_down && ! icon_right) {
            var subrows = row.parentNode.querySelectorAll('[data-child-of="'+ row.id +'"]');
            row.style.display = 'table-row';
            [].forEach.call(subrows, expandRow);
        }

        if (! icon_down) {
            row.style.display = 'table-row';
        }
    }

    function formatDate(date) {
        return date.getFullYear()
            +'-'+ ("0" + date.getMonth()).substr(-2)
            +'-'+ ("0" + date.getDay()).substr(-2)
            +' '+ ("0" + date.getHours()).substr(-2)
            +':'+ ("0" + date.getMinutes()).substr(-2);
    }

    function formatUser(user_json) {
        return '<a href="'+ user_json.user_url +'"> \
                    '+ user_json.display_name +' \
                </a>';
    }
})();