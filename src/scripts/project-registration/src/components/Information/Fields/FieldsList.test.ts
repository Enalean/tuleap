/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import FieldsList from "./FieldsList.vue";
import type { FieldData } from "../../../type";
import emitter from "../../../helpers/emitter";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import { buildVueDompurifyHTMLDirective } from "vue-dompurify-html";

function getWrapper(field: FieldData): VueWrapper {
    return shallowMount(FieldsList, {
        global: {
            ...getGlobalTestOptions(),
        },
        directives: {
            "dompurify-html": buildVueDompurifyHTMLDirective(),
        },
        props: { field },
    });
}

describe("FieldsList -", () => {
    it("Display correctly a text field", async () => {
        const wrapper = await getWrapper({
            group_desc_id: "1",
            desc_name: "custom field",
            desc_type: "text",
            desc_description: "a helpful description",
            desc_required: "1",
        });

        expect(wrapper).toMatchSnapshot();
    });

    it("Display correctly a string field", async () => {
        const wrapper = await getWrapper({
            group_desc_id: "1",
            desc_name: "custom field",
            desc_type: "line",
            desc_description: "a helpful description",
            desc_required: "1",
        });

        expect(wrapper).toMatchSnapshot();
    });

    it("Does not display a text-info if there is no description", async () => {
        const wrapper = await getWrapper({
            group_desc_id: "1",
            desc_type: "line",
            desc_description: "",
        } as FieldData);

        expect(wrapper.find("[data-test=text-info]").exists()).toBe(false);
    });

    it("Does not display the field if it is not required", async () => {
        const wrapper = await getWrapper({
            group_desc_id: "1",
            desc_type: "line",
            desc_required: "0",
        } as FieldData);

        expect(wrapper.isVisible()).toBe(false);
    });

    it("Send an event when user chooses a new value for the field", async () => {
        const emit = jest.spyOn(emitter, "emit");

        const wrapper = await getWrapper({
            group_desc_id: "1",
            desc_type: "text",
            desc_required: "1",
        } as FieldData);

        wrapper.get("[data-test=project-field-text]").setValue("my new value");

        expect(emit).toHaveBeenCalledWith("update-field-list", {
            field_id: "1",
            value: "my new value",
        });
    });
});
