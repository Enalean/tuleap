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
import { tlp } from "tlp-mocks";

import PermissionsUpdateModal from "./PermissionsUpdateModal.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper.js";

import {
    rewire$getProjectUserGroupsWithoutServiceSpecialUGroups,
    restore as restoreUGroupsHelper
} from "../../../helpers/permissions/ugroups.js";
import {
    rewire$handleErrors,
    restore as restoreHandleErrorsHelper
} from "../../../store/actions-helpers/handle-errors.js";
import EventBus from "../../../helpers/event-bus.js";

describe("PermissionsUpdateModal", () => {
    let factory, store;

    beforeEach(() => {
        store = createStoreMock({}, { project_id: 102, error: {} });

        factory = (props = {}) => {
            return shallowMount(PermissionsUpdateModal, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store }
            });
        };

        let hideFunction = null;
        tlp.modal.and.returnValue({
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
            }
        });
    });

    afterEach(() => {
        restoreUGroupsHelper();
        restoreHandleErrorsHelper();
    });

    it("Set a loading a state by default", () => {
        const wrapper = factory({ item: {} });

        expect(wrapper.find("[class=document-permissions-modal-loading-state]").exists()).toBe(
            true
        );
    });

    it("When the modal is first opened the project user groups are loaded and the content populated", async () => {
        const getProjectUserGroupsWithoutServiceSpecialUGroupsSpy = jasmine.createSpy(
            "getProjectUserGroupsWithoutServiceSpecialUGroups"
        );
        rewire$getProjectUserGroupsWithoutServiceSpecialUGroups(
            getProjectUserGroupsWithoutServiceSpecialUGroupsSpy
        );
        getProjectUserGroupsWithoutServiceSpecialUGroupsSpy.and.returnValue(
            Promise.resolve([{ id: "102_3", label: "Project members" }])
        );

        const item_to_update = {
            id: 104,
            title: "My item",
            permissions_for_groups: {
                can_read: [],
                can_write: [],
                can_manage: [{ id: "102_3" }]
            }
        };
        const wrapper = factory({ item: item_to_update });

        EventBus.$emit("show-update-permissions-modal");
        await wrapper.vm.$nextTick().then(() => {});
        expect(wrapper.find("[data-test=document-permissions-update-selectors]").exists()).toBe(
            true
        );

        const nb_calls_after_first_opening_of_the_modal = getProjectUserGroupsWithoutServiceSpecialUGroupsSpy.calls.count();
        EventBus.$emit("show-update-permissions-modal");
        expect(getProjectUserGroupsWithoutServiceSpecialUGroupsSpy).toHaveBeenCalledTimes(
            nb_calls_after_first_opening_of_the_modal
        );

        expect(wrapper.vm.updated_permissions).toEqual(item_to_update.permissions_for_groups);
    });

    it("When the modal is first opened but the project user groups can not be loaded a global error is generated", async () => {
        const handleErrors = jasmine.createSpy("handleErrors");
        rewire$handleErrors(handleErrors);
        const getProjectUserGroupsWithoutServiceSpecialUGroupsSpy = jasmine.createSpy(
            "getProjectUserGroupsWithoutServiceSpecialUGroups"
        );
        rewire$getProjectUserGroupsWithoutServiceSpecialUGroups(
            getProjectUserGroupsWithoutServiceSpecialUGroupsSpy
        );
        getProjectUserGroupsWithoutServiceSpecialUGroupsSpy.and.returnValue(Promise.reject({}));

        const item_to_update = {
            id: 104,
            title: "My item",
            permissions_for_groups: {
                can_read: [],
                can_write: [],
                can_manage: []
            }
        };
        const wrapper = factory({ item: item_to_update });

        EventBus.$emit("show-update-permissions-modal");
        await wrapper.vm.$nextTick().then(() => {});

        expect(handleErrors).toHaveBeenCalledTimes(1);
    });

    it("Change permissions to update when the bound item is updated", () => {
        const wrapper = factory({ item: {} });

        const item_to_update = {
            id: 104,
            title: "My item",
            permissions_for_groups: {
                can_read: [],
                can_write: [{ id: "102_3" }],
                can_manage: [{ id: "102_4" }]
            }
        };

        wrapper.setProps({ item: item_to_update });

        expect(wrapper.vm.updated_permissions).toEqual(item_to_update.permissions_for_groups);
    });

    it("Send update request when form is submitted", () => {
        const item_to_update = {
            id: 104,
            title: "My item",
            permissions_for_groups: {
                can_read: [],
                can_write: [],
                can_manage: []
            }
        };
        const wrapper = factory({ item: item_to_update });

        wrapper.find("form").trigger("submit.prevent");

        expect(store.dispatch).toHaveBeenCalledWith("updatePermissions", [
            item_to_update,
            item_to_update.permissions_for_groups
        ]);
    });

    it("Reset selected user groups when the modal is closed", () => {
        const item = {
            id: 104,
            title: "My item",
            permissions_for_groups: {
                can_read: [],
                can_write: [],
                can_manage: []
            }
        };

        const wrapper = factory({ item });

        wrapper.setData({
            updated_permissions: {
                can_read: ["102_3"],
                can_write: ["102_3", "138"],
                can_manage: ["102_4"]
            }
        });
        wrapper.vm.modal.hide();

        expect(wrapper.vm.updated_permissions).toEqual(item.permissions_for_groups);
    });
});
