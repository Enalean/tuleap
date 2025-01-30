/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
import AppBreadcrumb from "./AppBreadcrumb.vue";
import { createProgramManagementLocalVue } from "../helpers/local-vue-for-test";

describe("AppBreadcrumb", () => {
    async function getWrapper(is_program_admin: boolean): Promise<Wrapper<Vue>> {
        return shallowMount(AppBreadcrumb, {
            localVue: await createProgramManagementLocalVue(),
            propsData: {
                project_public_name: "Public name",
                project_short_name: "short-name",
                project_privacy: {},
                project_flags: [],
                project_icon: "",
                is_program_admin,
            },
        });
    }

    it("When user is not program admin, Then breadcrumb does not contain administration link", async () => {
        const wrapper = await getWrapper(false);
        expect(wrapper.find("[data-test=breadcrumb-item-switchable]").classes()).not.toContain(
            "breadcrumb-switchable",
        );
        expect(wrapper.find("[data-test=breadcrumb-item-administration]").exists()).toBe(false);
    });

    it("When user is program admin, Then administration link is displayed", async () => {
        const wrapper = await getWrapper(true);
        expect(wrapper.find("[data-test=breadcrumb-item-switchable]").classes()).toContain(
            "breadcrumb-switchable",
        );
        expect(wrapper.find("[data-test=breadcrumb-item-administration]").exists()).toBe(true);
    });
});
