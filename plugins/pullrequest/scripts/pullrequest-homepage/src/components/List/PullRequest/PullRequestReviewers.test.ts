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

import { describe, it, expect } from "vitest";
import { shallowMount } from "@vue/test-utils";
import { PullRequestStub } from "@tuleap/plugin-pullrequest-stub";
import type { User } from "@tuleap/plugin-pullrequest-rest-api-types";
import PullRequestReviewers from "./PullRequestReviewers.vue";

const reviewers: readonly User[] = [
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

describe("PullRequestReviewers", () => {
    it("should display the reviewers avatars", () => {
        const wrapper = shallowMount(PullRequestReviewers, {
            props: {
                pull_request: PullRequestStub.buildOpenPullRequest({
                    reviewers,
                }),
            },
        });

        const reviewers_avatars = wrapper.findAll("[data-test=pull-request-card-reviewer-avatar]");

        expect(reviewers_avatars).toHaveLength(reviewers.length);

        const [jolasti_avatar, jdoe_avatar, jz5_avatar] = reviewers_avatars;

        expect(jolasti_avatar.attributes("title")).toBe(reviewers[0].display_name);
        expect(jolasti_avatar.attributes("src")).toBe(reviewers[0].avatar_url);

        expect(jdoe_avatar.attributes("title")).toBe(reviewers[1].display_name);
        expect(jdoe_avatar.attributes("src")).toBe(reviewers[1].avatar_url);

        expect(jz5_avatar.attributes("title")).toBe(reviewers[2].display_name);
        expect(jz5_avatar.attributes("src")).toBe(reviewers[2].avatar_url);
    });
});
