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
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import NewItemButton from "./NewItemButton.vue";
import type { Item } from "../../../type";
import localVue from "../../../helpers/local-vue";
import { TYPE_FOLDER } from "../../../constants";
import mitt from "../../../helpers/emitter";

jest.mock("../../../helpers/emitter");

describe("NewItemButton", () => {
    function createWrapper(item: Item): Wrapper<NewItemButton> {
        return shallowMount(NewItemButton, {
            localVue,
            propsData: { item: item },
        });
    }

    it(`When user clicks on New item button
        Then it should open a modal`, () => {
        const item = {
            type: TYPE_FOLDER,
            user_can_write: true,
        } as Item;

        const wrapper = createWrapper(item);

        wrapper.get("[data-test=docman-new-item-button]").trigger("click");

        expect(mitt.emit).toHaveBeenCalledWith("createItem", { item: item });
    });
});
