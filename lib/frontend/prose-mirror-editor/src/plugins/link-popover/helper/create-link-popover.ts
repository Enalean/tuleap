/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
 *
 */

import type { GetText } from "@tuleap/gettext";
import { TAG as LINK_POPOVER_TAG } from "../element/LinkPopoverElement";
import type { LinkPopoverElement } from "../element/LinkPopoverElement";
import { sanitizeURL } from "@tuleap/url-sanitizer";

export function buildLinkPopoverId(editor_id: string): string {
    return `link-popover-${editor_id}`;
}

export function removePopover(doc: Document, editor_id: string): void {
    const existing_menu = doc.getElementById(buildLinkPopoverId(editor_id));
    if (!existing_menu) {
        return;
    }
    existing_menu.remove();
}

const isLinkPopoverElement = (element: HTMLElement): element is LinkPopoverElement & HTMLElement =>
    element.localName === LINK_POPOVER_TAG;

function createBasePopoverElement(
    doc: Document,
    gettext_provider: GetText,
    popover_anchor: HTMLElement,
    editor_id: string,
    link_href: string,
): LinkPopoverElement & HTMLElement {
    const popover = doc.createElement(LINK_POPOVER_TAG);
    if (!isLinkPopoverElement(popover)) {
        throw new Error("Unable to create the popover element :(");
    }

    popover.id = buildLinkPopoverId(editor_id);
    popover.gettext_provider = gettext_provider;
    popover.popover_anchor = popover_anchor;
    popover.buttons = [{ type: "open-link", sanitized_link_href: sanitizeURL(link_href) }];

    return popover;
}

export function insertLinkPopover(
    doc: Document,
    gettext_provider: GetText,
    popover_anchor: HTMLElement,
    editor_id: string,
    link_href: string,
): void {
    const popover = createBasePopoverElement(
        doc,
        gettext_provider,
        popover_anchor,
        editor_id,
        link_href,
    );

    popover.buttons.push({
        type: "copy-to-clipboard",
        value_to_copy: sanitizeURL(link_href),
        copy_value_title: gettext_provider.gettext("Copy link url"),
        value_copied_title: gettext_provider.gettext("Link url has been copied!"),
    });

    doc.body.appendChild(popover);
}

export function insertCrossReferenceLinkPopover(
    doc: Document,
    gettext_provider: GetText,
    popover_anchor: HTMLElement,
    editor_id: string,
    link_href: string,
    cross_ref_text: string,
): void {
    const popover = createBasePopoverElement(
        doc,
        gettext_provider,
        popover_anchor,
        editor_id,
        link_href,
    );

    popover.buttons.push({
        type: "copy-to-clipboard",
        value_to_copy: cross_ref_text,
        copy_value_title: gettext_provider.gettext("Copy Tuleap reference"),
        value_copied_title: gettext_provider.gettext("Tuleap reference has been copied!"),
    });

    doc.body.appendChild(popover);
}
