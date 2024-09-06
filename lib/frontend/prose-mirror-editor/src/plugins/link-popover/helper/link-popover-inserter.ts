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

export function buildLinkPopoverId(editor_id: string): string {
    return `link-popover-${editor_id}`;
}

export function removeLinkPopover(doc: Document, editor_id: string): void {
    const existing_menu = doc.getElementById(buildLinkPopoverId(editor_id));
    if (!existing_menu) {
        return;
    }
    existing_menu.remove();
}

const isLinkPopoverElement = (element: HTMLElement): element is LinkPopoverElement & HTMLElement =>
    element.localName === LINK_POPOVER_TAG;

export function insertLinkPopover(
    doc: Document,
    gettext_provider: GetText,
    popover_anchor: HTMLElement,
    editor_id: string,
    link_href: string,
): void {
    const popover = doc.createElement(LINK_POPOVER_TAG);
    if (!isLinkPopoverElement(popover)) {
        return;
    }

    popover.id = buildLinkPopoverId(editor_id);
    popover.gettext_provider = gettext_provider;
    popover.link_href = link_href;
    popover.popover_anchor = popover_anchor;

    doc.body.appendChild(popover);
}
