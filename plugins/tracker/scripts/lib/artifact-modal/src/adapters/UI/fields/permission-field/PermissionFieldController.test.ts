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

import { PermissionFieldController } from "./PermissionFieldController";
import type { PermissionFieldType } from "./PermissionFieldType";
import type { PermissionFieldValueModel } from "./PermissionFieldValueModel";

function getField(is_field_required: boolean): PermissionFieldType {
    return {
        field_id: 1060,
        label: "Permissions",
        required: is_field_required,
        values: {
            ugroup_representations: [],
        },
    };
}

function getValueModel(
    is_used_by_default: boolean,
    granted_groups: ReadonlyArray<string>,
): PermissionFieldValueModel {
    return {
        field_id: 1060,
        value: {
            is_used_by_default,
            granted_groups,
        },
    };
}

describe("PermissionFieldController", () => {
    describe("isSelectBoxDisabled", () => {
        it.each([
            ["true when the field is required and disabled", true, true, true, true],
            ["false when the field is required and not disabled", true, false, true, false],
            [
                "true when the field is not required and not used by default",
                false,
                false,
                false,
                true,
            ],
            [
                "true when the field is not required, disabled and used by default",
                false,
                true,
                true,
                true,
            ],
        ])(
            `should be %s`,
            (
                expectation: string,
                is_field_required: boolean,
                is_field_disabled: boolean,
                is_used_by_default: boolean,
                will_be_disabled: boolean,
            ) => {
                const presenter = PermissionFieldController(
                    getField(is_field_required),
                    getValueModel(is_used_by_default, []),
                    is_field_disabled,
                ).buildPresenter();

                expect(presenter.is_select_box_disabled).toBe(will_be_disabled);
            },
        );
    });

    describe("isSelectBoxRequired", () => {
        it.each([
            ["true when the field is used by default", false, true, true],
            ["true when the field is required and not used by default", true, false, true],
            ["false when the field is not required and not used by default", false, false, false],
        ])(
            `should be %s`,
            (
                expectation: string,
                is_field_required: boolean,
                is_used_by_default: boolean,
                will_be_required: boolean,
            ) => {
                const presenter = PermissionFieldController(
                    getField(is_field_required),
                    getValueModel(is_used_by_default, []),
                    false,
                ).buildPresenter();

                expect(presenter.is_select_box_required).toBe(will_be_required);
            },
        );
    });

    describe("setIsFieldUsedByDefault", () => {
        it.each([[true], [false]])(
            `should return a presenter having is_used_by_default being %s`,
            (is_used_by_default: boolean) => {
                const presenter = PermissionFieldController(
                    getField(true),
                    getValueModel(!is_used_by_default, []),
                    false,
                ).setIsFieldUsedByDefault(is_used_by_default);

                expect(presenter.is_used).toBe(is_used_by_default);
            },
        );

        it("should clear the granted groups when is_used_by_default has been set to false", () => {
            const granted_groups = ["101_3", "101_2"];
            const presenter = PermissionFieldController(
                getField(true),
                getValueModel(true, granted_groups),
                false,
            ).setIsFieldUsedByDefault(false);

            expect(presenter.granted_groups).toHaveLength(0);
        });
    });

    describe("setGrantedGroups", () => {
        it("should return a presenter with granted_groups containing the given ids", () => {
            const presenter = PermissionFieldController(
                getField(true),
                getValueModel(true, []),
                false,
            ).setGrantedGroups(["101_3", "101_2"]);

            expect(presenter.granted_groups).toHaveLength(2);
            expect(presenter.granted_groups).toStrictEqual(["101_3", "101_2"]);
        });
    });
});
