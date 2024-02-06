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

import type { HTMLTemplateStringProcessor, HTMLTemplateResult, LazyboxItem } from "@tuleap/lazybox";
import { isLabel } from "./LabelsSelectorEntry";

export const LabelsTemplatingCallback = (
    html: typeof HTMLTemplateStringProcessor,
    item: LazyboxItem,
): HTMLTemplateResult => {
    if (!isLabel(item.value)) {
        return html``;
    }

    const badge_classes = {
        [`tlp-badge-${item.value.color}`]: true,
        "tlp-badge-outline": item.value.is_outline,
    };

    return html`
        <span class="${badge_classes}" data-test="pull-request-label"
            ><i class="fa-solid fa-tag tlp-badge-icon"></i>${item.value.label}</span
        >
    `;
};
