/**
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

import { createDatePicker, getLocaleWithDefault } from "@tuleap/tlp-date-picker";
import { initDateTimeFields } from "./date-time-fields";

jest.mock("@tuleap/tlp-date-picker", () => ({
    createDatePicker: jest.fn(),
    getLocaleWithDefault: jest.fn(),
}));

const LOCALE = "fr_FR";

describe("date-time-fields", () => {
    beforeEach(() => {
        document.body.innerHTML = "";
        jest.clearAllMocks();
        jest.mocked(getLocaleWithDefault).mockReturnValue(LOCALE);
    });

    it("should not initializes datetime picker for non input elements", () => {
        const element = document.createElement("div");
        element.classList.add("datetime-picker");
        document.body.appendChild(element);

        initDateTimeFields();

        expect(createDatePicker).not.toHaveBeenCalled();
    });

    it("should initializes datetime picker only on focus", () => {
        const input_datetime = document.createElement("input");
        input_datetime.classList.add("datetime-picker");
        document.body.appendChild(input_datetime);

        initDateTimeFields();

        expect(createDatePicker).not.toHaveBeenCalled();

        input_datetime.dispatchEvent(new Event("focus"));
        expect(createDatePicker).toHaveBeenCalledTimes(1);
    });
});
