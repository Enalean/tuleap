/*
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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
 *
 */

import { shallowMount } from "@vue/test-utils";
import QuickLookFolder from "./QuickLookFolder.vue";
import localVue from "../../helpers/local-vue";
import { TYPE_FOLDER } from "../../constants";
import type { Item } from "../../type";
import emitter from "../../helpers/emitter";

jest.mock("../../helpers/emitter");

describe("QuickLookFolder", () => {
    it("User can create/remove folder when he can write", () => {
        const item = {
            type: TYPE_FOLDER,
            user_can_write: true,
        } as Item;

        const wrapper = shallowMount(QuickLookFolder, {
            localVue,
            propsData: { item: item },
        });

        expect(wrapper.find("[data-test=create-folder-button]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=delete-folder-button]").exists()).toBeTruthy();
    });

    it("User can NOT create/remove folder when he can write", () => {
        const item = {
            type: TYPE_FOLDER,
            user_can_write: false,
        } as Item;

        const wrapper = shallowMount(QuickLookFolder, {
            localVue,
            propsData: { item: item },
        });

        expect(wrapper.find("[data-test=create-folder-button]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=delete-folder-button]").exists()).toBeFalsy();
    });

    it("trigger open modal event when user click on new folder", () => {
        const item = {
            type: TYPE_FOLDER,
            user_can_write: true,
        } as Item;

        const wrapper = shallowMount(QuickLookFolder, {
            localVue,
            propsData: { item: item },
        });

        wrapper.get("[data-test=create-folder-button]").trigger("click");
        expect(emitter.emit).toHaveBeenCalledWith("show-new-folder-modal", {
            detail: { parent: item },
        });
    });
});
