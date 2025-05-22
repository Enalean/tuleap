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

import { describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import UpdateProperties from "./UpdateProperties.vue";
import emitter from "../../../../helpers/emitter";
import type { Item } from "../../../../type";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";

vi.mock("../../../../helpers/emitter");

describe("UpdateProperties", () => {
    function createWrapper(item: Item): VueWrapper<InstanceType<typeof UpdateProperties>> {
        return shallowMount(UpdateProperties, {
            props: { item },
            global: { ...getGlobalTestOptions({}) },
        });
    }

    it(`Click on folder open the corresponding modal`, () => {
        const item = {
            user_can_write: true,
        } as Item;
        const wrapper = createWrapper(item);
        wrapper.get("[data-test=document-dropdown-update-properties]").trigger("click");

        expect(emitter.emit).toHaveBeenCalledWith("show-update-item-properties-modal", {
            detail: { current_item: item },
        });
    });
});
