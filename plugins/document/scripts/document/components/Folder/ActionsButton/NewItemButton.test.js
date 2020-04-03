/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
import { shallowMount } from "@vue/test-utils";
import NewItemButton from "./NewItemButton.vue";

import localVue from "../../../helpers/local-vue.js";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import EventBus from "../../../helpers/event-bus.js";

describe("NewItemButton", () => {
    let factory;
    beforeEach(() => {
        const state = {
            project_id: 101,
        };

        const store_options = {
            state,
        };

        const store = createStoreMock(store_options);

        factory = (props = {}) => {
            return shallowMount(NewItemButton, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });

    it(`When user clicks on New item button
        Then it should open a modal`, () => {
        const event_bus_emit = jest.spyOn(EventBus, "$emit");
        const wrapper = factory({
            item: {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: true,
            },
        });

        wrapper.get("[data-test=docman-new-item-button]").trigger("click");

        expect(event_bus_emit).toHaveBeenCalledWith("show-new-document-modal", expect.any(Object));
    });
});
