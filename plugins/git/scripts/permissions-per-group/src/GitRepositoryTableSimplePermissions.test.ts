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
import GitRepositoryTableSimplePermissions from "./GitRepositoryTableSimplePermissions.vue";
import type { RepositorySimplePermissions } from "./type";
import localVueForTest from "./helper/local-vue-for-test";

describe("GitRepositoryTableSimplePermissions", () => {
    let propsData = {};

    function instantiateComponent(): Wrapper<GitRepositoryTableSimplePermissions> {
        return shallowMount(GitRepositoryTableSimplePermissions, {
            propsData,
            localVue: localVueForTest,
        });
    }

    it("When permissions exist, Then GitPermissionsBadge si displayed for each group", () => {
        propsData = {
            repositoryPermission: {
                has_fined_grained_permissions: false,
                name: "repo",
                readers: [
                    {
                        is_custom: false,
                        is_project_admin: false,
                        is_static: false,
                        ugroup_name: "project_members_readers",
                    },
                ],
                writers: [
                    {
                        is_custom: false,
                        is_project_admin: false,
                        is_static: false,
                        ugroup_name: "project_members_writers",
                    },
                ],
                rewinders: [
                    {
                        is_custom: false,
                        is_project_admin: false,
                        is_static: false,
                        ugroup_name: "project_members_rewinders",
                    },
                ],
                repository_id: 1,
                url: "/git/?action=repo_management&group_id=101&repo_id=1&pane=perms",
            } as RepositorySimplePermissions,
        };

        const wrapper = instantiateComponent();

        expect(
            wrapper.find("[data-test=git-permission-badge-project_members_readers]").exists(),
        ).toBeTruthy();
        expect(
            wrapper.find("[data-test=git-permission-badge-project_members_writers]").exists(),
        ).toBeTruthy();
        expect(
            wrapper.find("[data-test=git-permission-badge-project_members_rewinders]").exists(),
        ).toBeTruthy();
    });
});
