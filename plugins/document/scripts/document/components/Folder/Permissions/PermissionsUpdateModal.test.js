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

import { shallowMount } from "@vue/test-utils";
import PermissionsUpdateModal from "./PermissionsUpdateModal.vue";
import * as tlp_modal from "@tuleap/tlp-modal";
import * as handle_errors from "../../../store/actions-helpers/handle-errors";
import emitter from "../../../helpers/emitter";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import { nextTick } from "vue";

describe("PermissionsUpdateModal", () => {
    let factory;
    let load_project_ugroups = jest.fn().mockImplementation(() => {
        return { id: "102_3", label: "Project members" };
    });
    const update_permissions = jest.fn().mockImplementation(() => {
        return { id: "102_3", label: "Project members" };
    });

    beforeEach(() => {
        load_project_ugroups.mockReset();
        update_permissions.mockReset();

        factory = (props = {}, ugroups) => {
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
                            configuration: {
                                namespaced: true,
                                state: {},
                            },
                            error: {
                                namespaced: true,
                                mutations: {
                                    resetModalError: jest.fn(),
                                },
                            },
                        },
                    }),
                },
            });
        };

        let hideFunction = null;
        jest.spyOn(tlp_modal, "createModal").mockReturnValue({
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

    it(`when the modal receives a "show" event, it will open again`, async () => {
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
        await nextTick();
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

    it("When the modal is first opened the project user groups are loaded and the content populated", async () => {
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
        await nextTick();
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
        const handleErrors = jest.spyOn(handle_errors, "handleErrors").mockImplementation(() => {});

        load_project_ugroups = jest.fn().mockImplementation(() => {
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
        await nextTick();
        await nextTick();

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
        wrapper.setProps({ item: item_to_update }, project_ugroups);
        await nextTick();

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
        await nextTick();
        await nextTick();
        expect(wrapper.vm.can_be_submitted).toBe(true);
    });

    it("Reset selected user groups when the modal is closed", () => {
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

        wrapper.setData({
            updated_permissions: {
                apply_permissions_on_children: true,
                can_read: ["102_3"],
                can_write: ["102_3", "138"],
                can_manage: ["102_4"],
            },
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
});
