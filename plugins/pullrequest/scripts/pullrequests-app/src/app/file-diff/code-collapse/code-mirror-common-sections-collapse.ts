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

import type { Editor } from "codemirror";
import type {
    CollapsibleSection,
    SynchronizedCollapsibleSections,
} from "./collaspible-code-sections-builder";
import { getCollapsibleSectionLabel } from "../../gettext-catalog";

export function collapseCommonSectionsSideBySide(
    doc: Document,
    left_codemirror: Editor,
    right_codemirror: Editor,
    sections: readonly SynchronizedCollapsibleSections[],
): void {
    sections.forEach((section) => appendCollapsedSectionLabel(doc, left_codemirror, section.left));
    sections.forEach((section) =>
        appendCollapsedSectionLabel(doc, right_codemirror, section.right),
    );

    synchronizeExpandCollapsedSectionsSideBySide(left_codemirror, right_codemirror);
}
export function collapseCommonSectionsUnidiff(
    doc: Document,
    unidiff_codemirror: Editor,
    sections: readonly CollapsibleSection[],
): void {
    sections.forEach((section) => appendCollapsedSectionLabel(doc, unidiff_codemirror, section));
}

function getCollapsedLabelElement(doc: Document, section: CollapsibleSection): HTMLSpanElement {
    const nb_lines = section.end - section.start + 1;
    const collapsed_label = doc.createElement("span");
    collapsed_label.className =
        "pull-request-file-diff-section-collapsed tlp-badge-primary tlp-badge-outline";

    collapsed_label.appendChild(doc.createTextNode(getCollapsibleSectionLabel(nb_lines)));
    return collapsed_label;
}

function appendCollapsedSectionLabel(
    doc: Document,
    codemirror: Editor,
    section: CollapsibleSection,
): void {
    const last_line = codemirror.getLine(section.end);
    const collapsed_label = getCollapsedLabelElement(doc, section);

    const marker = codemirror.markText(
        { line: section.start, ch: 0 },
        { line: section.end, ch: last_line ? last_line.length : 0 },
        {
            replacedWith: collapsed_label,
        },
    );

    collapsed_label.addEventListener("click", () => marker.clear());
}

function synchronizeExpandCollapsedSectionsSideBySide(
    left_codemirror: Editor,
    right_codemirror: Editor,
): void {
    const left_labels = left_codemirror.getAllMarks();
    const right_labels = right_codemirror.getAllMarks();

    left_labels.forEach((label, index) => {
        label.replacedWith?.addEventListener("click", () => right_labels[index].clear());
    });

    right_labels.forEach((label, index) => {
        label.replacedWith?.addEventListener("click", () => left_labels[index].clear());
    });
}
