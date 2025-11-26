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

import { describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ApprovalTableReviewers from "./ApprovalTableReviewers.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import { PROJECT, USER_ID } from "../../../configuration-keys";
import { ProjectBuilder } from "../../../../tests/builders/ProjectBuilder";
import { ItemBuilder } from "../../../../tests/builders/ItemBuilder";
import type { ApprovalTableReviewer } from "../../../type";
import { UserBuilder } from "../../../../tests/builders/UserBuilder";

describe("ApprovalTableReviewers", () => {
    function getWrapper(
        reviewers: ReadonlyArray<ApprovalTableReviewer>,
        is_readonly: boolean,
    ): VueWrapper<InstanceType<typeof ApprovalTableReviewers>> {
        return shallowMount(ApprovalTableReviewers, {
            props: {
                item: new ItemBuilder(123).build(),
                reviewers,
                is_readonly,
            },
            global: {
                ...getGlobalTestOptions({}),
                provide: {
                    [USER_ID.valueOf()]: 102,
                    [PROJECT.valueOf()]: new ProjectBuilder(101).build(),
                },
            },
        });
    }

    it("Should display empty state when no reviewers", () => {
        const wrapper = getWrapper([], false);

        expect(wrapper.find("[data-test=no-reviewer]").exists()).toBe(true);
    });

    it("Should display each reviewers", () => {
        const wrapper = getWrapper(
            [
                {
                    user: new UserBuilder(102).build(),
                    rank: 1,
                    review_date: null,
                    state: "Not yet",
                    comment: "",
                    version_id: null,
                    version_name: "",
                },
                {
                    user: new UserBuilder(103).build(),
                    rank: 2,
                    review_date: "2025-11-27 17:28:35",
                    state: "Approved",
                    comment: "",
                    version_id: null,
                    version_name: "2",
                },
            ],
            false,
        );

        const rows = wrapper.findAll("[data-test=reviewer-row]");
        expect(rows).toHaveLength(2);
        // Row 0
        expect(rows[0].classes()).not.toContain("reviewer-not-current");
        expect(rows[0].find("[data-test=reviewer-state]").text()).toBe("Not yet");
        expect(rows[0].find("[data-test=reviewer-state] > a").exists()).toBe(true);
        // Row 1
        expect(rows[1].classes()).toContain("reviewer-not-current");
        expect(rows[1].find("[data-test=reviewer-state]").text()).toBe("Approved");
        expect(rows[1].find("[data-test=reviewer-state] > a").exists()).toBe(false);
    });

    it("Should display each reviewers in readonly", () => {
        const wrapper = getWrapper(
            [
                {
                    user: new UserBuilder(102).build(),
                    rank: 1,
                    review_date: null,
                    state: "Not yet",
                    comment: "",
                    version_id: null,
                    version_name: "",
                },
                {
                    user: new UserBuilder(103).build(),
                    rank: 2,
                    review_date: "2025-11-27 17:28:35",
                    state: "Approved",
                    comment: "",
                    version_id: null,
                    version_name: "2",
                },
            ],
            true,
        );

        const rows = wrapper.findAll("[data-test=reviewer-row]");
        expect(rows).toHaveLength(2);
        // Row 0
        expect(rows[0].classes()).toContain("reviewer-not-current");
        expect(rows[0].find("[data-test=reviewer-state]").text()).toBe("Not yet");
        expect(rows[0].find("[data-test=reviewer-state] > a").exists()).toBe(false);
        // Row 1
        expect(rows[1].classes()).toContain("reviewer-not-current");
        expect(rows[1].find("[data-test=reviewer-state]").text()).toBe("Approved");
        expect(rows[1].find("[data-test=reviewer-state] > a").exists()).toBe(false);
    });
});
