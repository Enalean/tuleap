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

import { describe, beforeEach, it, expect, vi } from "vitest";
import type { SpyInstance } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import * as tlp_dropdown from "@tuleap/tlp-dropdown";
import { PullRequestStub } from "@tuleap/plugin-pullrequest-stub";
import type { User } from "@tuleap/plugin-pullrequest-rest-api-types";
import PullRequestReviewers from "./PullRequestReviewers.vue";

const reviewers: User[] = [
    {
        id: 101,
        display_name: "Joe l'asticot (jolasti)",
        avatar_url: "url/to/jolasti.png",
    } as User,
    {
        id: 102,
        display_name: "John Doe (jdoe)",
        avatar_url: "url/to/jdoe.png",
    } as User,
    {
        id: 5,
        display_name: "Johann Zarco (jz5)",
        avatar_url: "url/to/jz5.png",
    } as User,
];

vi.mock("@tuleap/tlp-dropdown", () => ({
    createDropdown: (): void => {
        // do nothing
    },
}));

describe("PullRequestReviewers", () => {
    let createDropdown: SpyInstance;

    beforeEach(() => {
        createDropdown = vi.spyOn(tlp_dropdown, "createDropdown");
    });

    const getWrapper = (): VueWrapper =>
        shallowMount(PullRequestReviewers, {
            props: {
                pull_request: PullRequestStub.buildOpenPullRequest({
                    reviewers,
                }),
            },
        });

    it("should display the reviewers avatars", () => {
        const reviewers_avatars = getWrapper().findAll(
            "[data-test=pull-request-card-reviewer-avatar]",
        );

        expect(reviewers_avatars).toHaveLength(reviewers.length);

        const [jolasti_avatar, jdoe_avatar, jz5_avatar] = reviewers_avatars;

        expect(createDropdown).not.toHaveBeenCalled();

        expect(jolasti_avatar.attributes("title")).toBe(reviewers[0].display_name);
        expect(jolasti_avatar.attributes("src")).toBe(reviewers[0].avatar_url);

        expect(jdoe_avatar.attributes("title")).toBe(reviewers[1].display_name);
        expect(jdoe_avatar.attributes("src")).toBe(reviewers[1].avatar_url);

        expect(jz5_avatar.attributes("title")).toBe(reviewers[2].display_name);
        expect(jz5_avatar.attributes("src")).toBe(reviewers[2].avatar_url);
    });

    it(`When there are more than three reviewers, then it should:
        - display the three first reviewers in the list
        - display the number of remaining reviewers
        - display the remaining reviewers in a dropdown
    `, () => {
        const fourth_reviewer = {
            id: 104,
            display_name: "Joe the hobo (jhobo)",
            avatar_url: "url/to/jhobo.png",
        } as User;

        const fifth_reviewer = {
            id: 105,
            display_name: "Joe Dalton (jdalton)",
            avatar_url: "url/to/jdalton.png",
        } as User;

        reviewers.push(fourth_reviewer, fifth_reviewer);

        const wrapper = getWrapper();

        const displayed_reviewers = wrapper.findAll(
            "[data-test=pull-request-card-reviewer-avatar]",
        );
        const remaining_reviewers = wrapper.findAll(
            "[data-test=pull-request-card-remaining-reviewer-avatar]",
        );
        const remaining_reviewers_count = wrapper.find(
            "[data-test=pull-request-card-remaining-reviewer-count]",
        );

        expect(createDropdown).toHaveBeenCalledOnce();
        expect(displayed_reviewers).toHaveLength(3);
        expect(remaining_reviewers).toHaveLength(2);
        expect(remaining_reviewers_count.text()).toBe("+2");
    });
});
