/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

/* global $$:readonly, Ajax:readonly, tuleap:readonly */

(function () {
    $$("td.artifacts-folders-rollup > a.direct-link-to-artifact").each(function (link) {
        initRollupViewOfLink(link, 1);
    });

    function initRollupViewOfLink(link, depth) {
        var cell = link.parentNode,
            row = cell.parentNode,
            row_id = row.identify(),
            next_row = row.nextElementSibling,
            tbody = row.parentNode,
            icon = document.createElement("i"),
            artifact_id = link.dataset.artifactId;

        cell.classList.add("artifacts-folders-rollup");
        icon.classList.add("artifacts-folders-rollup-icon", "fa");
        cell.insertBefore(icon, link);

        loadChildrenRecursively();

        function loadChildrenRecursively() {
            // eslint-disable-next-line no-new
            new Ajax.Request("/plugins/artifactsfolders/", {
                method: "GET",
                parameters: {
                    action: "get-children",
                    aid: artifact_id,
                },
                onSuccess: function (transport) {
                    var children = transport.responseJSON;
                    if (children.length) {
                        injectChildrenInTable(children);
                    }
                },
            });
        }

        function injectChildrenInTable(children_to_inject) {
            icon.classList.add("fa-caret-right");

            icon.addEventListener("click", function () {
                simpleExpandCollapse(this, children_to_inject);
            });
        }

        function simpleExpandCollapse(icon_clicked, children_to_inject) {
            icon_clicked.classList.toggle("fa-caret-right");
            icon_clicked.classList.toggle("fa-caret-down");

            var subrows = icon_clicked
                .closest("tbody")
                .querySelectorAll('[data-child-of="' + icon_clicked.closest("tr").id + '"]');

            if (subrows.length <= 0) {
                subrows = children_to_inject.map(injectChildInTable);
                subrows.forEach(function (row) {
                    initRollupViewOfLink(row.querySelector("a.direct-link-to-artifact"), depth + 1);
                });
            } else {
                if (icon_clicked.classList.contains("fa-caret-right")) {
                    subrows.forEach(collapseRow);
                } else {
                    subrows.forEach(expandRow);
                }
            }
        }

        function injectChildInTable(child) {
            var additional_row = document.createElement("tr");

            additional_row.dataset.childOf = row_id;
            /* eslint-disable no-multi-str,no-unsanitized/property */
            additional_row.innerHTML =
                ' \
                    <td class="artifacts-folders-rollup" style="padding-left: ' +
                depth * 20 +
                'px;"> \
                        <a class="direct-link-to-artifact" \
                            href="' +
                tuleap.escaper.html(child.html_url) +
                '" \
                            data-artifact-id="' +
                tuleap.escaper.html(child.id) +
                '" \
                        >' +
                tuleap.escaper.html(child.xref) +
                "</a> \
                    </td> \
                    <td>" +
                formatFolders(child.folder_hierarchy) +
                "</td> \
                    <td>" +
                tuleap.escaper.html(child.title) +
                "</td> \
                    <td>" +
                tuleap.escaper.html(child.status || "") +
                "</td> \
                    <td>" +
                tuleap.escaper.html(child.last_modified_date) +
                "</td> \
                    <td>" +
                formatUser(child.submitter) +
                "</td> \
                    <td>" +
                child.assignees.map(formatUser).join(", ") +
                "</td>";
            /* eslint-enable no-multi-str,no-unsanitized/property */

            if (next_row) {
                tbody.insertBefore(additional_row, next_row);
            } else {
                tbody.appendChild(additional_row);
            }

            return additional_row;
        }
    }

    function formatFolders(folder_hierarchy) {
        var html = "";

        folder_hierarchy.forEach(function (folder) {
            /* eslint-disable no-multi-str */
            html +=
                '<i class="fa fa-angle-right"></i> \
                <a class="direct-link-to-artifact" \
                href="' +
                tuleap.escaper.html(folder.url) +
                '&view=artifactsfolders">' +
                tuleap.escaper.html(folder.title) +
                "</a> ";
            /* eslint-enable no-multi-str */
        });

        return html;
    }

    function collapseRow(row) {
        var subrows = row.parentNode.querySelectorAll('[data-child-of="' + row.id + '"]');
        row.style.display = "none";
        [].forEach.call(subrows, collapseRow);
    }

    function expandRow(row) {
        var tr_rollup_view = row.querySelector(".artifacts-folders-rollup");
        var icon_down = tr_rollup_view.querySelector(".fa-caret-down");
        var icon_right = tr_rollup_view.querySelector(".fa-caret-right");

        if (icon_down && !icon_right) {
            var subrows = row.parentNode.querySelectorAll('[data-child-of="' + row.id + '"]');
            row.style.display = "table-row";
            [].forEach.call(subrows, expandRow);
        }

        if (!icon_down) {
            row.style.display = "table-row";
        }
    }

    function formatUser(user_json) {
        /* eslint-disable no-multi-str */
        return (
            '<a href="' +
            tuleap.escaper.html(user_json.url) +
            '"> \
                    ' +
            tuleap.escaper.html(user_json.display_name) +
            " \
                </a>"
        );
        /* eslint-enable no-multi-str */
    }
})();
