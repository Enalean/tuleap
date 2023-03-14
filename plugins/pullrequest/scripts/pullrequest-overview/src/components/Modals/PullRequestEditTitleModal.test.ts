/*
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

import type { SpyInstance } from "vitest";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import PullRequestEditTitleModal from "./PullRequestEditTitleModal.vue";
import { getGlobalTestOptions } from "../../tests-helpers/global-options-for-tests";
import * as tlp_modal from "@tuleap/tlp-modal";
import type { Modal } from "@tuleap/tlp-modal";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import * as tuleap_api from "../../api/tuleap-rest-querier";
import { errAsync, okAsync } from "neverthrow";
import { DISPLAY_TULEAP_API_ERROR, UPDATE_PULL_REQUEST_TITLE } from "../../constants";
import * as strict_inject from "@tuleap/vue-strict-inject";
import { Fault } from "@tuleap/fault";

vi.mock("@tuleap/vue-strict-inject");

vi.mock("@tuleap/tlp-modal", () => ({
    createModal: vi.fn(),
    EVENT_TLP_MODAL_HIDDEN: "tlp-modal-hidden",
}));

const noop = (): void => {
    // do nothing
};
let api_error_callback: SpyInstance;
let update_pullrequest_title_callback: SpyInstance;

const getWrapper = (pull_request_id: number): VueWrapper => {
    vi.spyOn(strict_inject, "strictInject").mockImplementation((key) => {
        switch (key) {
            case DISPLAY_TULEAP_API_ERROR:
                return api_error_callback;
            case UPDATE_PULL_REQUEST_TITLE:
                return update_pullrequest_title_callback;
            default:
                return noop;
        }
    });
    return shallowMount(PullRequestEditTitleModal, {
        global: {
            ...getGlobalTestOptions(),
        },
        props: {
            pull_request_info: {
                id: pull_request_id,
                title: "My Pr title <a href='https://example.com'>https://example.com</a>",
                raw_title: "My Pr title https://example.com",
            } as PullRequest,
        },
    });
};

describe("PullRequestEditTitleModal", () => {
    let modal_instance: Modal;

    beforeEach(() => {
        modal_instance = {
            show: vi.fn(),
            addEventListener: vi.fn(),
            hide: vi.fn(),
        } as unknown as Modal;

        vi.spyOn(tlp_modal, "createModal").mockReturnValue(modal_instance);

        api_error_callback = vi.fn();
        update_pullrequest_title_callback = vi.fn();
    });

    it("When pull request is defined, it shows the modal", async () => {
        const wrapper = getWrapper(1);

        wrapper
            .find<HTMLButtonElement>("[data-test=pull-request-open-title-modal-button]")
            .trigger("click");

        await wrapper.vm.$nextTick();

        expect(tlp_modal.createModal).toHaveBeenCalledOnce();
        expect(modal_instance.addEventListener).toHaveBeenCalledWith(
            "tlp-modal-hidden",
            expect.any(Function)
        );
    });

    it("Update title When user submit modal", async () => {
        const pull_request_id = 1;

        vi.spyOn(tuleap_api, "patchTitle").mockReturnValue(
            okAsync({
                title: "My updated title",
            } as PullRequest)
        );

        const wrapper = getWrapper(pull_request_id);

        wrapper
            .find<HTMLButtonElement>("[data-test=pull-request-open-title-modal-button]")
            .trigger("click");

        await wrapper.vm.$nextTick();

        wrapper
            .find<HTMLInputElement>("[data-test=pull-request-edit-title-input]")
            .setValue("My updated title");

        wrapper
            .find<HTMLButtonElement>("[data-test=pull-request-save-changes-button]")
            .trigger("click");

        await wrapper.vm.$nextTick();

        expect(tuleap_api.patchTitle).toHaveBeenCalledWith(pull_request_id, "My updated title");
        expect(update_pullrequest_title_callback).toHaveBeenCalled();
    });

    it("When an error occurs, Then it should call the display_error_callback with the fault", async () => {
        const pull_request_id = 1;

        const fault = Fault.fromMessage("some-reason");
        vi.spyOn(tuleap_api, "patchTitle").mockReturnValue(errAsync(fault));

        const wrapper = getWrapper(pull_request_id);

        wrapper
            .find<HTMLButtonElement>("[data-test=pull-request-open-title-modal-button]")
            .trigger("click");

        await wrapper.vm.$nextTick();

        wrapper
            .find<HTMLInputElement>("[data-test=pull-request-edit-title-input]")
            .setValue("My updated title");

        wrapper
            .find<HTMLButtonElement>("[data-test=pull-request-save-changes-button]")
            .trigger("click");

        await wrapper.vm.$nextTick();

        expect(api_error_callback).toHaveBeenCalledWith(fault);
    });
});
