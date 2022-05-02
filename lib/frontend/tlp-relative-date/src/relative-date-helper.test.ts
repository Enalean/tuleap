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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { relativeDatePlacement, relativeDatePreference } from "./relative-date-helper";

describe("relative-date helpers", () => {
    it("tests relativeDatePreference", () => {
        expect(relativeDatePreference("absolute_first-relative_shown")).toBe("absolute");
        expect(relativeDatePreference("absolute_first-relative_tooltip")).toBe("absolute");
        expect(relativeDatePreference("relative_first-absolute_shown")).toBe("relative");
        expect(relativeDatePreference("relative_first-absolute_tooltip")).toBe("relative");
    });

    it("tests relativeDatePlacement", () => {
        expect(relativeDatePlacement("absolute_first-relative_shown", "top")).toBe("top");
        expect(relativeDatePlacement("absolute_first-relative_tooltip", "top")).toBe("tooltip");
        expect(relativeDatePlacement("relative_first-absolute_shown", "top")).toBe("top");
        expect(relativeDatePlacement("relative_first-absolute_tooltip", "top")).toBe("tooltip");

        expect(relativeDatePlacement("absolute_first-relative_shown", "right")).toBe("right");
        expect(relativeDatePlacement("absolute_first-relative_tooltip", "right")).toBe("tooltip");
        expect(relativeDatePlacement("relative_first-absolute_shown", "right")).toBe("right");
        expect(relativeDatePlacement("relative_first-absolute_tooltip", "right")).toBe("tooltip");
    });
});
