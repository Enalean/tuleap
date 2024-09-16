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
import type { ExtractLinkUrl } from "./LinkUrlExtractor";
import {
    insertCrossReferenceLinkPopover,
    insertLinkPopover,
    removePopover,
} from "./create-link-popover";
import type { CheckEmptySelection } from "./EmptySelectionChecker";

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
    extract_regular_link_url: ExtractLinkUrl,
): InsertLinkPopover => ({
    insertPopover(position: number): boolean {
        removePopover(doc, editor_id);

        if (!check_empty_selection.isSelectionEmpty()) {
            return false;
        }

        const popover_anchor = find_node.findNodeAtGivenPosition(position).parentElement;
        if (!popover_anchor) {
            return false;
        }

        const is_cross_ref_link =
            detect_cross_reference.isCrossReferenceHTMLElement(popover_anchor);
        const url = is_cross_ref_link
            ? extract_cross_reference_url.extractUrl(popover_anchor)
            : extract_regular_link_url.extractLinkUrl(position);

        if (url.length === 0) {
            return false;
        }

        if (is_cross_ref_link) {
            insertCrossReferenceLinkPopover(
                doc,
                gettext_provider,
                popover_anchor,
                editor_id,
                url,
                popover_anchor.textContent ?? "",
            );

            return true;
        }

        insertLinkPopover(doc, gettext_provider, popover_anchor, editor_id, url);

        return true;
    },
});
