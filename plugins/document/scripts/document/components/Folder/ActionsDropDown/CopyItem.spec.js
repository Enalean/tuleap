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
import { createStoreMock } from "@tuleap-vue-components/store-wrapper.js";
import CopyItem from "./CopyItem.vue";

describe("CopyItem", () => {
    let store, copy_item_factory;
    beforeEach(() => {
        store = createStoreMock({}, { clipboard: {} });

        copy_item_factory = (props = {}) => {
            return shallowMount(CopyItem, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store }
            });
        };
    });

    it(`Given item is copied
        Then the store is updated accordingly`, () => {
        const item = { id: 147, type: "item_type", title: "My item" };
        const wrapper = copy_item_factory({ item });

        wrapper.trigger("click");

        expect(store.commit).toHaveBeenCalledWith("clipboard/copyItem", item);
    });

    it(`Given an item is being pasted
        Then the action is marked as disabled`, () => {
        const item = { id: 147, type: "item_type", title: "My item" };
        store.state.clipboard.pasting_in_progress = true;
        const wrapper = copy_item_factory({ item });

        expect(wrapper.attributes().disabled).toBeTruthy();
        expect(wrapper.classes("tlp-dropdown-menu-item-disabled")).toBe(true);
    });
});
