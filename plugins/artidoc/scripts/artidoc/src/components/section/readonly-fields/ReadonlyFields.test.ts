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

import { describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { ReadonlyField } from "@/sections/readonly-fields/ReadonlyFields";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import { ReadonlyFieldStub } from "@/sections/stubs/ReadonlyFieldStub";
import {
    DISPLAY_TYPE_BLOCK,
    DISPLAY_TYPE_COLUMN,
} from "@/sections/readonly-fields/AvailableReadonlyFields";
import ReadonlyFields from "@/components/section/readonly-fields/ReadonlyFields.vue";
import FieldText from "@/components/section/readonly-fields/FieldText.vue";
import FieldUserGroupsList from "@/components/section/readonly-fields/FieldUserGroupsList.vue";
import FieldStaticList from "@/components/section/readonly-fields/FieldStaticList.vue";
import FieldUserList from "@/components/section/readonly-fields/FieldUserList.vue";
import FieldLinks from "@/components/section/readonly-fields/FieldLinks.vue";
import FieldNumeric from "@/components/section/readonly-fields/FieldNumeric.vue";

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

    it("When the display type of a readonly field is 'column', then it should display it in column", () => {
        const wrapper = getWrapper([
            ReadonlyFieldStub.string("The first field", DISPLAY_TYPE_COLUMN),
        ]);

        expect(wrapper.findComponent(FieldText).exists()).toBe(true);
        expect(wrapper.findAll(".tlp-property")[0].classes()).toStrictEqual(["tlp-property"]);
    });

    it("When the display type of a readonly field is 'block', then it should display it in block", () => {
        const wrapper = getWrapper([
            ReadonlyFieldStub.string("The first field", DISPLAY_TYPE_BLOCK),
        ]);

        expect(wrapper.findComponent(FieldText).exists()).toBe(true);
        expect(wrapper.findAll(".tlp-property")[0].classes()).toStrictEqual([
            "tlp-property",
            "display-field-in-block",
            "document-grid-element-full-row",
        ]);
    });

    it("should display all kinds of readonly fields", () => {
        const bob = { display_name: "Bob", avatar_url: "https://example.com/bob_avatar.png" };
        const fields = [
            ReadonlyFieldStub.string("String field", DISPLAY_TYPE_COLUMN),
            ReadonlyFieldStub.userGroupsList(
                [{ label: "Project Administrators" }],
                DISPLAY_TYPE_COLUMN,
            ),
            ReadonlyFieldStub.staticList(
                [{ label: "Red", tlp_color: "fiesta-red" }],
                DISPLAY_TYPE_BLOCK,
            ),
            ReadonlyFieldStub.userList([bob], DISPLAY_TYPE_BLOCK),
            ReadonlyFieldStub.linkField([]),
            ReadonlyFieldStub.numericField(42, DISPLAY_TYPE_COLUMN),
            ReadonlyFieldStub.userField(bob, DISPLAY_TYPE_COLUMN),
        ];
        const wrapper = getWrapper(fields);

        expect(wrapper.findAll("[data-test=readonly-field]")).toHaveLength(fields.length);
        expect(wrapper.findComponent(FieldText).exists()).toBe(true);
        expect(wrapper.findComponent(FieldUserGroupsList).exists()).toBe(true);
        expect(wrapper.findComponent(FieldStaticList).exists()).toBe(true);
        expect(wrapper.findAllComponents(FieldUserList)).toHaveLength(2);
        expect(wrapper.findComponent(FieldLinks).exists()).toBe(true);
        expect(wrapper.findComponent(FieldNumeric).exists()).toBe(true);
    });
});
