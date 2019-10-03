/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { shallowMount } from "@vue/test-utils";
import BannerDeleter from "./BannerDeleter.vue";
import { createProjectAdminBannerLocalVue } from "../helpers/local-vue-for-tests";

describe("BannerDeleter", () => {
    it("displays a delete button", async () => {
        const wrapper = shallowMount(BannerDeleter, {
            localVue: await createProjectAdminBannerLocalVue()
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("emits a delete delete-banner event on click", async () => {
        const wrapper = shallowMount(BannerDeleter, {
            localVue: await createProjectAdminBannerLocalVue()
        });

        const emitSpy = jest.spyOn(wrapper.vm, "$emit");

        wrapper.find("button").trigger("click");
        expect(emitSpy).toBeCalledWith("delete-banner");
    });

    it("disables the button and displays a transition test while requesting deletion", async () => {
        const wrapper = shallowMount(BannerDeleter, {
            localVue: await createProjectAdminBannerLocalVue()
        });

        wrapper.find("button").trigger("click");
        expect(wrapper.element).toMatchSnapshot();
    });
});
