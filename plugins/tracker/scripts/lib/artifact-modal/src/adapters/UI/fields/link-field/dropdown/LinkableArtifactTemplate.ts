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

import type { HTMLTemplateStringProcessor, LazyboxItem, HTMLTemplateResult } from "@tuleap/lazybox";
import type {
    LinkableArtifact,
    Status,
} from "../../../../../domain/fields/link-field/LinkableArtifact";
import { getAlreadyLinkedTextTooltip, getAlreadyLinkedInfo } from "../../../../../gettext-catalog";
import { Option } from "@tuleap/option";

const isLinkableArtifact = (item: unknown): item is LinkableArtifact =>
    typeof item === "object" && item !== null && "id" in item;

export const getLinkableArtifact = (item: unknown): Option<LinkableArtifact> => {
    if (!isLinkableArtifact(item)) {
        return Option.nothing();
    }
    return Option.fromValue(item);
};

export const getStatusClasses = (status: Status): string[] => {
    if (!status.color) {
        return ["link-field-item-status", "tlp-badge-outline", "tlp-badge-secondary"];
    }
    return ["link-field-item-status", "tlp-badge-outline", `tlp-badge-${status.color}`];
};

export const getLinkableArtifactTemplate = (
    html: typeof HTMLTemplateStringProcessor,
    item: LazyboxItem
): HTMLTemplateResult =>
    getLinkableArtifact(item.value).mapOr((artifact) => {
        const item_classes = [
            `tlp-swatch-${artifact.xref.color}`,
            "cross-ref-badge",
            "link-field-item-xref-badge",
        ];

        if (item.is_disabled) {
            return html`<span class="link-field-item" title="${getAlreadyLinkedTextTooltip()}">
                <span class="${item_classes}">${artifact.xref.ref}</span>
                <span class="link-field-item-title">${artifact.title}</span>
                <span class="link-field-disabled-item-already-linked-info"
                    >${getAlreadyLinkedInfo()}</span
                >
                ${artifact.status &&
                html`<span class="${getStatusClasses(artifact.status)}"
                    >${artifact.status.value}</span
                >`}
                <span class="link-field-item-project">${artifact.project.label}</span>
            </span>`;
        }

        return html`<span class="link-field-item">
            <span class="${item_classes}">${artifact.xref.ref}</span>
            <span class="link-field-item-title">${artifact.title}</span>
            ${artifact.status &&
            html`<span class="${getStatusClasses(artifact.status)}"
                >${artifact.status.value}</span
            >`}
            <span class="link-field-item-project">${artifact.project.label}</span>
        </span>`;
    }, html``);
