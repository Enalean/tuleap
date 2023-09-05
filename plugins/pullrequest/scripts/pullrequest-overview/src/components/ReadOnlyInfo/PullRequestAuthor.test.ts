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
import { mount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import type { User } from "@tuleap/plugin-pullrequest-rest-api-types";
import PullRequestAuthor from "./PullRequestAuthor.vue";
import { getGlobalTestOptions } from "../../../tests/helpers/global-options-for-tests";

describe("PullRequestAuthor", () => {
    const getWrapper = (): VueWrapper => {
        return mount(PullRequestAuthor, {
            global: {
                ...getGlobalTestOptions(),
            },
            props: {
                pull_request_author: null,
            },
        });
    };

    it(`Should display a skeleton when the author data is loading
        And display the author data when finished`, async () => {
        const wrapper = getWrapper();
        expect(wrapper.find("[data-test=pullrequest-property-skeleton]").exists()).toBe(true);
        expect(wrapper.find("[data-test=pullrequest-author-info]").exists()).toBe(false);

        wrapper.setProps({
            pull_request_author: {
                avatar_url: "/url/to/author_avatar.png",
                user_url: "/url/to/author_profile_page.html",
                display_name: "The Author",
            } as User,
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=pullrequest-property-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=pullrequest-author-info]").exists()).toBe(true);
        expect(wrapper.find("[data-test=pullrequest-author-avatar]").attributes("src")).toBe(
            "/url/to/author_avatar.png",
        );

        const author_name = wrapper.find("[data-test=pullrequest-author-name]");
        expect(author_name.attributes("href")).toBe("/url/to/author_profile_page.html");
        expect(author_name.text()).toBe("The Author");
    });
});
