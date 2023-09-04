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

import { CheckboxFieldController } from "./CheckboxFieldController";

describe("CheckboxFieldController", () => {
    it("returns a presenter with the updated value of the given checkbox", () => {
        const bind_value_ids = [null, null, 3, 4];
        const checkbox_one = {
            id: 1,
            label: "One",
        };

        const presenter = CheckboxFieldController(
            {
                field_id: 1060,
                label: "Numbers",
                values: [
                    checkbox_one,
                    {
                        id: 2,
                        label: "Two",
                    },
                    {
                        id: 3,
                        label: "Three",
                    },
                    {
                        id: 4,
                        label: "Four",
                    },
                ],
                required: true,
            },
            bind_value_ids,
            false,
        ).setCheckboxValue(1, 0, true);

        expect(bind_value_ids[0]).toBe(checkbox_one.id);
        expect(presenter.checkbox_values[0].is_checked).toBe(true);
    });
});
