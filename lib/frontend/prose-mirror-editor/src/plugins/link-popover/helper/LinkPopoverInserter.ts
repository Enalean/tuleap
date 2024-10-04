/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import type { GetText } from "@tuleap/gettext";
import type { FindDOMNodeAtPosition } from "./DOMNodeAtPositionFinder";
import type { DetectCrossReferenceHTMLElement } from "./CrossReferenceNodeDetector";
import type { ExtractCrossReferenceUrl } from "./CrossReferenceUrlExtractor";
import type { ExtractLinkProperties } from "../../../helpers/LinkPropertiesExtractor";
import {
    insertCrossReferenceLinkPopover,
    insertLinkPopover,
    removePopover,
} from "./create-link-popover";
import type { CheckEmptySelection } from "./EmptySelectionChecker";
import type { BuildRemoveLinkCallback } from "./RemoveLinkCallbackBuilder";
import type { BuildEditLinkCallback } from "./EditLinkCallbackBuilder";
import type { BuildEditCrossReferenceCallback } from "./EditCrossReferenceCallbackBuilder";

type InsertLinkPopover = {
    insertPopover(position: number): boolean;
};

export const LinkPopoverInserter = (
    doc: Document,
    gettext_provider: GetText,
    editor_id: string,
    check_empty_selection: CheckEmptySelection,
    find_node: FindDOMNodeAtPosition,
    detect_cross_reference: DetectCrossReferenceHTMLElement,
    extract_cross_reference_url: ExtractCrossReferenceUrl,
    extract_regular_link_url: ExtractLinkProperties,
    build_remove_link_callback: BuildRemoveLinkCallback,
    build_edit_link_callback: BuildEditLinkCallback,
    build_edit_cross_reference_callback: BuildEditCrossReferenceCallback,
): InsertLinkPopover => {
    const insertPopoverForCrossReference = (
        popover_anchor: HTMLElement,
        position: number,
    ): boolean => {
        const url = extract_cross_reference_url.extractUrl(popover_anchor);
        if (!url) {
            return false;
        }

        insertCrossReferenceLinkPopover(
            doc,
            gettext_provider,
            popover_anchor,
            editor_id,
            {
                href: extract_cross_reference_url.extractUrl(popover_anchor),
                title: popover_anchor.textContent?.trim() ?? "",
            },
            build_edit_cross_reference_callback.build(doc, editor_id, position),
        );

        return true;
    };

    const insertPopoverForRegularLink = (
        popover_anchor: HTMLElement,
        position: number,
    ): boolean => {
        const link = extract_regular_link_url.extractLinkProperties(position);
        if (!link) {
            return false;
        }

        insertLinkPopover(
            doc,
            gettext_provider,
            popover_anchor,
            editor_id,
            link,
            build_remove_link_callback.build(doc, editor_id),
            build_edit_link_callback.build(doc, editor_id),
        );

        return true;
    };

    return {
        insertPopover(position: number): boolean {
            removePopover(doc, editor_id);

            if (!check_empty_selection.isSelectionEmpty()) {
                return false;
            }

            const popover_anchor = find_node.findNodeAtGivenPosition(position).parentElement;
            if (!popover_anchor) {
                return false;
            }

            if (detect_cross_reference.isCrossReferenceHTMLElement(popover_anchor)) {
                return insertPopoverForCrossReference(popover_anchor, position);
            }

            return insertPopoverForRegularLink(popover_anchor, position);
        },
    };
};
