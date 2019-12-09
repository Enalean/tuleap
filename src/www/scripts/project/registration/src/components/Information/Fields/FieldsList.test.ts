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

describe("FieldsList - ", () => {
    let factory: Wrapper<FieldsList>;
    beforeEach(async () => {
        const field = {
            group_desc_id: "1",
            desc_name: "custom field",
            desc_type: "text",
            desc_description: "a helpful description",
            desc_required: true
        };

        factory = shallowMount(FieldsList, {
            localVue: await createProjectRegistrationLocalVue(),
            propsData: { field: field }
        });
    });

    it("Display correctly the component", () => {
        const wrapper = factory;

        expect(wrapper).toMatchSnapshot();
    });

    it("Send an event when user chooses a category", () => {
        const event_bus_emit = jest.spyOn(EventBus, "$emit");

        const wrapper = factory;

        wrapper.find("[data-test=project-field-text]").setValue("my new value");

        expect(event_bus_emit).toHaveBeenCalledWith("update-field-list", {
            field_id: "1",
            value: "my new value"
        });
    });
});
