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
import { TextRun } from "docx";

export function transformTextWithNewlines(
    content: string,
    properties: IRunPropertiesOptions,
): TextRun[] {
    return content.split("\n").map((text: string, index: number) => {
        const is_first_line = index === 0;
        return new TextRun({ text, break: is_first_line ? 0 : 1, ...properties });
    });
}
