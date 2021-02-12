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

import type { LegacyColorRGB } from "../type";

export function isColorBad(color_value: string): boolean {
    return color_value.match(/^#[a-f0-9]{6}$/i) !== null;
}

export function convertBadColorHexToRGB(hex_color_value: string): LegacyColorRGB | null {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex_color_value);
    return result
        ? {
              red: parseInt(result[1], 16),
              green: parseInt(result[2], 16),
              blue: parseInt(result[3], 16),
          }
        : null;
}
