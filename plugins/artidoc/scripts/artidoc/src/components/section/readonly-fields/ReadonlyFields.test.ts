/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { describe, it, expect } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import type { ReadonlyField, ReadonlyFieldString } from "@/sections/readonly-fields/ReadonlyFields";
import FieldString from "@/components/section/readonly-fields/FieldString.vue";
import ReadonlyFields from "@/components/section/readonly-fields/ReadonlyFields.vue";

const string_field: ReadonlyFieldString = {
    type: "string",
    label: "String Field",
    display_type: "column",
    value: "The first field",
};

describe("ReadonlyFields", () => {
    const getWrapper = (fields: ReadonlyField[]): VueWrapper => {
        const section = ArtifactSectionFactory.override({
            fields,
        });

        return shallowMount(ReadonlyFields, {
            props: {
                section,
            },
        });
    };

    it("should display String fields in column", () => {
        const wrapper = getWrapper([string_field]);

        expect(wrapper.findComponent(FieldString).exists()).toBe(true);
        expect(wrapper.findAll(".tlp-property")[0].classes()).toStrictEqual(["tlp-property"]);
    });

    it("should display String fields in block", () => {
        const wrapper = getWrapper([{ ...string_field, display_type: "block" }]);

        expect(wrapper.findComponent(FieldString).exists()).toBe(true);
        expect(wrapper.findAll(".tlp-property")[0].classes()).toStrictEqual([
            "tlp-property",
            "display-field-in-block",
            "document-grid-element-full-row",
        ]);
    });
});
