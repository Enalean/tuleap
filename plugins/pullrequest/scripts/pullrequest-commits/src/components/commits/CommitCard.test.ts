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
import type { PullRequestCommit } from "@tuleap/plugin-pullrequest-rest-api-types";
import { COMMIT_BUILD_STATUS_PENDING } from "@tuleap/plugin-pullrequest-constants";
import { CommitStub } from "../../../tests/stubs/CommitStub";
import CommitCard from "./CommitCard.vue";
import CommitStatusBadge from "./CommitStatusBadge.vue";

const commit_id = "d8fb8fc8e9d384402eec582fe504eae109f6fc9a";

describe("CommitCard", () => {
    const getWrapper = (commit: PullRequestCommit): VueWrapper =>
        shallowMount(CommitCard, {
            propsData: { commit },
        });

    it("When the commit has a CI build status, then it should display the commit-status-badge component", () => {
        const wrapper = getWrapper(
            CommitStub.withCIBuildStatus(commit_id, {
                name: COMMIT_BUILD_STATUS_PENDING,
                date: new Date().toDateString(),
            }),
        );
        expect(wrapper.findComponent(CommitStatusBadge).exists()).toBe(true);
    });

    it("When the commit has no CI build status, then it should NOT display the commit-status-badge component", () => {
        const wrapper = getWrapper(CommitStub.withDefaults(commit_id));
        expect(wrapper.findComponent(CommitStatusBadge).exists()).toBe(false);
    });
});
