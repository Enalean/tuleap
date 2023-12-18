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

import { describe, it, expect, beforeEach } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { PullRequestStub } from "@tuleap/plugin-pullrequest-stub";
import { getGlobalTestOptions } from "../../../../tests/global-options-for-tests";
import PullRequestBrokenBadge from "./PullRequestBrokenBadge.vue";

describe("PullRequestBrokenBadge", () => {
    let is_git_reference_broken: boolean;

    const getWrapper = (): VueWrapper => {
        return shallowMount(PullRequestBrokenBadge, {
            global: {
                ...getGlobalTestOptions(),
            },
            props: {
                pull_request: PullRequestStub.buildOpenPullRequest({ is_git_reference_broken }),
            },
        });
    };

    beforeEach(() => {
        is_git_reference_broken = true;
    });

    it("When the git reference of the pull-request is broken, then it should be displayed", () => {
        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=broken-git-ref-badge]").exists()).toBe(true);
    });

    it("When the git reference of the pull-request is NOT broken, then it should be NOT displayed", () => {
        is_git_reference_broken = false;

        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=broken-git-ref-badge]").exists()).toBe(false);
    });
});
