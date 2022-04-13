/*
 * Copyright (c) Enalean 2019 -  Present. All Rights Reserved.
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

import DeleteItem from "./DeleteItem.vue";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { Item } from "../../../../type";
import localVue from "../../../../helpers/local-vue";
import emitter from "../../../../helpers/emitter";

jest.mock("../../../../helpers/emitter");

describe("DeleteItem", () => {
    let store = {};
    function createWrapper(
        user_can_write: boolean,
        is_deletion_allowed: boolean
    ): Wrapper<DeleteItem> {
        store = createStoreMock({
            state: {
                configuration: { is_deletion_allowed },
            },
        });
        return shallowMount(DeleteItem, {
            mocks: {
                $store: store,
            },
            localVue: localVue,
            propsData: { item: { id: 1, user_can_write } as Item },
        });
    }

    it(`Displays the delete button because the user can write and deletion is allowed`, () => {
        const wrapper = createWrapper(true, true);
        expect(wrapper.find("[data-test=document-delete-item]").exists()).toBeTruthy();
    });
    it(`Does not display the delete button if the user can't write but deletion is allowed`, () => {
        const wrapper = createWrapper(false, true);
        expect(wrapper.find("[data-test=document-delete-item]").exists()).toBeFalsy();
    });
    it(`Does not display the delete button if the user can write but deletion is  not allowed`, () => {
        const wrapper = createWrapper(true, false);
        expect(wrapper.find("[data-test=document-delete-item]").exists()).toBeFalsy();
    });
    it(`When the user clicks the button, then it should trigger an event to open the confirmation modal`, () => {
        const wrapper = createWrapper(true, true);
        wrapper.get("[data-test=document-delete-item]").trigger("click");

        expect(emitter.emit).toHaveBeenCalledWith("deleteItem", {
            item: {
                id: 1,
                user_can_write: true,
            },
        });
    });
});
