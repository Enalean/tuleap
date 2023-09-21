/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import type { ListPickerItem } from "../../type";
import { convertBadColorHexToRGB, isColorBad } from "../color-helper";
import type { TemplateResult } from "lit-html";
import { html } from "lit-html";
import { ListItemMapBuilder } from "../../items/ListItemMapBuilder";
import { classMap } from "lit-html/directives/class-map.js";
import { styleMap } from "lit-html/directives/style-map.js";

export function createItemBadgeTemplate(
    event_listener: (event: Event) => void,
    list_item: ListPickerItem,
): TemplateResult {
    const badge_color = list_item.target_option.dataset.colorValue;
    if (badge_color) {
        if (!isColorBad(badge_color)) {
            return createColoredBadge(badge_color, event_listener, list_item);
        }
        return createLegacyColoredBadge(badge_color, event_listener, list_item);
    }

    const badge_classes = {
        "list-picker-badge": true,
        "list-picker-badge-custom":
            list_item.template.strings.toString() !==
            ListItemMapBuilder.buildDefaultTemplateForItem(list_item.label).strings.toString(),
    };

    const badge_template = html`
        <span class="${classMap(badge_classes)}" title="${list_item.label}">
            <span
                role="presentation"
                class="list-picker-value-remove-button"
                @pointerup=${event_listener}
            >
                ×
            </span>
            ${list_item.template}
        </span>
    `;

    return badge_template;
}

function createColoredBadge(
    badge_color: string,
    event_listener: (event: Event) => void,
    list_item: ListPickerItem,
): TemplateResult {
    return html`
        <span class="list-picker-badge list-picker-badge-${badge_color}" title="${list_item.label}">
            <span
                role="presentation"
                class="list-picker-value-remove-button"
                @pointerup=${event_listener}
            >
                ×
            </span>
            ${list_item.label}
        </span>
    `;
}

function createLegacyColoredBadge(
    badge_color: string,
    event_listener: (event: Event) => void,
    list_item: ListPickerItem,
): TemplateResult {
    const rgb_legacy_color = convertBadColorHexToRGB(badge_color);
    const badge_legacy_color_style = {
        border: `1px solid rgba(${rgb_legacy_color?.red}, ${rgb_legacy_color?.green}, ${rgb_legacy_color?.blue}, .6)`,
        background: `rgba(${rgb_legacy_color?.red}, ${rgb_legacy_color?.green}, ${rgb_legacy_color?.blue}, .1)`,
        color: badge_color,
    };
    return html`
        <span
            class="list-picker-badge"
            style="${styleMap(badge_legacy_color_style)}"
            title="${list_item.label}"
        >
            <span
                role="presentation"
                class="list-picker-value-remove-button"
                @pointerup=${event_listener}
            >
                ×
            </span>
            ${list_item.label}
        </span>
    `;
}
