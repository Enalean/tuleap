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
import PullRequestStats from "./PullRequestStats.vue";
import { getGlobalTestOptions } from "../../../tests/helpers/global-options-for-tests";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";

describe("PullRequestStats", () => {
    it("should display a skeleton while the pull request is loading, and the stats when finished", async () => {
        const wrapper = mount(PullRequestStats, {
            global: {
                ...getGlobalTestOptions(),
            },
            props: {
                pull_request_info: null,
            },
        });

        expect(wrapper.find("[data-test=pullrequest-property-skeleton]").exists()).toBe(true);
        expect(wrapper.find("[data-test=pullrequest-stats]").exists()).toBe(false);

        await wrapper.setProps({
            pull_request_info: {
                short_stat: {
                    lines_added: 75,
                    lines_removed: 200,
                },
            } as PullRequest,
        });

        expect(wrapper.find("[data-test=pullrequest-property-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=pullrequest-stats]").exists()).toBe(true);
        expect(wrapper.find("[data-test=pullrequest-added-lines]").text()).toBe("+75");
        expect(wrapper.find("[data-test=pullrequest-removed-lines]").text()).toBe("-200");
    });
});
