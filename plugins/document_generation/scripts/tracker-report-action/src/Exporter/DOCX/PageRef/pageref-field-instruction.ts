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

import { XmlComponent } from "docx";
import { TextAttributes } from "../base-elements";

export interface PageRefOptions {
    readonly hyperlink?: boolean;
    readonly use_relative_position?: boolean;
}

export class PageRefFieldInstruction extends XmlComponent {
    constructor(bookmark_id: string, options: PageRefOptions = {}) {
        super("w:instrText");
        this.root.push(new TextAttributes({ space: "preserve" }));

        let instruction = `PAGEREF ${bookmark_id}`;

        if (options.hyperlink) {
            instruction = `${instruction} \\h`;
        }
        if (options.use_relative_position) {
            instruction = `${instruction} \\p`;
        }

        this.root.push(instruction);
    }
}
