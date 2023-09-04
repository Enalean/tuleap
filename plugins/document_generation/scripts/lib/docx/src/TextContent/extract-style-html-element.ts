/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import type { IRunPropertiesOptions } from "docx";

export function extractInlineStyles(
    node: HTMLElement,
    source_style: Readonly<IRunPropertiesOptions>,
): IRunPropertiesOptions {
    const color_rgb = node.style.color.match(
        /^rgba?\((\d{1,3}), (\d{1,3}), (\d{1,3})(?:, [\d.]+)?\)$/,
    );
    if (color_rgb === null) {
        return source_style;
    }

    return {
        ...source_style,
        color: color_rgb
            .slice(1)
            .map((color_value) => parseInt(color_value, 10).toString(16).padStart(2, "0"))
            .join(""),
    };
}
