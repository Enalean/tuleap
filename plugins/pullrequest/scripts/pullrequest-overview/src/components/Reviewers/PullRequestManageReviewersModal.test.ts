/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { okAsync, errAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import * as strict_inject from "@tuleap/vue-strict-inject";
import * as tlp_modal from "@tuleap/tlp-modal";
import type { Modal } from "@tuleap/tlp-modal";
import type { User } from "@tuleap/plugin-pullrequest-rest-api-types";
import { LazyboxVueStub } from "../../../tests/stubs/LazyboxVueStub";
import { DISPLAY_TULEAP_API_ERROR, PULL_REQUEST_ID_KEY } from "../../constants";
import { getGlobalTestOptions } from "../../../tests/helpers/global-options-for-tests";
import * as tuleap_api from "../../api/tuleap-rest-querier";
import PullRequestManageReviewersModal from "./PullRequestManageReviewersModal.vue";

const pull_request_id = 50;
const joe_lasticot_id = 101;
const joe_hobo_id = 102;

const joe_lasticot = {
    id: joe_lasticot_id,
    avatar_url: "/url/to/user_avatar.png",
    display_name: "Joe l'Asticot (jolasti)",
} as User;

const reviewers: ReadonlyArray<User> = [
    joe_lasticot,
    {
        id: joe_hobo_id,
        avatar_url: "/url/to/user_avatar.png",
        display_name: "Joe the hobo (jhobo)",
    } as User,
];

vi.mock("@tuleap/vue-strict-inject");
vi.mock("@tuleap/tlp-modal", () => ({
    createModal: vi.fn(),
    EVENT_TLP_MODAL_HIDDEN: "tlp-modal-hidden",
}));

describe("PullRequestManageReviewersModal", () => {
    let on_save_callback: (reviewers: ReadonlyArray<User>) => void,
        on_cancel_callback: () => void,
        display_api_error_callback: () => void,
        modal_instance: Modal;

    beforeEach(() => {
        display_api_error_callback = vi.fn();
        on_save_callback = vi.fn();
        on_cancel_callback = (): void => {
            // Do nothing
        };

        modal_instance = {
            show: vi.fn(),
            hide: vi.fn(),
            addEventListener: vi.fn(),
        } as unknown as Modal;

        vi.spyOn(tlp_modal, "createModal").mockReturnValue(modal_instance);
    });

    const getWrapper = (reviewers_list: ReadonlyArray<User>): VueWrapper => {
        vi.spyOn(strict_inject, "strictInject").mockImplementation((key): unknown => {
            switch (key) {
                case PULL_REQUEST_ID_KEY:
                    return pull_request_id;
                case DISPLAY_TULEAP_API_ERROR:
                    return display_api_error_callback;
                default:
                    throw new Error("Tried to strictInject a value while it was not mocked");
            }
        });

        return shallowMount(PullRequestManageReviewersModal, {
            global: {
                ...getGlobalTestOptions(),
                stubs: { "tuleap-lazybox": LazyboxVueStub },
            },
            props: {
                reviewers_list,
                on_save_callback,
                on_cancel_callback,
            },
        });
    };

    describe("Setup", () => {
        it(`Given that some reviewers are already assigned on the pull-request
            Then lazybox's selection should be set with these reviewers`, () => {
            const wrapper = getWrapper(reviewers);
            const lazybox_stub = wrapper.findComponent(LazyboxVueStub);

            const selection = lazybox_stub.vm.getInitialSelection().map((item) => {
                const user = item.value as User;
                return user.id;
            });
            expect(selection).toStrictEqual([joe_lasticot_id, joe_hobo_id]);
        });

        it(`Given that no reviewers are assigned on the pull-request yet
            Then lazybox's selection should not be set`, () => {
            const wrapper = getWrapper([]);
            const lazybox_stub = wrapper.findComponent(LazyboxVueStub);

            expect(lazybox_stub.vm.getInitialSelection()).toStrictEqual([]);
        });
    });

    describe("All reviewers will be cleared info", () => {
        it(`Given that some reviewers were assigned on the pull-request
            When the user removes them from the selection
            Then an informative text should be displayed in the modal's footer`, async () => {
            const wrapper = getWrapper(reviewers);

            await wrapper.vm.$nextTick();

            expect(wrapper.find("[data-test=text-info-all-reviewers-cleared]").exists()).toBe(
                false
            );

            const lazybox_stub = wrapper.findComponent(LazyboxVueStub);
            lazybox_stub.vm.selectItems([]);

            await wrapper.vm.$nextTick();
            expect(wrapper.find("[data-test=text-info-all-reviewers-cleared]").exists()).toBe(true);
        });
    });

    describe("saveReviewers()", () => {
        it("When the reviewers have been saved, Then it should trigger the on_save_callback", async () => {
            vi.spyOn(tuleap_api, "putReviewers").mockReturnValue(okAsync(new Response()));

            const wrapper = getWrapper([]);

            const lazybox_stub = wrapper.findComponent(LazyboxVueStub);
            lazybox_stub.vm.selectItems([joe_lasticot]);

            await wrapper.find("[data-test=save-reviewers-button]").trigger("click");

            expect(tuleap_api.putReviewers).toHaveBeenCalledOnce();
            expect(tuleap_api.putReviewers).toHaveBeenCalledWith(pull_request_id, [joe_lasticot]);

            expect(modal_instance.hide).toHaveBeenCalledOnce();

            expect(on_save_callback).toHaveBeenCalledOnce();
            expect(on_save_callback).toHaveBeenCalledWith([joe_lasticot]);
        });

        it(`When an error occurres while saving the reviewers
            Then it should trigger the "api fault callback" with the fault`, async () => {
            const tuleap_api_fault = Fault.fromMessage("This user cannot be reviewer");

            vi.spyOn(tuleap_api, "putReviewers").mockReturnValue(errAsync(tuleap_api_fault));

            await getWrapper([]).find("[data-test=save-reviewers-button]").trigger("click");

            expect(display_api_error_callback).toHaveBeenCalledOnce();
            expect(display_api_error_callback).toHaveBeenCalledWith(tuleap_api_fault);
        });
    });
});
