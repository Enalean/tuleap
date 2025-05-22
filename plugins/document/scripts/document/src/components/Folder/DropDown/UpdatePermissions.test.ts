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

import { describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import UpdatePermissions from "./UpdatePermissions.vue";
import emitter from "../../../helpers/emitter";
import type { Item } from "../../../type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

vi.mock("../../../helpers/emitter");

describe("UpdatePermissions", () => {
    function createWrapper(item: Item): VueWrapper<InstanceType<typeof UpdatePermissions>> {
        return shallowMount(UpdatePermissions, {
            props: { item },
            global: { ...getGlobalTestOptions({}) },
        });
    }

    it(`Given a user can not manage the item then the corresponding option is not shown`, () => {
        const item = {
            can_user_manage: false,
        } as Item;
        const wrapper = createWrapper(item);

        expect(wrapper.html()).toMatchInlineSnapshot(`<!--v-if-->`);
    });

    it(`Given a user click on the element then the corresponding modal is opened`, () => {
        const item = {
            can_user_manage: true,
        } as Item;
        const wrapper = createWrapper(item);

        expect(wrapper.html()).toBeTruthy();

        wrapper.trigger("click");

        expect(emitter.emit).toHaveBeenCalledWith("show-update-permissions-modal", {
            detail: { current_item: item },
        });
    });
});
