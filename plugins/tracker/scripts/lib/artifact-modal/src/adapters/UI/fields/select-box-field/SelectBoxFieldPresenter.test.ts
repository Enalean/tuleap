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

import { SelectBoxFieldPresenter } from "./SelectBoxFieldPresenter";
import type { ListFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";

describe("SelectBoxFieldPresenter", () => {
    it.each([
        ["sb", false],
        ["msb", true],
    ])(
        `When the type of the field is %s, Then is_multiple_field should be %s`,
        (select_box_field_type, is_multiple) => {
            const field = {
                field_id: 105,
                label: "Assigned to",
                type: select_box_field_type,
                values: [],
            } as unknown as ListFieldStructure;

            const presenter = SelectBoxFieldPresenter.fromField(field, [], true);

            expect(presenter.is_multiple_select_box).toBe(is_multiple);
        },
    );

    it(`should build a presenter taking into account:
        - the state of the field (disabled, required)
        - the colors bound to the options`, () => {
        const value_1 = {
            id: 1051,
            label: "Open",
            value_color: "acid-green",
        };
        const value_2 = {
            id: 1052,
            label: "Close",
            value_color: "fiesta-red",
        };
        const field = {
            field_id: 105,
            label: "Status",
            required: true,
            values: [value_1, value_2],
        } as unknown as ListFieldStructure;

        const presenter = SelectBoxFieldPresenter.fromField(field, [value_2.id], true);

        expect(presenter.field_id).toStrictEqual(field.field_id);
        expect(presenter.field_label).toStrictEqual(field.label);
        expect(presenter.is_field_required).toStrictEqual(field.required);
        expect(presenter.is_field_disabled).toBe(true);
        expect(presenter.select_box_options).toStrictEqual([
            {
                id: "1052",
                label: "Close",
                value_color: "fiesta-red",
            },
        ]);
    });

    it(`should only take into account allowed values`, () => {
        const value_1 = {
            id: 1051,
            label: "Open",
            value_color: "acid-green",
        };
        const value_2 = {
            id: 1052,
            label: "Close",
            value_color: "fiesta-red",
        };
        const field = {
            field_id: 105,
            label: "Status",
            required: true,
            values: [value_1, value_2],
        } as unknown as ListFieldStructure;

        const presenter = SelectBoxFieldPresenter.fromField(field, [value_2.id], true);
        expect(presenter.select_box_options).not.toContain([
            {
                id: String(value_1.id),
                label: "Open",
                value_color: "acid-green",
            },
        ]);
    });

    it(`should build a presenter taking into account:
        - the state of the field (disabled, required)
        - the avatars bound to the options`, () => {
        const value_1 = {
            id: 1051,
            label: "User 1051",
            user_reference: {
                avatar_url: "url/to/avatar/1051",
            },
        };
        const value_2 = {
            id: 1052,
            label: "User 1052",
            user_reference: {
                avatar_url: "url/to/avatar/1052",
            },
        };
        const field = {
            field_id: 105,
            label: "Assigned to",
            required: false,
            values: [value_1, value_2],
        } as unknown as ListFieldStructure;

        const presenter = SelectBoxFieldPresenter.fromField(field, [value_2.id], false);
        expect(presenter.select_box_options).toStrictEqual([
            {
                id: "1052",
                label: "User 1052",
                avatar_url: "url/to/avatar/1052",
            },
        ]);
    });
});
