/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach } from "@jest/globals";
import type { StaticOpenListFieldType } from "../../../../../domain/fields/static-open-list-field/StaticOpenListFieldType";
import { StaticOpenListFieldPresenterBuilder } from "./StaticOpenListFieldPresenter";

const bind_values = [
    { id: "1", label: "Foo", is_hidden: false },
    { id: "2", label: "Bar", is_hidden: false },
    { id: "3", label: "Baz", is_hidden: false },
];

describe("StaticOpenListFieldPresenter", () => {
    let is_required: boolean;

    beforeEach(() => {
        is_required = false;
    });

    const getField = (): StaticOpenListFieldType =>
        ({
            field_id: 1001,
            label: "Random meaningless stuff",
            name: "random_stuff",
            hint: "Please select some value, or create one and forget about it",
            required: is_required,
        }) as StaticOpenListFieldType;

    it("Given an open list field bound to static values and the list of selected values, then it should build a presenter", () => {
        const field = getField();
        const presenter = StaticOpenListFieldPresenterBuilder.withSelectableValues(
            field,
            [bind_values[1], bind_values[2]],
            bind_values,
        );

        expect(presenter).toStrictEqual({
            field_id: "tracker_field_1001",
            label: field.label,
            name: field.name,
            hint: field.hint,
            required: field.required,
            is_required_and_empty: false,
            values: [
                { ...bind_values[0], selected: false },
                { ...bind_values[1], selected: true },
                { ...bind_values[2], selected: true },
            ],
        });
    });

    describe("is_required_and_empty", () => {
        it("Given that the field was required and the value model empty, then it will return true", () => {
            is_required = true;
            const presenter = StaticOpenListFieldPresenterBuilder.withSelectableValues(
                getField(),
                [],
                bind_values,
            );

            expect(presenter.is_required_and_empty).toBe(true);
        });

        it("Given that the field was required and the value model had a value, then it will return false", () => {
            is_required = true;
            const presenter = StaticOpenListFieldPresenterBuilder.withSelectableValues(
                getField(),
                [bind_values[0]],
                bind_values,
            );

            expect(presenter.is_required_and_empty).toBe(false);
        });

        it("Given that the field was not required and the value model empty, then it will return false", () => {
            is_required = false;
            const presenter = StaticOpenListFieldPresenterBuilder.withSelectableValues(
                getField(),
                [],
                bind_values,
            );

            expect(presenter.is_required_and_empty).toBe(false);
        });
    });
});
