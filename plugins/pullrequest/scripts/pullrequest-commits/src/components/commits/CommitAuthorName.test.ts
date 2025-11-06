/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { PullRequestCommit, User } from "@tuleap/plugin-pullrequest-rest-api-types";
import CommitAuthorName from "./CommitAuthorName.vue";
import { CommitStub } from "../../../tests/stubs/CommitStub";

describe("CommitAuthorName", () => {
    const getWrapper = (commit: PullRequestCommit): VueWrapper =>
        shallowMount(CommitAuthorName, {
            propsData: { commit },
        });

    it("When the author of the commit is unknown, then it should only display their name", () => {
        const commit = CommitStub.fromUnknownAuthor("4a178d8dc96b284801177865d5897da5e1ff8030", {
            author_name: "Jane Doe",
            author_email: "jane.doe@example.com",
        });
        const wrapper = getWrapper(commit);

        expect(wrapper.find("[data-test=unknown-author-name]").text()).toBe(commit.author_name);
        expect(wrapper.find("[data-test=known-author-name]").exists()).toBe(false);
    });

    it("When the author is a known user, then it should display their display name as a link to their profile page", () => {
        const user = { display_name: "Mister commiter" } as User;
        const wrapper = getWrapper(
            CommitStub.fromExistingAuthor("4a178d8dc96b284801177865d5897da5e1ff8030", user),
        );

        const link = wrapper.find("[data-test=known-author-name]");
        expect(link.text()).toBe(user.display_name);
        expect(link.attributes("href")).toBe(user.user_url);
        expect(wrapper.find("[data-test=unknown-author-name]").exists()).toBe(false);
    });
});
