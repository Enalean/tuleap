/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import * as tlp_date_picker from "@tuleap/tlp-date-picker";
import { DatePickerInitializer } from "./DatePickerInitializer";

describe("DatePickerInitializer", () => {
    it("Given an input element, Then it should init a TLP datePicker on it", () => {
        const datePickerSpy = jest.spyOn(tlp_date_picker, "datePicker");
        const input = document.implementation.createHTMLDocument().createElement("input");
        const initializer = DatePickerInitializer();

        initializer.initDatePicker(input);

        expect(datePickerSpy).toHaveBeenCalledWith(input);
    });
});
