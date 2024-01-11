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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { Store } from "@tuleap/vuex-store-wrapper-jest";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import CreateBranchPrefixModal from "./CreateBranchPrefixModal.vue";
import * as gitlab_error_handler from "../../../gitlab/gitlab-error-handler";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import { createLocalVueForTests } from "../../../helpers/local-vue-for-tests";

describe("CreateBranchPrefixModal", () => {
    let store: Store;

    async function instantiateComponent(): Promise<Wrapper<CreateBranchPrefixModal>> {
        store = createStoreMock(
            {},
            {
                gitlab: {
                    create_branch_prefix_repository: {
                        integration_id: 10,
                        label: "wow gitlab",
                        create_branch_prefix: "",
                    },
                },
                create_branch_prefix: "previous/",
            },
        );

        return shallowMount(CreateBranchPrefixModal, {
            propsData: {},
            mocks: { $store: store },
            localVue: await createLocalVueForTests(),
        });
    }

    describe("The feedback display", () => {
        it("shows the error feedback if there is any REST error", async () => {
            const wrapper = await instantiateComponent();

            wrapper.setData({ message_error_rest: "error" });
            await wrapper.vm.$nextTick();

            expect(wrapper.find("[data-test=create-branch-prefix-fail]").exists()).toBe(true);
        });

        it("does not show the error feedback if there is no REST error", async () => {
            const wrapper = await instantiateComponent();

            wrapper.setData({ message_error_rest: "" });
            await wrapper.vm.$nextTick();

            expect(wrapper.find("[data-test=create-branch-prefix-fail]").exists()).toBe(false);
        });
    });

    describe("The 'Save' button display", () => {
        it("disables the button and displays the spinner during the Gitlab integration", async () => {
            const wrapper = await instantiateComponent();

            wrapper.setData({ is_updating_gitlab_repository: true, message_error_rest: "" });
            await wrapper.vm.$nextTick();

            expect(wrapper.find("[data-test=create-branch-prefix-modal-icon-spin]").exists()).toBe(
                true,
            );

            const save_button = wrapper.find("[data-test=create-branch-prefix-modal-save-button]")
                .element as HTMLButtonElement;
            if (!(save_button instanceof HTMLButtonElement)) {
                throw new Error("Could not find the help button");
            }
            expect(save_button.disabled).toBe(true);
        });

        it("disables the button but does NOT display the spinner if the update failed", async () => {
            const wrapper = await instantiateComponent();

            wrapper.setData({ is_updating_gitlab_repository: false, message_error_rest: "error" });
            await wrapper.vm.$nextTick();

            expect(wrapper.find("[data-test=create-branch-prefix-modal-icon-spin]").exists()).toBe(
                false,
            );

            const save_button = wrapper.find("[data-test=create-branch-prefix-modal-save-button]")
                .element as HTMLButtonElement;
            if (!(save_button instanceof HTMLButtonElement)) {
                throw new Error("Could not find the help button");
            }
            expect(save_button.disabled).toBe(true);
        });

        it("let enabled the button when everything are ok and there when is no update", async () => {
            const wrapper = await instantiateComponent();

            wrapper.setData({ is_updating_gitlab_repository: false, message_error_rest: "" });
            await wrapper.vm.$nextTick();

            expect(wrapper.find("[data-test=create-branch-prefix-modal-icon-spin]").exists()).toBe(
                false,
            );

            const save_button = wrapper.find("[data-test=create-branch-prefix-modal-save-button]")
                .element as HTMLButtonElement;
            if (!(save_button instanceof HTMLButtonElement)) {
                throw new Error("Could not find the help button");
            }
            expect(save_button.disabled).toBe(false);
        });
    });

    describe("updateCreateBranchPrefix", () => {
        it("set the message error and display this error in the console if there is error during the update", async () => {
            const wrapper = await instantiateComponent();

            wrapper.setData({
                create_branch_prefix: "dev-",
            });

            await wrapper.vm.$nextTick();

            jest.spyOn(store, "dispatch").mockRejectedValue(
                new FetchWrapperError("", {
                    status: 400,
                    json: (): Promise<{ error: { code: number; message: string } }> =>
                        Promise.resolve({ error: { code: 400, message: "Error on server" } }),
                } as Response),
            );

            jest.spyOn(gitlab_error_handler, "handleError");
            // We also display the error in the console.
            jest.spyOn(global.console, "error").mockImplementation();

            wrapper.find("[data-test=create-branch-prefix-modal-save-button]").trigger("click");
            await wrapper.vm.$nextTick();
            await wrapper.vm.$nextTick();
            await wrapper.vm.$nextTick();

            expect(wrapper.vm.$data.message_error_rest).toBe("400 Error on server");
        });
    });
});
