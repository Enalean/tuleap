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

import { describe, it, expect, vi } from "vitest";
import { mount, flushPromises } from "@vue/test-utils";
import { okAsync } from "neverthrow";
import PullRequestAuthor from "./PullRequestAuthor.vue";
import * as tuleap_api from "../../api/tuleap-rest-querier";
import { getGlobalTestOptions } from "../../tests-helpers/global-options-for-tests";
import type { PullRequestInfo } from "../../api/types";

describe("PullRequestAuthor", () => {
    it(`Should display a skeleton when:
        - The pull-request data is loading
        - The author data is loading
        And display the author data when finished`, async () => {
        vi.spyOn(tuleap_api, "fetchUserInfo").mockReturnValue(
            okAsync({
                avatar_url: "/url/to/author_avatar.png",
                user_url: "/url/to/author_profile_page.html",
                display_name: "The Author",
            })
        );

        const wrapper = mount(PullRequestAuthor, {
            global: {
                ...getGlobalTestOptions(),
            },
            props: {
                pull_request_info: null,
            },
        });

        expect(wrapper.find("[data-test=pullrequest-property-skeleton]").exists()).toBe(true);
        expect(wrapper.find("[data-test=pullrequest-author-info]").exists()).toBe(false);

        wrapper.setProps({
            pull_request_info: {
                user_id: 102,
            } as PullRequestInfo,
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=pullrequest-property-skeleton]").exists()).toBe(true);
        expect(wrapper.find("[data-test=pullrequest-author-info]").exists()).toBe(false);

        await flushPromises();

        expect(tuleap_api.fetchUserInfo).toHaveBeenCalledWith(102);

        expect(wrapper.find("[data-test=pullrequest-property-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=pullrequest-author-info]").exists()).toBe(true);
        expect(wrapper.find("[data-test=pullrequest-author-avatar]").attributes("src")).toBe(
            "/url/to/author_avatar.png"
        );

        const author_name = wrapper.find("[data-test=pullrequest-author-name]");
        expect(author_name.attributes("href")).toBe("/url/to/author_profile_page.html");
        expect(author_name.text()).toBe("The Author");
    });
});
