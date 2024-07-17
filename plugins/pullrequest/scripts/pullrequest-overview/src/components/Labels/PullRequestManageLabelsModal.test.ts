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
import { okAsync, errAsync } from "neverthrow";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { Fault } from "@tuleap/fault";
import * as tlp_modal from "@tuleap/tlp-modal";
import type { Modal } from "@tuleap/tlp-modal";
import { LazyboxVueStub } from "../../../tests/stubs/LazyboxVueStub";
import { getGlobalTestOptions } from "../../../tests/helpers/global-options-for-tests";
import * as tuleap_api from "../../api/tuleap-rest-querier";
import { DISPLAY_TULEAP_API_ERROR, PULL_REQUEST_ID_KEY } from "../../constants";
import type { ProjectLabel } from "@tuleap/plugin-pullrequest-rest-api-types";
import PullRequestManageLabelsModal from "./PullRequestManageLabelsModal.vue";

const pull_request_id = 50;
const emergency_label: ProjectLabel = {
    id: 1,
    label: "Emergency",
    is_outline: false,
    color: "red-wine",
};
const easy_fix_label: ProjectLabel = {
    id: 2,
    label: "Eazy fix",
    is_outline: true,
    color: "army-green",
};
const project_labels = [emergency_label, easy_fix_label];

vi.mock("@tuleap/tlp-modal", () => ({
    createModal: vi.fn(),
    EVENT_TLP_MODAL_HIDDEN: "tlp-modal-hidden",
}));

describe("PullRequestManageLabelsModal", () => {
    let post_edition_callback: () => void,
        on_cancel_callback: () => void,
        display_api_error_callback: () => void,
        modal_instance: Modal;

    beforeEach(() => {
        post_edition_callback = vi.fn();
        on_cancel_callback = vi.fn();
        display_api_error_callback = vi.fn();

        modal_instance = {
            show: vi.fn(),
            hide: vi.fn(),
            addEventListener: vi.fn(),
        } as unknown as Modal;

        vi.spyOn(tlp_modal, "createModal").mockReturnValue(modal_instance);
    });

    const getWrapper = (current_labels: ReadonlyArray<ProjectLabel>): VueWrapper => {
        return shallowMount(PullRequestManageLabelsModal, {
            global: {
                ...getGlobalTestOptions(),
                stubs: { "tuleap-lazybox": LazyboxVueStub },
                provide: {
                    [PULL_REQUEST_ID_KEY.valueOf()]: pull_request_id,
                    [DISPLAY_TULEAP_API_ERROR.valueOf()]: display_api_error_callback,
                },
            },
            props: {
                project_labels,
                current_labels,
                post_edition_callback,
                on_cancel_callback,
            },
        });
    };

    describe("Setup", () => {
        it(`Given that some labels are already assigned on the pull-request
            Then lazybox's selection should be set with these labels`, () => {
            const wrapper = getWrapper([emergency_label]);
            const lazybox_stub = wrapper.findComponent(LazyboxVueStub);

            const selection = lazybox_stub.vm.getInitialSelection().map((item) => {
                const label = item.value as ProjectLabel;
                return label.id;
            });
            expect(selection).toStrictEqual([emergency_label.id]);
        });

        it(`Given that no labels are assigned on the pull-request yet
            Then lazybox's selection should not be set`, () => {
            const wrapper = getWrapper([]);
            const lazybox_stub = wrapper.findComponent(LazyboxVueStub);

            expect(lazybox_stub.vm.getInitialSelection()).toStrictEqual([]);
        });
    });

    it("[Save changes] button should save the labels and trigger the post_edition_callback when done", async () => {
        vi.spyOn(tuleap_api, "patchPullRequestLabels").mockReturnValue(okAsync(new Response()));

        const wrapper = getWrapper([]);
        const button = wrapper.find("[data-test=save-labels-button]");

        wrapper.findComponent(LazyboxVueStub).vm.selectItems([easy_fix_label]);

        await button.trigger("click");

        expect(tuleap_api.patchPullRequestLabels).toHaveBeenCalledWith(
            pull_request_id,
            [easy_fix_label.id],
            [],
            [],
        );
        expect(modal_instance.hide).toHaveBeenCalledOnce();
        expect(post_edition_callback).toHaveBeenCalledOnce();
    });

    it("[Save changes] button should save labels created on-the-fly", async () => {
        vi.spyOn(tuleap_api, "patchPullRequestLabels").mockReturnValue(okAsync(new Response()));

        const wrapper = getWrapper([]);
        const button = wrapper.find("[data-test=save-labels-button]");

        wrapper.findComponent(LazyboxVueStub).vm.createItem("Gluten free");

        await button.trigger("click");

        expect(tuleap_api.patchPullRequestLabels).toHaveBeenCalledWith(
            pull_request_id,
            [],
            [],
            ["Gluten free"],
        );
        expect(modal_instance.hide).toHaveBeenCalledOnce();
        expect(post_edition_callback).toHaveBeenCalledOnce();
    });

    it("When an error occurs while saving the labels, Then it should call the display_api_error_callback", async () => {
        const tuleap_api_fault = Fault.fromMessage("Niet!");
        vi.spyOn(tuleap_api, "patchPullRequestLabels").mockReturnValue(errAsync(tuleap_api_fault));

        const wrapper = getWrapper([]);
        const button = wrapper.find("[data-test=save-labels-button]");

        wrapper.findComponent(LazyboxVueStub).vm.selectItems([easy_fix_label]);

        await button.trigger("click");

        expect(display_api_error_callback).toHaveBeenCalledWith(tuleap_api_fault);
    });
});
