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

import type { TemplateResult } from "lit-html";
import { html } from "lit-html";
import { convertBadColorHexToRGB, isColorBad } from "../color-helper";
import { getOptionsLabel } from "../option-label-helper";
import { styleMap } from "lit-html/directives/style-map.js";

export function retrievePredefinedTemplate(option: HTMLOptionElement): TemplateResult {
    const option_label = getOptionsLabel(option);

    const avatar_url = option.dataset.avatarUrl;
    if (avatar_url && avatar_url !== "") {
        return html`
            <span class="list-picker-avatar"><img src="${avatar_url}" loading="lazy" /></span>
            ${option_label}
        `;
    }

    const color_value = option.dataset.colorValue;
    if (color_value && color_value !== "") {
        return getColoredTemplate(color_value, option_label);
    }
    throw new Error("The predefined template does not exist");
}

function getColoredTemplate(color_value: string, option_label: string): TemplateResult {
    const is_color_bad = isColorBad(color_value);
    const rgb_color_legacy = convertBadColorHexToRGB(color_value);
    if (!is_color_bad && rgb_color_legacy === null) {
        return html`
            <span class="list-picker-option-colored-label-container">
                <span class="tlp-swatch-${color_value} list-picker-circular-color"></span>
                ${option_label}
            </span>
        `;
    }

    if (is_color_bad && rgb_color_legacy !== null) {
        const legacy_color_styles = {
            background: `rgba(${rgb_color_legacy.red}, ${rgb_color_legacy.green}, ${rgb_color_legacy.blue}, .6)`,
            border: `3px solid rgba(${rgb_color_legacy.red}, ${rgb_color_legacy.green}, ${rgb_color_legacy.blue})`,
            color: `${color_value}`,
        };

        return html`
            <span class="list-picker-option-colored-label-container">
                <span
                    class="list-picker-circular-legacy-color"
                    style="${styleMap(legacy_color_styles)}"
                ></span>
                ${option_label}
            </span>
        `;
    }
    throw new Error("The colored template cannot be retrieved");
}

export function hasOptionPredefinedTemplate(option: HTMLOptionElement): boolean {
    return option.dataset.avatarUrl !== undefined || option.dataset.colorValue !== undefined;
}
