/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { markPotentiallyDangerousBidirectionalUnicodeText } from "./bidirectional-unicode-text";

describe("bidirectional-unicode-text", () => {
    it("marks potentially dangerous bidirectional characters", () => {
        const result = markPotentiallyDangerousBidirectionalUnicodeText("A\u202aB\u202b");

        expect(result).toBe(
            'A<span class="syntax-highlight-invisible-char" dir="ltr" title="\\u202a">\u202a</span>B<span class="syntax-highlight-invisible-char" dir="ltr" title="\\u202b">\u202b</span>',
        );
    });
});
