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

import { CheckboxFieldPresenter } from "./CheckboxFieldPresenter";

describe("CheckboxFieldPresenter", () => {
    it("should build from field", () => {
        const presenter = CheckboxFieldPresenter.fromField(
            {
                field_id: 1060,
                label: "Numbers",
                values: [
                    {
                        id: 1,
                        label: "One",
                    },
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
            [null, null, 3, 4],
            false,
        );

        expect(presenter.field_id).toBe(1060);
        expect(presenter.field_label).toBe("Numbers");
        expect(presenter.is_field_required).toBe(true);
        expect(presenter.is_field_disabled).toBe(false);
        expect(presenter.checkbox_values).toStrictEqual([
            {
                id: 1,
                label: "One",
                is_checked: false,
            },
            {
                id: 2,
                label: "Two",
                is_checked: false,
            },
            {
                id: 3,
                label: "Three",
                is_checked: true,
            },
            {
                id: 4,
                label: "Four",
                is_checked: true,
            },
        ]);
    });
});
