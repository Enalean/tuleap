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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";

import PermissionsUpdateFolderSubItems from "./PermissionsUpdateFolderSubItems.vue";
import { TYPE_FOLDER, TYPE_EMPTY } from "../../../constants";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import type { Empty, Folder, Item } from "../../../type";

describe("PermissionsUpdateFolderSubItems", () => {
    function getWrapper(
        item: Item,
    ): VueWrapper<InstanceType<typeof PermissionsUpdateFolderSubItems>> {
        return shallowMount(PermissionsUpdateFolderSubItems, {
            props: {
                item,
            },
            global: { ...getGlobalTestOptions({}) },
        });
    }

    it("Visible when item is a folder", () => {
        const item = { type: TYPE_FOLDER } as Folder;
        const wrapper = getWrapper(item);

        expect(wrapper.html()).toBeTruthy();
    });

    it("Not visible when item is not a folder", () => {
        const item = { type: TYPE_EMPTY } as Empty;
        const wrapper = getWrapper(item);

        expect(wrapper.html()).toMatchInlineSnapshot(`<!--v-if-->`);
    });
});
