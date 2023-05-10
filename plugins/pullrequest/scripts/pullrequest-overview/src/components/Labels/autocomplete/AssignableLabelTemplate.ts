/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
    LazyboxItem,
    HTMLTemplateResult,
    SelectionBadge,
} from "@tuleap/lazybox";
import { createSelectionBadge } from "@tuleap/lazybox";
import type { ProjectLabel } from "@tuleap/plugin-pullrequest-rest-api-types";

export const isAssignableLabel = (item_value: unknown): item_value is ProjectLabel =>
    typeof item_value === "object" && item_value !== null && "id" in item_value;

export const getAssignableLabel = (item_value: unknown): ProjectLabel | null => {
    if (!isAssignableLabel(item_value)) {
        return null;
    }
    return item_value;
};

export const getSelectedLabels = (selected_labels: unknown): ProjectLabel[] => {
    if (!Array.isArray(selected_labels)) {
        return [];
    }
    return selected_labels.filter((label) => getAssignableLabel(label) !== null);
};

export const getAssignableLabelsTemplate = (
    html: typeof HTMLTemplateStringProcessor,
    item: LazyboxItem
): HTMLTemplateResult => {
    const label = getAssignableLabel(item.value);
    if (!label) {
        return html``;
    }

    const badge_classes = {
        [`tlp-badge-${label.color}`]: true,
        "tlp-badge-outline": label.is_outline,
    };

    return html`
        <span class="${badge_classes}">
            <i class="fa-solid fa-tag tlp-badge-icon" aria-hidden="true"></i>
            ${label.label}
        </span>
    `;
};

export const getAssignedLabelTemplate = (item: LazyboxItem): SelectionBadge & HTMLElement => {
    const label = item.value;
    if (!isAssignableLabel(label)) {
        throw new Error("The current LazyboxItem does not seem valid");
    }

    const badge = createSelectionBadge(document);

    badge.color = label.color;
    badge.outline = label.is_outline;

    const badge_label = document.createTextNode(label.label);
    const icon = document.createElement("i");

    icon.classList.add("fa-solid", "fa-tag", "tlp-badge-icon");
    icon.ariaHidden = "true";

    badge.append(icon, badge_label);

    return badge;
};
