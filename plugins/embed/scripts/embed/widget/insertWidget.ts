/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

export default function insertWidget(embeddable_link: Element, widget: Element): void {
    if (!embeddable_link.ownerDocument) {
        throw Error(
            "Embeddable link does not have a top-level document. Perhaps it is a document itself?",
        );
    }

    const block_containers = [
        "div",
        "p",
        "li",
        "h1",
        "h2",
        "h3",
        "h4",
        "h5",
        "h6",
        "td",
        "pre",
        "dd",
        "blockquote",
        "form",
        "footer",
        "fieldset",
        "article",
        "address",
        "section",
        "main",
        "body",
    ];
    let parent: HTMLElement | null = embeddable_link.parentElement;
    while (parent !== null && !block_containers.includes(parent.tagName.toLowerCase())) {
        parent = parent.parentElement;
    }
    if (!parent || !parent.parentElement) {
        return;
    }

    if (
        ["div", "p", "pre", "h1", "h2", "h3", "h4", "h5", "h6"].includes(
            parent.tagName.toLowerCase(),
        )
    ) {
        parent.parentElement.insertBefore(widget, parent.nextSibling);
    } else {
        parent.appendChild(embeddable_link.ownerDocument.createElement("br"));
        parent.appendChild(widget);
    }
}
