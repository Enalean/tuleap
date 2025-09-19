/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import PermissionsUpdateModal from "./PermissionsUpdateModal.vue";
import * as tlp_modal from "@tuleap/tlp-modal";
import * as handle_errors from "../../../store/actions-helpers/handle-errors";
import emitter from "../../../helpers/emitter";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import { CAN_MANAGE, CAN_READ, CAN_WRITE } from "../../../constants";
import { PROJECT } from "../../../configuration-keys";
import { ProjectBuilder } from "../../../../tests/builders/ProjectBuilder";

vi.useFakeTimers();

describe("PermissionsUpdateModal", () => {
    let load_project_ugroups = vi.fn().mockImplementation(() => {
        return { id: "102_3", label: "Project members" };
    });
    const update_permissions = vi.fn().mockImplementation(() => {
        return { id: "102_3", label: "Project members" };
    });
    const factory = (props = {}, ugroups = null): VueWrapper<PermissionsUpdateModal> => {
        return shallowMount(PermissionsUpdateModal, {
            props: { ...props },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        permissions: {
                            namespaced: true,
                            state: { project_ugroups: ugroups },
                            actions: {
                                loadProjectUserGroupsIfNeeded: load_project_ugroups,
                                updatePermissions: update_permissions,
                            },
                        },
                        error: {
                            namespaced: true,
                            mutations: {
                                resetModalError: vi.fn(),
                            },
                        },
                    },
                }),
                provide: {
                    [PROJECT.valueOf()]: new ProjectBuilder(1).build(),
                },
            },
        });
    };

    beforeEach(() => {
        load_project_ugroups.mockReset();
        update_permissions.mockReset();

        let hideFunction = null;
        vi.spyOn(tlp_modal, "createModal").mockReturnValue({
            addEventListener(type, listener) {
                hideFunction = listener;
            },
            removeEventListener() {
                hideFunction = null;
            },
            show: () => {},
            hide() {
                if (hideFunction !== null) {
                    hideFunction();
                }
            },
        });
    });

    it(`when the modal receives a "show" event, it will open again`, () => {
        const item_to_update = {
            id: 104,
            title: "My item",
            permissions_for_groups: {
                can_read: [],
                can_write: [],
                can_manage: [{ id: "102_3" }],
            },
        };
        const project_ugroups = [{ id: "102_3", label: "Project members" }];
        const wrapper = factory({ item: item_to_update }, project_ugroups);
        wrapper.vm.reset();
        emitter.emit("show-update-permissions-modal");

        expect(load_project_ugroups).toHaveBeenCalledTimes(2);
    });

    it("Set a loading a state by default", () => {
        const wrapper = factory({ item: {} }, null);

        expect(wrapper.find("[class=document-permissions-modal-loading-state]").exists()).toBe(
            true,
        );
    });

    it("When the modal is first opened the project user groups are loaded and the content populated", () => {
        const item_to_update = {
            id: 104,
            title: "My item",
            permissions_for_groups: {
                can_read: [],
                can_write: [],
                can_manage: [{ id: "102_3" }],
            },
        };
        const project_ugroups = [{ id: "102_3", label: "Project members" }];
        const wrapper = factory({ item: item_to_update }, project_ugroups);
        expect(wrapper.find(".document-permissions-update-container").exists()).toBe(true);
        expect(wrapper.vm.can_be_submitted).toBe(true);

        const updated_permissions_per_groups = {
            can_read: wrapper.vm.updated_permissions.can_read,
            can_write: wrapper.vm.updated_permissions.can_write,
            can_manage: wrapper.vm.updated_permissions.can_manage,
        };
        expect(updated_permissions_per_groups).toEqual(item_to_update.permissions_for_groups);
    });

    it("When the modal is first opened but the project user groups can not be loaded a global error is generated", async () => {
        const handleErrors = vi.spyOn(handle_errors, "handleErrors").mockImplementation(() => {});

        load_project_ugroups = vi.fn().mockImplementation(() => {
            return Promise.reject({});
        });

        const item_to_update = {
            id: 104,
            title: "My item",
            permissions_for_groups: {
                can_read: [],
                can_write: [],
                can_manage: [],
            },
        };
        factory({ item: item_to_update });
        await vi.runOnlyPendingTimersAsync();

        expect(handleErrors).toHaveBeenCalledTimes(1);
    });

    it("Change permissions to update when the bound item is updated", async () => {
        const wrapper = factory({ item: {} });

        const item_to_update = {
            id: 104,
            title: "My item",
            permissions_for_groups: {
                can_read: [],
                can_write: [{ id: "102_3" }],
                can_manage: [{ id: "102_4" }],
            },
        };

        const project_ugroups = [{ id: "102_3", label: "Project members" }];
        await wrapper.setProps({ item: item_to_update }, project_ugroups);

        const updated_permissions_per_groups = {
            can_read: wrapper.vm.updated_permissions.can_read,
            can_write: wrapper.vm.updated_permissions.can_write,
            can_manage: wrapper.vm.updated_permissions.can_manage,
        };
        expect(updated_permissions_per_groups).toEqual(item_to_update.permissions_for_groups);
    });

    it("Send update request when form is submitted", async () => {
        const item = {
            id: 104,
            title: "My item",
            permissions_for_groups: {
                can_read: [],
                can_write: [],
                can_manage: [],
            },
        };

        const project_ugroups = [];
        const wrapper = factory({ item }, project_ugroups);

        wrapper.get("form").trigger("submit.prevent");

        const permissions_to_update = {
            apply_permissions_on_children: false,
            can_read: wrapper.vm.updated_permissions.can_read,
            can_write: wrapper.vm.updated_permissions.can_write,
            can_manage: wrapper.vm.updated_permissions.can_manage,
        };

        expect(update_permissions).toHaveBeenCalledWith(expect.anything(), {
            item: item,
            updated_permissions: permissions_to_update,
        });
        await vi.runOnlyPendingTimersAsync();
        expect(wrapper.vm.can_be_submitted).toBe(true);
    });

    it("Resets selected user groups and apply_permissions_on_children when the modal is closed", () => {
        const item = {
            id: 104,
            title: "My item",
            permissions_for_groups: {
                can_read: [],
                can_write: [],
                can_manage: [],
            },
        };

        const wrapper = factory({ item });

        emitter.emit("update-permissions", {
            label: CAN_READ,
            value: [{ id: "102_3" }],
        });
        emitter.emit("update-permissions", {
            label: CAN_WRITE,
            value: [{ id: "102_3" }, { id: "138" }],
        });
        emitter.emit("update-permissions", {
            label: CAN_MANAGE,
            value: [{ id: "102_4" }],
        });
        wrapper.vm.modal.hide();

        const expected_permissions_to_update_state = {
            apply_permissions_on_children: false,
            can_read: item.permissions_for_groups.can_read,
            can_write: item.permissions_for_groups.can_write,
            can_manage: item.permissions_for_groups.can_manage,
        };
        expect(wrapper.vm.updated_permissions).toEqual(expected_permissions_to_update_state);
    });

    it('When an event "update-apply-permissions-on-children" is received, then it should update do_permissions_apply_on_children', () => {
        const item = {
            id: 104,
            title: "My item",
            permissions_for_groups: {
                can_read: [],
                can_write: [],
                can_manage: [],
            },
        };

        const wrapper = factory({ item });

        emitter.emit("update-apply-permissions-on-children", {
            do_permissions_apply_on_children: true,
        });

        expect(wrapper.vm.updated_permissions).toStrictEqual({
            ...item.permissions_for_groups,
            apply_permissions_on_children: true,
        });
    });
});
