/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import { describe, it, expect, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { MULTI_SELECTBOX_FIELD, SELECTBOX_FIELD } from "@tuleap/plugin-tracker-constants";
import type { ListFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import { StaticBoundListFieldTestBuilder } from "../../tests/builders/StaticBoundListFieldTestBuilder";
import FieldSelect from "./FieldSelect.vue";

vi.mock("@tuleap/list-picker", () => {
    return {
        createListPicker: (): void => {
            //Do nothing
        },
    };
});

describe("FieldSelect", () => {
    const getWrapper = (field: ListFieldStructure): VueWrapper =>
        shallowMount(FieldSelect, {
            props: { field },
        });

    it("When the field is a simple select box field, Then it should render a single select field", () => {
        const wrapper = getWrapper(
            StaticBoundListFieldTestBuilder.aStaticBoundListField(SELECTBOX_FIELD).build(),
        );
        const select = wrapper.find("select");

        expect(select.attributes("multiple")).not.toBeDefined();
    });

    it("When the field is a multiple select box field, Then it should render a multiple select field", () => {
        const wrapper = getWrapper(
            StaticBoundListFieldTestBuilder.aStaticBoundListField(MULTI_SELECTBOX_FIELD).build(),
        );
        const select = wrapper.find("select");

        expect(select.attributes("multiple")).toBeDefined();
    });
});
