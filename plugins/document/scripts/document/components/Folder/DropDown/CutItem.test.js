/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
import CutItem from "./CutItem.vue";
import EventBus from "../../../helpers/event-bus.js";

describe("CutItem", () => {
    let store, cut_item_factory;
    beforeEach(() => {
        store = createStoreMock({}, { clipboard: {} });

        cut_item_factory = (props = {}) => {
            return shallowMount(CutItem, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });

    it(`Given item is cut
        Then the store is updated accordingly
        And the menu closed`, () => {
        const item = {
            id: 147,
            type: "item_type",
            title: "My item",
            parent_id: 146,
            user_can_write: true,
        };
        const event_bus_emit = jest.spyOn(EventBus, "$emit");
        const wrapper = cut_item_factory({ item });

        wrapper.trigger("click");

        expect(store.commit).toHaveBeenCalledWith("clipboard/cutItem", item);
        expect(event_bus_emit).toHaveBeenCalledWith("hide-action-menu");
    });

    it(`Given an item is being pasted
        Then the action is marked as disabled
        And the menu is not closed if the user tries to click on it`, () => {
        const item = {
            id: 147,
            type: "item_type",
            title: "My item",
            parent_id: 146,
            user_can_write: true,
        };
        store.state.clipboard.pasting_in_progress = true;
        const event_bus_emit = jest.spyOn(EventBus, "$emit");
        const wrapper = cut_item_factory({ item });

        expect(wrapper.attributes().disabled).toBeTruthy();
        expect(wrapper.classes("tlp-dropdown-menu-item-disabled")).toBe(true);

        wrapper.trigger("click");

        expect(event_bus_emit).not.toHaveBeenCalled();
    });

    it(`Given the item is the root
        Then the cut action is not visible`, () => {
        const item = {
            id: 147,
            type: "item_type",
            title: "My item",
            parent_id: 0,
            user_can_write: true,
        };
        const wrapper = cut_item_factory({ item });

        expect(wrapper.html()).toBeFalsy();
    });

    it(`Given the item is not writable
        Then the cut action is not visible`, () => {
        const item = {
            id: 147,
            type: "item_type",
            title: "My item",
            parent_id: 146,
            user_can_write: false,
        };
        const wrapper = cut_item_factory({ item });

        expect(wrapper.html()).toBeFalsy();
    });
});
