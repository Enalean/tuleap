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
import type { LinkProperties } from "../../../types/internal-types";
import { TAG as LINK_POPOVER_TAG } from "../element/LinkPopoverElement";
import type { LinkPopoverElement } from "../element/LinkPopoverElement";
import type { RemoveLinkCallback } from "../element/items/RemoveLinkButtonElement";
import {
    CrossReferenceLinkPopoverButtonsRenderer,
    RegularLinkPopoverButtonsRenderer,
} from "../element/LinkPopoverButtonsRenderers";
import type { EditLinkCallback } from "../element/LinkPopoverEditionFormRenderers";
import {
    CrossReferenceLinkEditionPopoverRenderer,
    RegularLinkEditionPopoverRenderer,
} from "../element/LinkPopoverEditionFormRenderers";
import type { EditCrossReferenceCallback } from "../element/forms/EditCrossReferenceFormElement";

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
    popover_anchor: HTMLElement,
    editor_id: string,
): LinkPopoverElement & HTMLElement {
    const popover = doc.createElement(LINK_POPOVER_TAG);
    if (!isLinkPopoverElement(popover)) {
        throw new Error("Unable to create the popover element :(");
    }

    popover.id = buildLinkPopoverId(editor_id);
    popover.popover_anchor = popover_anchor;

    return popover;
}

export function insertLinkPopover(
    doc: Document,
    gettext_provider: GetText,
    popover_anchor: HTMLElement,
    editor_id: string,
    link: LinkProperties,
    remove_link_callback: RemoveLinkCallback,
    edit_link_callback: EditLinkCallback,
): void {
    const popover = createBasePopoverElement(doc, popover_anchor, editor_id);

    popover.buttons_renderer = RegularLinkPopoverButtonsRenderer(
        gettext_provider,
        link,
        remove_link_callback,
    );
    popover.edition_form_renderer = RegularLinkEditionPopoverRenderer(
        gettext_provider,
        link,
        edit_link_callback,
    );

    doc.body.appendChild(popover);
}

export function insertCrossReferenceLinkPopover(
    doc: Document,
    gettext_provider: GetText,
    popover_anchor: HTMLElement,
    editor_id: string,
    link: LinkProperties,
    edit_cross_reference_callback: EditCrossReferenceCallback,
): void {
    const popover = createBasePopoverElement(doc, popover_anchor, editor_id);

    popover.buttons_renderer = CrossReferenceLinkPopoverButtonsRenderer(gettext_provider, link);
    popover.edition_form_renderer = CrossReferenceLinkEditionPopoverRenderer(
        gettext_provider,
        link,
        edit_cross_reference_callback,
    );

    doc.body.appendChild(popover);
}
