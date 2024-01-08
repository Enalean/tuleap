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

import { describe, it, expect } from "vitest";
import { shallowMount } from "@vue/test-utils";
import GitPermissionsTableRepository from "./GitPermissionsTableRepository.vue";
import GitRepositoryTableSimplePermissions from "./GitRepositoryTableSimplePermissions.vue";
import type {
    FineGrainedPermission,
    RepositoryFineGrainedPermissions,
    RepositorySimplePermissions,
} from "./type";
import GitRepositoryTableFineGrainedPermissionsRepository from "./GitRepositoryTableFineGrainedPermissionsRepository.vue";
import GitRepositoryTableFineGrainedPermission from "./GitRepositoryTableFineGrainedPermission.vue";
import { createGettext } from "vue3-gettext";

describe("GitPermissionsTableRepository", () => {
    it("When repository hasn't fine grained permission, Then GitRepositoryTableSimplePermissions is displayed", () => {
        const wrapper = shallowMount(GitPermissionsTableRepository, {
            props: {
                filter: "",
                repository: {
                    repository_id: 1,
                    has_fined_grained_permissions: false,
                } as RepositorySimplePermissions,
            },
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });

        expect(wrapper.findComponent(GitRepositoryTableSimplePermissions).exists()).toBeTruthy();
        expect(
            wrapper
                .findComponent(GitRepositoryTableSimplePermissions)
                .props("repository_permission"),
        ).toStrictEqual({ repository_id: 1, has_fined_grained_permissions: false });
        expect(
            wrapper.findComponent(GitRepositoryTableFineGrainedPermissionsRepository).exists(),
        ).toBeFalsy();
        expect(wrapper.findComponent(GitRepositoryTableFineGrainedPermission).exists()).toBeFalsy();
    });

    it("When repository is hidden and hasn't fine grained permission, Then no components are displayed", async () => {
        const wrapper = shallowMount(GitPermissionsTableRepository, {
            props: {
                filter: "",
                repository: {
                    repository_id: 1,
                    has_fined_grained_permissions: false,
                    name: "Repository",
                } as RepositorySimplePermissions,
            },
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });

        await wrapper.setProps({ filter: "lorem" });

        expect(wrapper.findComponent(GitRepositoryTableSimplePermissions).exists()).toBeFalsy();
        expect(
            wrapper.findComponent(GitRepositoryTableFineGrainedPermissionsRepository).exists(),
        ).toBeFalsy();
        expect(wrapper.findComponent(GitRepositoryTableFineGrainedPermission).exists()).toBeFalsy();
    });

    it("When repository is hidden and has fine grained permission, Then no components are displayed", async () => {
        const wrapper = shallowMount(GitPermissionsTableRepository, {
            props: {
                filter: "",
                repository: {
                    repository_id: 1,
                    has_fined_grained_permissions: true,
                    name: "Repository",
                } as RepositoryFineGrainedPermissions,
            },
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });

        await wrapper.setProps({ filter: "lorem" });

        expect(wrapper.findComponent(GitRepositoryTableSimplePermissions).exists()).toBeFalsy();
        expect(
            wrapper.findComponent(GitRepositoryTableFineGrainedPermissionsRepository).exists(),
        ).toBeFalsy();
        expect(wrapper.findComponent(GitRepositoryTableFineGrainedPermission).exists()).toBeFalsy();
    });

    it("When repository has fine grained permission, Then GitRepositoryTableFineGrainedPermissionsRepository is displayed", () => {
        const props = {
            filter: "",
            repository: {
                repository_id: 1,
                name: "Repository",
                has_fined_grained_permissions: true,
                fine_grained_permission: [
                    {
                        id: 101,
                    },
                    {
                        id: 102,
                    },
                ] as FineGrainedPermission[],
            } as RepositoryFineGrainedPermissions,
        };

        const wrapper = shallowMount(GitPermissionsTableRepository, {
            props,
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });

        expect(wrapper.findComponent(GitRepositoryTableSimplePermissions).exists()).toBeFalsy();
        expect(
            wrapper.findComponent(GitRepositoryTableFineGrainedPermissionsRepository).exists(),
        ).toBeTruthy();
        expect(
            wrapper.find("[data-test=git-repository-fine-grained-permission-101]").exists(),
        ).toBeTruthy();
        expect(
            wrapper.find("[data-test=git-repository-fine-grained-permission-102]").exists(),
        ).toBeTruthy();
    });
});
