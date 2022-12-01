/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import CodeMirror from "codemirror";

export default CodeMirrorHelperService;

CodeMirrorHelperService.$inject = ["gettextCatalog"];

function CodeMirrorHelperService(gettextCatalog) {
    const self = this;
    Object.assign(self, {
        collapseCommonSectionsSideBySide,
        collapseCommonSectionsUnidiff,
    });

    function getCollapsedLabelElement(section) {
        const collapsed_label = document.createElement("span");
        collapsed_label.className =
            "pull-request-file-diff-section-collapsed tlp-badge-primary tlp-badge-outline";

        collapsed_label.appendChild(
            document.createTextNode(
                gettextCatalog.getPlural(
                    section.end - section.start + 1,
                    "... Skipped 1 common line",
                    "... Skipped {{ $count }} common lines",
                    {}
                )
            )
        );
        return collapsed_label;
    }

    function collapseCommonSectionsUnidiff(unidiff_codemirror, sections) {
        sections.forEach((section) => appendCollapsedSectionLabel(unidiff_codemirror, section));
    }

    function appendCollapsedSectionLabel(codemirror, section) {
        let last_line_length = 0;

        if (codemirror.getLine(section.end)) {
            last_line_length = codemirror.getLine(section.end).length;
        }

        const collapsed_label = getCollapsedLabelElement(section);

        const marker = codemirror.markText(
            CodeMirror.Pos(section.start, 0),
            CodeMirror.Pos(section.end, last_line_length),
            {
                replacedWith: collapsed_label,
            }
        );

        collapsed_label.addEventListener("click", () => marker.clear());
    }

    function synchronizeExpandCollapsedSectionsSideBySide(left_codemirror, right_codemirror) {
        const left_labels = left_codemirror.getAllMarks();
        const right_labels = right_codemirror.getAllMarks();

        left_labels.forEach((label, index) => {
            label.replacedWith.addEventListener("click", () => right_labels[index].clear());
        });

        right_labels.forEach((label, index) => {
            label.replacedWith.addEventListener("click", () => left_labels[index].clear());
        });
    }

    function collapseCommonSectionsSideBySide(left_codemirror, right_codemirror, sections) {
        sections.forEach((section) => appendCollapsedSectionLabel(left_codemirror, section.left));

        sections.forEach((section) => appendCollapsedSectionLabel(right_codemirror, section.right));

        synchronizeExpandCollapsedSectionsSideBySide(left_codemirror, right_codemirror);
    }
}
