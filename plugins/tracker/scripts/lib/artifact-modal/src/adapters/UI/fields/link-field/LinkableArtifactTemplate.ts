/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type {
    HTMLTemplateStringProcessor,
    LinkSelectorItem,
    HTMLTemplateResult,
} from "@tuleap/link-selector";
import type { LinkableArtifact } from "../../../../domain/fields/link-field/LinkableArtifact";
import { getAlreadyLinkedTextTooltip, getAlreadyLinkedInfo } from "../../../../gettext-catalog";

const isLinkableArtifact = (item: unknown): item is LinkableArtifact =>
    typeof item === "object" && item !== null && "id" in item;

export const getLinkableArtifact = (item: unknown): LinkableArtifact | null => {
    if (!isLinkableArtifact(item)) {
        return null;
    }
    return item;
};

export const getLinkableArtifactTemplate = (
    lit_html: typeof HTMLTemplateStringProcessor,
    item: LinkSelectorItem
): HTMLTemplateResult => {
    const artifact = getLinkableArtifact(item.value);
    if (!artifact) {
        return lit_html``;
    }

    const item_classes = `tlp-swatch-${artifact.xref.color} cross-ref-badge link-field-xref-badge`;

    if (item.is_disabled) {
        return lit_html`
            <span class="link-field-item" title="${getAlreadyLinkedTextTooltip()}">
                <span class="${item_classes}">${artifact.xref.ref}</span>
                <span class="link-field-item-title">${artifact.title}</span>
                <span class="link-field-disabled-item-already-linked-info">
                    ${getAlreadyLinkedInfo()}
                </span>
            </span>
        `;
    }

    return lit_html`
        <span class="link-field-item">
            <span class="${item_classes}">${artifact.xref.ref}</span>
            <span class="link-field-item-title">${artifact.title}</span>
        </span>
    `;
};
