/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { RouterLinkStub, shallowMount } from "@vue/test-utils";
import ApprovalTableReviewModal from "./ApprovalTableReviewModal.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import { USER_LOCALE, USER_TIMEZONE } from "../../../configuration-keys";
import { ItemBuilder } from "../../../../tests/builders/ItemBuilder";
import { ApprovalTableBuilder } from "../../../../tests/builders/ApprovalTableBuilder";
import { ApprovalTableReviewerBuilder } from "../../../../tests/builders/ApprovalTableReviewerBuilder";
import * as rest_querier from "../../../api/approval-table-rest-querier";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";

vi.useFakeTimers();

describe("ApprovalTableReviewModal", () => {
    let trigger: HTMLButtonElement;

    function getWrapper(): VueWrapper<InstanceType<typeof ApprovalTableReviewModal>> {
        return shallowMount(ApprovalTableReviewModal, {
            props: {
                item: new ItemBuilder(123).build(),
                trigger,
                reviewer: new ApprovalTableReviewerBuilder(102).build(),
                table: new ApprovalTableBuilder(35).build(),
            },
            global: {
                ...getGlobalTestOptions({}),
                provide: {
                    [USER_LOCALE.valueOf()]: "fr_FR",
                    [USER_TIMEZONE.valueOf()]: "Europe/Paris",
                },
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });
    }

    beforeEach(() => {
        const doc = document.implementation.createHTMLDocument();
        trigger = doc.createElement("button");
    });

    it("Should display error when API fails", async () => {
        const putReview = vi
            .spyOn(rest_querier, "putReview")
            .mockReturnValue(errAsync(Fault.fromMessage("Oh no!")));
        const wrapper = getWrapper();
        trigger.click();
        await vi.runOnlyPendingTimersAsync();

        await wrapper.find("[data-test=send-review-button]").trigger("click");

        expect(putReview).toHaveBeenCalledWith(123, "not_yet", "", false);
        expect(wrapper.find("[data-test=review-modal-error]").text()).toContain("Oh no!");
    });

    it("Should emit event when submitting", async () => {
        const putReview = vi.spyOn(rest_querier, "putReview").mockReturnValue(okAsync(null));
        const wrapper = getWrapper();
        trigger.click();
        await vi.runOnlyPendingTimersAsync();

        await wrapper.find("[data-test=review-select-state]").setValue("comment_only");
        await wrapper.find("[data-test=review-comment]").setValue("This is my comment");

        await wrapper.find("[data-test=send-review-button]").trigger("click");

        expect(putReview).toHaveBeenCalledWith(123, "comment_only", "This is my comment", false);
        expect(wrapper.emitted("refresh-data")).not.toBe(undefined);
    });
});
