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
import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper, DOMWrapper } from "@vue/test-utils";
import { mount } from "@vue/test-utils";
import PullRequestReviewerList from "./PullRequestReviewerList.vue";
import { getGlobalTestOptions } from "../../tests-helpers/global-options-for-tests";
import type { Reviewer, User } from "@tuleap/plugin-pullrequest-rest-api-types";
import { errAsync, okAsync } from "neverthrow";
import * as tuleap_api from "../../api/tuleap-rest-querier";
import * as strict_inject from "@tuleap/vue-strict-inject";
import { DISPLAY_TULEAP_API_ERROR, PULL_REQUEST_ID_KEY } from "../../constants";
import { Fault } from "@tuleap/fault";

vi.mock("@tuleap/vue-strict-inject");
const noop = (): void => {
    // do nothing
};
let api_error_callback: SpyInstance;
describe("PullRequestReviewerList", () => {
    beforeEach(() => {
        api_error_callback = vi.fn();
    });
    const getWrapper = (): VueWrapper => {
        vi.spyOn(strict_inject, "strictInject").mockImplementation((key) => {
            switch (key) {
                case DISPLAY_TULEAP_API_ERROR:
                    return api_error_callback;
                case PULL_REQUEST_ID_KEY:
                    return 1;
                default:
                    return noop;
            }
        });
        return mount(PullRequestReviewerList, {
            global: {
                ...getGlobalTestOptions(),
            },
        });
    };

    it(`Should display a skeleton when the reviewer data is loading
        And display the author data when finished`, async () => {
        const reviewers: Reviewer = {
            users: [
                {
                    avatar_url: "/url/to/reviewer_avatar.png",
                    user_url: "/url/to/reviewer_profile_page.html",
                    display_name: "A reviewer",
                } as User,
                {
                    avatar_url: "/url/to/other_reviewer_avatar.png",
                    user_url: "/url/to/other_reviewer_profile_page.html",
                    display_name: "An other reviewer",
                } as User,
            ],
        } as Reviewer;
        vi.spyOn(tuleap_api, "fetchReviewersInfo").mockReturnValue(okAsync(reviewers));

        const wrapper = getWrapper();
        expect(wrapper.find("[data-test=pullrequest-property-skeleton]").exists()).toBe(true);
        expect(wrapper.find("[data-test=pullrequest-reviewer-info]").exists()).toBe(false);

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=pullrequest-property-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=pullrequest-reviewer-info]").exists()).toBe(true);

        const avatar: DOMWrapper<HTMLImageElement>[] = wrapper.findAll(
            "[data-test=pullrequest-reviewer-avatar]"
        );

        expect(avatar).toHaveLength(2);

        expect(avatar[0].attributes().src).toBe("/url/to/reviewer_avatar.png");
        expect(avatar[1].attributes().src).toBe("/url/to/other_reviewer_avatar.png");
    });

    it(`Should display an empty state when nobody is reviewing the pull request`, async () => {
        const reviewers: Reviewer = {
            users: [],
        } as Reviewer;
        vi.spyOn(tuleap_api, "fetchReviewersInfo").mockReturnValue(okAsync(reviewers));

        const wrapper = getWrapper();
        expect(wrapper.find("[data-test=pullrequest-property-skeleton]").exists()).toBe(true);
        expect(wrapper.find("[data-test=pullrequest-reviewer-info]").exists()).toBe(false);

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=pullrequest-property-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=pullrequest-reviewer-info]").exists()).toBe(true);
        expect(wrapper.find("[data-test=pull-request-reviewers-empty-state]").exists()).toBe(true);
    });

    it("When an error occurs, Then it should call the display_error_callback with the fault", async () => {
        const fault = Fault.fromMessage("some-reason");
        vi.spyOn(tuleap_api, "fetchReviewersInfo").mockReturnValue(errAsync(fault));

        const wrapper = getWrapper();
        expect(wrapper.find("[data-test=pullrequest-property-skeleton]").exists()).toBe(true);
        expect(wrapper.find("[data-test=pullrequest-reviewer-info]").exists()).toBe(false);

        await wrapper.vm.$nextTick();

        expect(api_error_callback).toHaveBeenCalledWith(fault);
    });
});
