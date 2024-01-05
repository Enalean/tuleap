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

import { shallowMount } from "@vue/test-utils";
import GitRepositoryTableFineGrainedPermission from "./GitRepositoryTableFineGrainedPermission.vue";
import { createGettext } from "vue3-gettext";

describe("GitRepositoryTableFineGrainedPermission", () => {
    it("When there are writers and rewinders permissions, Then GitPermissionsBadge is displayed", () => {
        const wrapper = shallowMount(GitRepositoryTableFineGrainedPermission, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            props: {
                fine_grained_permissions: {
                    id: 100,
                    branch: "master",
                    tag: "",
                    writers: [
                        {
                            is_project_admin: false,
                            is_static: false,
                            is_custom: false,
                            ugroup_name: "project_member_writters",
                        },
                    ],
                    rewinders: [
                        {
                            is_project_admin: false,
                            is_static: false,
                            is_custom: false,
                            ugroup_name: "project_member_rewinders",
                        },
                    ],
                },
            },
        });

        expect(
            wrapper.find("[data-test=git-permission-badge-project_member_writters]").exists(),
        ).toBeTruthy();
        expect(
            wrapper.find("[data-test=git-permission-badge-project_member_rewinders]").exists(),
        ).toBeTruthy();
    });
});
