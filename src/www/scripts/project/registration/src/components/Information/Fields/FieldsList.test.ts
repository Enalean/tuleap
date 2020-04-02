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

import { shallowMount, Wrapper } from "@vue/test-utils";
import { createProjectRegistrationLocalVue } from "../../../helpers/local-vue-for-tests";
import FieldsList from "./FieldsList.vue";
import EventBus from "../../../helpers/event-bus";
import { FieldData } from "../../../type";

async function getWrapper(field: FieldData): Promise<Wrapper<FieldsList>> {
    return shallowMount(FieldsList, {
        localVue: await createProjectRegistrationLocalVue(),
        propsData: { field },
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

        expect(wrapper.contains("[data-test=text-info]")).toBe(false);
    });

    it("Does not display the field if it is not required", async () => {
        const wrapper = await getWrapper({
            group_desc_id: "1",
            desc_type: "line",
            desc_required: "0",
        } as FieldData);

        expect(wrapper.isEmpty()).toBe(true);
    });

    it("Send an event when user chooses a new value for the field", async () => {
        const event_bus_emit = jest.spyOn(EventBus, "$emit");

        const wrapper = await getWrapper({
            group_desc_id: "1",
            desc_type: "text",
            desc_required: "1",
        } as FieldData);

        wrapper.get("[data-test=project-field-text]").setValue("my new value");

        expect(event_bus_emit).toHaveBeenCalledWith("update-field-list", {
            field_id: "1",
            value: "my new value",
        });
    });
});
