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

import { describe, it, expect, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import GitPermissions from "./GitPermissions.vue";
import * as api from "./rest-querier";
import GitInlineFilter from "./GitInlineFilter.vue";
import GitPermissionsTable from "./GitPermissionsTable.vue";
import { createGettext } from "vue3-gettext";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import type { RepositoryFineGrainedPermissions } from "./type";

describe("GitPermissions", () => {
    it("When API returns Error, Then it's displayed", async () => {
        const wrapper = shallowMount(GitPermissions, {
            props: {
                selected_project_id: 1,
                selected_ugroup_id: "1",
                selected_ugroup_name: "lorem",
            },
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });
        vi.spyOn(api, "getGitPermissions").mockReturnValue(
            errAsync(Fault.fromMessage("Error during get permissions")),
        );

        await wrapper.find("[data-test=git-permission-button-load]").trigger("click");

        expect(wrapper.find("[data-test=git-permission-error]").text()).toBe(
            "Error during get permissions",
        );
    });

    it("When component is loading, Then loader is displayed", async () => {
        const wrapper = shallowMount(GitPermissions, {
            props: {
                selected_project_id: 1,
                selected_ugroup_id: "1",
                selected_ugroup_name: "lorem",
            },
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });
        vi.spyOn(api, "getGitPermissions").mockReturnValue(okAsync({ repositories: [] }));

        wrapper.find("[data-test=git-permission-button-load]").trigger("click");
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=git-permission-error]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=git-permission-button-load]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=git-permission-loading]").exists()).toBeTruthy();
        expect(wrapper.findComponent(GitInlineFilter).exists()).toBeFalsy();
        expect(wrapper.findComponent(GitPermissionsTable).exists()).toBeFalsy();
    });

    it("When API returned repositories, Then GitInlineFilter and GitPermissionsTable are displayed", async () => {
        const wrapper = shallowMount(GitPermissions, {
            props: {
                selected_project_id: 1,
                selected_ugroup_id: "1",
                selected_ugroup_name: "lorem",
            },
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });
        vi.spyOn(api, "getGitPermissions").mockReturnValue(
            okAsync({ repositories: [{ id: 1 } as unknown as RepositoryFineGrainedPermissions] }),
        );

        await wrapper.find("[data-test=git-permission-button-load]").trigger("click");

        expect(wrapper.find("[data-test=git-permission-error]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=git-permission-button-load]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=git-permission-loading]").exists()).toBeFalsy();
        expect(wrapper.findComponent(GitInlineFilter).exists()).toBeTruthy();
        expect(wrapper.findComponent(GitPermissionsTable).exists()).toBeTruthy();
        expect(wrapper.findComponent(GitPermissionsTable).props("repositories")).toStrictEqual([
            { id: 1 },
        ]);
    });
});
