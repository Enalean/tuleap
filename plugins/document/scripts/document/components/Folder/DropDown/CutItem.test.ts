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

const emitMock = jest.fn();
jest.mock("../../../helpers/emitter", () => {
    return {
        emit: emitMock,
    };
});

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../../helpers/local-vue";
import CutItem from "./CutItem.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { Item } from "../../../type";

describe("CutItem", () => {
    let store = {
        commit: jest.fn(),
    };
    function createWrapper(item: Item, pasting_in_progress: boolean): Wrapper<CutItem> {
        store = createStoreMock({
            state: {
                clipboard: { pasting_in_progress },
            },
        });
        return shallowMount(CutItem, {
            mocks: {
                $store: store,
            },
            localVue: localVue,
            propsData: { item },
        });
    }

    beforeEach(() => {
        emitMock.mockClear();
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
        } as Item;
        const wrapper = createWrapper(item, false);

        wrapper.trigger("click");

        expect(store.commit).toHaveBeenCalledWith("clipboard/cutItem", item);
        expect(emitMock).toHaveBeenCalledWith("hide-action-menu");
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
        } as Item;
        const wrapper = createWrapper(item, true);

        expect(wrapper.attributes().disabled).toBeTruthy();
        expect(wrapper.classes("tlp-dropdown-menu-item-disabled")).toBe(true);

        wrapper.trigger("click");

        expect(emitMock).not.toHaveBeenCalled();
    });

    it(`Given the item is the root
        Then the cut action is not visible`, () => {
        const item = {
            id: 147,
            type: "item_type",
            title: "My item",
            parent_id: 0,
            user_can_write: true,
        } as Item;
        const wrapper = createWrapper(item, false);

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
        } as Item;
        const wrapper = createWrapper(item, false);

        expect(wrapper.html()).toBeFalsy();
    });
});
