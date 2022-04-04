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

import { getOptionsLabel } from "./option-label-helper";

describe("option-label-helper", () => {
    describe("getOptionsLabel", () => {
        it("should return the inner text of the option if the option has text", function () {
            const option = document.createElement("option");
            option.innerText = "GR-4 Yaris";
            option.setAttribute("label", "4WD");
            expect(getOptionsLabel(option)).toBe("GR-4 Yaris");
        });

        it("should return the label of the option if the option has no text", function () {
            const option = document.createElement("option");
            option.innerText = "";
            option.setAttribute("label", "4WD");
            expect(getOptionsLabel(option)).toBe("4WD");
        });
    });
});
