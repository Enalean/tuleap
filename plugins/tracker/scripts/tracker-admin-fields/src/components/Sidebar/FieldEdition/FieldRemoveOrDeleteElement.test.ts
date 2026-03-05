/*
 * Copyright (c) Enalean, 2026-present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import { shallowMount, type VueWrapper } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import { HANDLE_REMOVE_FIELD, TRACKER_ROOT } from "../../../injection-symbols";
import FieldRemoveOrDeleteElement from "./FieldRemoveOrDeleteElement.vue";
import { CONTAINER_FIELDSET, STRING_FIELD } from "@tuleap/plugin-tracker-constants";
import type { StructureFields } from "@tuleap/plugin-tracker-rest-api-types";
import type { Field, Fieldset } from "../../../type";
import { buildField } from "../../../tests/builders/SimpleStructuralFieldTestBuilder";

describe("FieldRemoveOrDeleteElement", () => {
    const field_in_root: Field = {
        field: buildField(0, STRING_FIELD),
    };

    const field_1: Field = {
        field: buildField(10, STRING_FIELD),
    };

    const fieldset_with_children: Fieldset = {
        field: buildField(1, CONTAINER_FIELDSET),
        children: [field_1],
    };

    const children = [field_in_root, fieldset_with_children];

    const tracker_root = {
        children,
    };

    const getWrapper = (field: StructureFields): VueWrapper =>
        shallowMount(FieldRemoveOrDeleteElement, {
            props: {
                field,
            },
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [TRACKER_ROOT.valueOf()]: tracker_root,
                    [HANDLE_REMOVE_FIELD.valueOf()]: () => {},
                },
            },
        });
    it("If the current field can be unused or deleted, the button is enabled", () => {
        const wrapper = getWrapper(field_in_root.field);
        expect(
            wrapper.find("[data-test=remove-or-delete-field]").attributes("disabled"),
        ).toBeUndefined();
    });

    it("If the current field cannot be unused or deleted, the button is disabled", () => {
        const wrapper = getWrapper(fieldset_with_children.field);
        expect(
            wrapper.find("[data-test=remove-or-delete-field]").attributes("disabled"),
        ).toBeDefined();
    });
});
