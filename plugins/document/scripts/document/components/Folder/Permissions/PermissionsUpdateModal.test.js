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
import localVue from "../../../helpers/local-vue.js";

import PermissionsUpdateModal from "./PermissionsUpdateModal.vue";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";

import * as tlp from "tlp";
import * as handle_errors from "../../../store/actions-helpers/handle-errors.js";
import EventBus from "../../../helpers/event-bus.js";

jest.mock("tlp");

describe("PermissionsUpdateModal", () => {
    let factory, store;

    beforeEach(() => {
        store = createStoreMock({}, { project_ugroups: null, error: {} });

        factory = (props = {}) => {
            return shallowMount(PermissionsUpdateModal, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };

        let hideFunction = null;
        jest.spyOn(tlp, "modal").mockReturnValue({
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

    it("Set a loading a state by default", () => {
        const wrapper = factory({ item: {} });

        expect(wrapper.find("[class=document-permissions-modal-loading-state]").exists()).toBe(
            true
        );
    });

    it("When the modal is first opened the project user groups are loaded and the content populated", async () => {
        store.dispatch.mockImplementation((name) => {
            if (name === "loadProjectUserGroupsIfNeeded") {
                store.state.project_ugroups = [{ id: "102_3", label: "Project members" }];
            }
        });

        const item_to_update = {
            id: 104,
            title: "My item",
            permissions_for_groups: {
                can_read: [],
                can_write: [],
                can_manage: [{ id: "102_3" }],
            },
        };
        const wrapper = factory({ item: item_to_update });

        expect(wrapper.vm.can_be_submitted).toBe(false);

        EventBus.$emit("show-update-permissions-modal");
        await wrapper.vm.$nextTick().then(() => {});
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

        store.dispatch.mockImplementation(() => {
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
        const wrapper = factory({ item: item_to_update });

        EventBus.$emit("show-update-permissions-modal");
        await wrapper.vm.$nextTick().then(() => {});

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

        wrapper.setProps({ item: item_to_update });
        await wrapper.vm.$nextTick();

        const updated_permissions_per_groups = {
            can_read: wrapper.vm.updated_permissions.can_read,
            can_write: wrapper.vm.updated_permissions.can_write,
            can_manage: wrapper.vm.updated_permissions.can_manage,
        };
        expect(updated_permissions_per_groups).toEqual(item_to_update.permissions_for_groups);
    });

    it("Send update request when form is submitted", async () => {
        const item_to_update = {
            id: 104,
            title: "My item",
            permissions_for_groups: {
                can_read: [],
                can_write: [],
                can_manage: [],
            },
        };

        store.state.project_ugroups = [];
        const wrapper = factory({ item: item_to_update });

        const expectedActionName = "updatePermissions";
        store.dispatch.mockImplementation(function (actionName) {
            if (actionName !== expectedActionName) {
                return;
            }
            expect(wrapper.vm.can_be_submitted).toBe(false);
        });

        wrapper.get("form").trigger("submit.prevent");

        const permissions_to_update = {
            apply_permissions_on_children: false,
            can_read: wrapper.vm.updated_permissions.can_read,
            can_write: wrapper.vm.updated_permissions.can_write,
            can_manage: wrapper.vm.updated_permissions.can_manage,
        };
        expect(store.dispatch).toHaveBeenCalledWith(expectedActionName, [
            item_to_update,
            permissions_to_update,
        ]);
        await wrapper.vm.$nextTick().then(() => {});
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
