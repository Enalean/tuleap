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

// See https://www.ecma-international.org/publications/standards/Ecma-376.htm (at Part 1, Page 1234)

import { Run } from "docx";
import type { PageRefOptions } from "./pageref-field-instruction";
import { PageRefFieldInstruction } from "./pageref-field-instruction";
import { ComplexFieldCharacter } from "../base-elements";

export class PageRef extends Run {
    constructor(bookmark_id: string, options: PageRefOptions = {}) {
        super({
            children: [
                new ComplexFieldCharacter("begin", true),
                new PageRefFieldInstruction(bookmark_id, options),
                new ComplexFieldCharacter("end"),
            ],
        });
    }
}
