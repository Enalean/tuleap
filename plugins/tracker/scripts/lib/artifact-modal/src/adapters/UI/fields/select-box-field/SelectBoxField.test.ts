/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { getFormElementClasses } from "./SelectBoxField";
import type { SelectBoxField } from "./SelectBoxField";
import type { SelectBoxFieldPresenter } from "./SelectBoxFieldPresenter";

describe("SelectBoxField", () => {
    describe("field error state", () => {
        const getFieldClasses = (
            is_field_required: boolean,
            bind_value_ids: number[]
        ): Record<string, boolean> => {
            return getFormElementClasses({
                bind_value_ids,
                field_presenter: {
                    is_field_required,
                } as SelectBoxFieldPresenter,
            } as unknown as SelectBoxField);
        };

        it(`Given that the field is required to have a value
            When there is no selected value
            Then it should have the tlp-form-element-error class`, () => {
            expect(getFieldClasses(true, [])).toStrictEqual({
                "tlp-form-element": true,
                "tlp-form-element-error": true,
            });
        });

        it(`Given that the field is required to have a value
            When only the value 100 is selected
            Then it should have the tlp-form-element-error class`, () => {
            expect(getFieldClasses(true, [100])).toStrictEqual({
                "tlp-form-element": true,
                "tlp-form-element-error": true,
            });
        });

        it(`Given that the field is not required to have a value
            When there is no selected value
            Then it should NOT have the tlp-form-element-error class`, () => {
            expect(getFieldClasses(false, [])).toStrictEqual({
                "tlp-form-element": true,
                "tlp-form-element-error": false,
            });
        });

        it(`Given that the field is not required to have a value
            When the only selected value is 100 (none)
            Then it should NOT have the tlp-form-element-error class`, () => {
            expect(getFieldClasses(false, [100])).toStrictEqual({
                "tlp-form-element": true,
                "tlp-form-element-error": false,
            });
        });
    });
});
