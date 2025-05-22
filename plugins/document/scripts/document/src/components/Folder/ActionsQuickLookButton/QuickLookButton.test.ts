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
import QuickLookButton from "./QuickLookButton.vue";
import { TYPE_FOLDER } from "../../../constants";
import type { Item } from "../../../type";
import emitter from "../../../helpers/emitter";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

vi.mock("../../../helpers/emitter");

describe("QuickLookButton", () => {
    function createWrapper(item: Item): VueWrapper<InstanceType<typeof QuickLookButton>> {
        return shallowMount(QuickLookButton, {
            props: { item },
            global: { ...getGlobalTestOptions({}) },
        });
    }

    it(`Emit displayQuickLook event with correct parameters when user click on button`, () => {
        const item = { type: TYPE_FOLDER, user_can_write: true } as Item;
        const wrapper = createWrapper(item);

        wrapper.get("[data-test=document-quick-look-button]").trigger("click");
        expect(emitter.emit).toHaveBeenCalledWith("toggle-quick-look", {
            details: { item },
        });
    });
});
