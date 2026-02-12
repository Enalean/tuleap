/*
 * Copyright (c) Enalean, 2026-present. All Rights Reserved.
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

import type { LazyboxItem, HTMLTemplateStringProcessor, HTMLTemplateResult } from "@tuleap/lazybox";
import { getOpenStaticValue } from "./open-static-list-value-getter";

export function getTemplateContent(
    html: typeof HTMLTemplateStringProcessor,
    item: LazyboxItem,
): HTMLTemplateResult {
    const item_value = getOpenStaticValue(item.value);
    if (item_value?.value_color !== "") {
        return html`<div class="badge-container">
            <span class="tlp-swatch-${item_value?.value_color} item-colored-element"></span>
            ${item_value?.label}
        </div>`;
    }
    return html`${item_value.label}`;
}
