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

import { describe, it, expect, vi, beforeEach } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import PullRequestErrorModal from "./PullRequestErrorModal.vue";
import { getGlobalTestOptions } from "../../../tests/global-options-for-tests";
import * as tlp_modal from "@tuleap/tlp-modal";
import type { Modal } from "@tuleap/tlp-modal";
import { Fault } from "@tuleap/fault";

vi.mock("@tuleap/tlp-modal", () => ({
    createModal: vi.fn(),
    EVENT_TLP_MODAL_HIDDEN: "tlp-modal-hidden",
}));

const getWrapper = (fault: Fault | null): VueWrapper => {
    return shallowMount(PullRequestErrorModal, {
        global: {
            ...getGlobalTestOptions(),
        },
        props: {
            fault: fault,
        },
    });
};

describe("PullRequestErrorModal", () => {
    let modal_instance: Modal;

    beforeEach(() => {
        modal_instance = {
            show: vi.fn(),
            addEventListener: vi.fn(),
            is_shown: false,
        } as unknown as Modal;

        vi.spyOn(tlp_modal, "createModal").mockReturnValue(modal_instance);
    });

    it("When a fault has been detected, it shows the modal", async () => {
        const wrapper = getWrapper(null);

        expect(tlp_modal.createModal).toHaveBeenCalledOnce();
        expect(modal_instance.show).not.toHaveBeenCalled();
        expect(modal_instance.addEventListener).toHaveBeenCalledWith(
            "tlp-modal-hidden",
            expect.any(Function),
        );

        wrapper.setProps({
            fault: Fault.fromMessage("Something wrong has occurred."),
        });

        await wrapper.vm.$nextTick();

        expect(modal_instance.show).toHaveBeenCalledOnce();
    });

    it("Shows the error details when the user clicks on [Show details]", async () => {
        const fault = Fault.fromMessage("Forbidden: Nope");
        const wrapper = getWrapper(fault);

        expect(wrapper.find("[data-test=pull-request-homepage-error-modal-details]").exists()).toBe(
            false,
        );
        wrapper
            .find<HTMLButtonElement>("[data-test=pull-request-homepage-error-modal-show-details]")
            .trigger("click");

        await wrapper.vm.$nextTick();
        expect(wrapper.find("[data-test=pull-request-homepage-error-modal-details]").exists()).toBe(
            true,
        );
        expect(
            wrapper.find("[data-test=pull-request-homepage-error-modal-details-message]").text(),
        ).toStrictEqual(String(fault));
    });

    it("When the modal is already open and the fault has changed, Then it should ignore it", async () => {
        const wrapper = getWrapper(Fault.fromMessage("Something wrong has occurred."));

        modal_instance.is_shown = true;

        wrapper.setProps({
            fault: Fault.fromMessage("Something wrong has occurred (again)."),
        });

        await wrapper.vm.$nextTick();
        expect(modal_instance.show).not.toHaveBeenCalled();
    });
});
