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
import localVue from "../../../helpers/local-vue.js";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import QuickLookButton from "./QuickLookButton.vue";
import { TYPE_FOLDER } from "../../../constants.js";
import EventBus from "../../../helpers/event-bus.js";

describe("QuickLookButton", () => {
    let factory, store;
    beforeEach(() => {
        store = createStoreMock({});
        store.getters.is_item_a_folder = () => true;
        factory = (props = {}) => {
            return shallowMount(QuickLookButton, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });

    it(`Emit displayQuickLook event with correct parameters when user click on button`, () => {
        const item = { type: TYPE_FOLDER, user_can_write: true };
        const event_bus_emit = jest.spyOn(EventBus, "$emit");
        const wrapper = factory({ item });

        wrapper.get("[data-test=document-quick-look-button]").trigger("click");
        expect(event_bus_emit).toHaveBeenCalledWith("toggle-quick-look", {
            details: { item },
        });
    });
});
