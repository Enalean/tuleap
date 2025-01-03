/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import CreateBranchPrefixModal from "./CreateBranchPrefixModal.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import type { GitLabRepository } from "../../../type";

jest.useFakeTimers();

describe("CreateBranchPrefixModal", () => {
    let store_options = {};
    let setCreateBranchPrefixModalSpy: jest.Mock;

    beforeEach(() => {
        setCreateBranchPrefixModalSpy = jest.fn();
        store_options = {
            modules: {
                state: {
                    create_branch_prefix_repository: {
                        integration_id: "gitlab-1",
                    } as unknown as GitLabRepository,
                },
                gitlab: {
                    namespaced: true,
                    mutations: {
                        setCreateBranchPrefixModal: setCreateBranchPrefixModalSpy,
                    },
                },
            },
        };
    });

    function instantiateComponent(): VueWrapper<InstanceType<typeof CreateBranchPrefixModal>> {
        return shallowMount(CreateBranchPrefixModal, {
            props: {},
            global: { ...getGlobalTestOptions(store_options) },
        });
    }

    describe("The feedback display", () => {
        it("shows the error feedback if there is any REST error", async () => {
            const wrapper = instantiateComponent();

            wrapper.vm.message_error_rest = "error";
            await jest.useFakeTimers();

            expect(wrapper.find("[data-test=create-branch-prefix-fail]").exists()).toBe(true);
        });

        it("does not show the error feedback if there is no REST error", async () => {
            const wrapper = instantiateComponent();

            wrapper.vm.message_error_rest = "";
            await jest.useFakeTimers();

            expect(wrapper.find("[data-test=create-branch-prefix-fail]").exists()).toBe(false);
        });
    });

    describe("The 'Save' button display", () => {
        it("disables the button and displays the spinner during the Gitlab integration", async () => {
            const wrapper = instantiateComponent();

            wrapper.vm.is_updating_gitlab_repository = true;
            wrapper.vm.message_error_rest = "";
            await jest.useFakeTimers();

            expect(wrapper.find("[data-test=create-branch-prefix-modal-icon-spin]").exists()).toBe(
                true,
            );

            const save_button = wrapper.find<HTMLButtonElement>(
                "[data-test=create-branch-prefix-modal-save-button]",
            ).element;
            expect(save_button.disabled).toBe(true);
        });

        it("disables the button but does NOT display the spinner if the update failed", async () => {
            const wrapper = instantiateComponent();

            wrapper.vm.is_updating_gitlab_repository = false;
            wrapper.vm.message_error_rest = "error";
            await jest.useFakeTimers();

            expect(wrapper.find("[data-test=create-branch-prefix-modal-icon-spin]").exists()).toBe(
                false,
            );

            const save_button = wrapper.find<HTMLButtonElement>(
                "[data-test=create-branch-prefix-modal-save-button]",
            ).element;
            expect(save_button.disabled).toBe(true);
        });

        it("let enabled the button when everything are ok and there when is no update", async () => {
            const wrapper = instantiateComponent();

            wrapper.vm.is_updating_gitlab_repository = false;
            wrapper.vm.message_error_rest = "";
            await jest.useFakeTimers();

            expect(wrapper.find("[data-test=create-branch-prefix-modal-icon-spin]").exists()).toBe(
                false,
            );

            const save_button = wrapper.find<HTMLButtonElement>(
                "[data-test=create-branch-prefix-modal-save-button]",
            ).element;
            expect(save_button.disabled).toBe(false);
        });
    });
});
