/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import GitPermissions from "./GitPermissions.vue";
import * as api from "./rest-querier";
import GitInlineFilter from "./GitInlineFilter.vue";
import GitPermissionsTable from "./GitPermissionsTable.vue";
import localVueForTest from "./helper/local-vue-for-test";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

describe("GitPermissions", () => {
    function instantiateComponent(): Wrapper<GitPermissions> {
        return shallowMount(GitPermissions, {
            localVue: localVueForTest,
        });
    }

    it("When API returns Error, Then it's displayed", async () => {
        const wrapper = instantiateComponent();
        jest.spyOn(api, "getGitPermissions").mockReturnValue(
            Promise.reject(
                new FetchWrapperError("Not found", {
                    status: 404,
                    json: (): Promise<{ error: string }> =>
                        Promise.resolve({ error: "Error during get permissions" }),
                } as Response),
            ),
        );

        wrapper.find("[data-test=git-permission-button-load]").trigger("click");
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=git-permission-error]").text()).toBe(
            "Error during get permissions",
        );
    });

    it("When component is loading, Then loader is displayed", async () => {
        const wrapper = instantiateComponent();
        wrapper.setData({
            is_loading: true,
            is_loaded: false,
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=git-permission-error]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=git-permission-button-load]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=git-permission-loading]").exists()).toBeTruthy();
        expect(wrapper.findComponent(GitInlineFilter).exists()).toBeFalsy();
        expect(wrapper.findComponent(GitPermissionsTable).exists()).toBeFalsy();
    });

    it("When API returned repositories, Then GitInlineFilter and GitPermissionsTable are displayed", async () => {
        const wrapper = instantiateComponent();
        wrapper.setData({
            is_loading: false,
            is_loaded: true,
            repositories: [{ id: 1 }],
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=git-permission-error]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=git-permission-button-load]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=git-permission-loading]").exists()).toBeFalsy();
        expect(wrapper.findComponent(GitInlineFilter).exists()).toBeTruthy();
        expect(wrapper.findComponent(GitPermissionsTable).exists()).toBeTruthy();
        expect(wrapper.findComponent(GitPermissionsTable).props("repositories")).toEqual([
            { id: 1 },
        ]);
    });
});
