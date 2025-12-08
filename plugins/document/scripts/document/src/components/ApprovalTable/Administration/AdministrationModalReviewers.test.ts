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
import { shallowMount } from "@vue/test-utils";
import AdministrationModalReviewers from "./AdministrationModalReviewers.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import * as ugroups from "../../../helpers/permissions/ugroups";
import * as rest_querier from "../../../api/approval-table-rest-querier";
import { okAsync } from "neverthrow";
import { PROJECT, USER_LOCALE } from "../../../configuration-keys";
import { ProjectBuilder } from "../../../../tests/builders/ProjectBuilder";
import { ItemBuilder } from "../../../../tests/builders/ItemBuilder";
import { ApprovalTableReviewerBuilder } from "../../../../tests/builders/ApprovalTableReviewerBuilder";

vi.useFakeTimers();

vi.mock("@tuleap/list-picker"); // ResizeObserver is not defined

describe("AdministrationModalReviewers", () => {
    function getWrapper(): VueWrapper<InstanceType<typeof AdministrationModalReviewers>> {
        return shallowMount(AdministrationModalReviewers, {
            props: {
                item: new ItemBuilder(123).build(),
                is_doing_something: false,
                table_reviewers_value: [
                    new ApprovalTableReviewerBuilder(102).withRank(0).build(),
                    new ApprovalTableReviewerBuilder(103).withRank(1).build(),
                    new ApprovalTableReviewerBuilder(104).withRank(2).build(),
                    new ApprovalTableReviewerBuilder(105).withRank(3).build(),
                    new ApprovalTableReviewerBuilder(106).withRank(4).build(),
                ],
                table_reviewers_to_add_value: [],
                table_reviewers_group_to_add_value: [],
            },
            global: {
                ...getGlobalTestOptions({}),
                provide: {
                    [USER_LOCALE.valueOf()]: "en_US",
                    [PROJECT.valueOf()]: new ProjectBuilder(101).build(),
                },
            },
        });
    }

    beforeEach(() => {
        vi.spyOn(ugroups, "loadProjectUserGroups").mockReturnValue(
            okAsync([
                { id: "101_3", label: "Project Members", short_name: "project_members" },
                { id: "154", label: "My_Group", short_name: "my_group" },
            ]),
        );
    });

    it("Should change order of reviewers", async () => {
        const wrapper = getWrapper();

        await vi.runOnlyPendingTimersAsync();

        await wrapper
            .findAll("[data-test=reviewer-row]")[1]
            .find("[data-test=rank-up]")
            .trigger("click");
        await wrapper
            .findAll("[data-test=reviewer-row]")[2]
            .find("[data-test=rank-down]")
            .trigger("click");
        await wrapper
            .findAll("[data-test=reviewer-row]")[4]
            .find("[data-test=rank-top]")
            .trigger("click");

        expect(wrapper.vm.table_reviewers_value).toStrictEqual([
            new ApprovalTableReviewerBuilder(106).withRank(0).build(),
            new ApprovalTableReviewerBuilder(103).withRank(1).build(),
            new ApprovalTableReviewerBuilder(102).withRank(2).build(),
            new ApprovalTableReviewerBuilder(105).withRank(3).build(),
            new ApprovalTableReviewerBuilder(104).withRank(4).build(),
        ]);
    });

    it("Should remove the reviewer", async () => {
        const wrapper = getWrapper();

        await vi.runOnlyPendingTimersAsync();

        await wrapper
            .findAll("[data-test=reviewer-row]")[2]
            .find("[data-test=remove-reviewer]")
            .trigger("click");

        expect(wrapper.vm.table_reviewers_value).toStrictEqual([
            new ApprovalTableReviewerBuilder(102).withRank(0).build(),
            new ApprovalTableReviewerBuilder(103).withRank(1).build(),
            new ApprovalTableReviewerBuilder(105).withRank(2).build(),
            new ApprovalTableReviewerBuilder(106).withRank(3).build(),
        ]);
    });

    it("Should send a reminder to the reviewer", async () => {
        const postReminder = vi
            .spyOn(rest_querier, "postApprovalTableReviewerReminder")
            .mockReturnValue(okAsync(null));
        const wrapper = getWrapper();

        await vi.runOnlyPendingTimersAsync();

        await wrapper
            .findAll("[data-test=reviewer-row]")[1]
            .find("[data-test=reviewer-send-reminder]")
            .trigger("click");

        expect(postReminder).toHaveBeenCalledWith(123, 103);
    });
});
