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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../support/global-options-for-tests";
import DeleteConfirmationModal from "./DeleteConfirmationModal.vue";

jest.useFakeTimers();

describe("DeleteConfirmationModal", () => {
    const confirm_selector = '[data-test-action="confirm"]';
    const spinner_selector = '[data-test-type="spinner"]';

    let confirm: jest.Mock;
    let confirmResolve: (value: unknown) => void;

    let wrapper: VueWrapper<InstanceType<typeof DeleteConfirmationModal>>;

    beforeEach(() => {
        confirm = jest.fn().mockReturnValue(
            new Promise((resolve) => {
                confirmResolve = resolve;
            }),
        );

        wrapper = shallowMount(DeleteConfirmationModal, {
            props: {
                submit_label: "Confirmation message",
                default_failed_message: "Failed message",
                on_submit: confirm,
            },
            global: { ...getGlobalTestOptions() },
        });
    });

    it("does not show spinner", () => {
        expect(wrapper.find(spinner_selector).exists()).toBeFalsy();
    });
    it("enables confirm button", () => {
        expect(wrapper.get(confirm_selector).attributes().disabled).toBeUndefined();
    });

    describe("when confirming", () => {
        beforeEach(async () => {
            await wrapper.get(confirm_selector).trigger("click");
        });

        it("shows spinner", () => {
            expect(wrapper.find(spinner_selector).exists()).toBeTruthy();
        });
        it("disables confirm button", () => {
            expect(wrapper.get(confirm_selector).attributes().disabled).toBe("");
        });
        it("calls confirm method", () => {
            expect(confirm).toHaveBeenCalled();
        });
    });

    describe("when deletion is completed", () => {
        beforeEach(async () => {
            wrapper.get(confirm_selector).trigger("click");
            confirmResolve("resolved");
            await jest.runOnlyPendingTimersAsync();
        });

        it("does not show spinner any more", () => {
            expect(wrapper.find(spinner_selector).exists()).toBeFalsy();
        });
        it("enables confirm button", () => {
            expect(wrapper.get(confirm_selector).attributes().disabled).toBeUndefined();
        });
    });
});
