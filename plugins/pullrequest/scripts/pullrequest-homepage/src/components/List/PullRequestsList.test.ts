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
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { okAsync } from "neverthrow";
import * as strict_inject from "@tuleap/vue-strict-inject";
import { PullRequestStub } from "@tuleap/plugin-pullrequest-stub";
import { injection_symbols_stub } from "../../../tests/injection-symbols-stub";
import * as tuleap_api from "../../api/tuleap-rest-querier";
import PullRequestsList from "./PullRequestsList.vue";

const getWrapper = (): VueWrapper => {
    vi.spyOn(strict_inject, "strictInject").mockImplementation(injection_symbols_stub);

    return shallowMount(PullRequestsList);
};

describe("PullRequestsList", () => {
    it("should load all the pull-requests of the repository and display them", async () => {
        vi.spyOn(tuleap_api, "fetchAllPullRequests").mockReturnValue(
            okAsync([
                PullRequestStub.buildOpenPullRequest({ id: 6 }),
                PullRequestStub.buildOpenPullRequest({ id: 5 }),
                PullRequestStub.buildOpenPullRequest({ id: 3 }),
            ]),
        );

        const wrapper = getWrapper();

        await wrapper.vm.$nextTick();

        expect(wrapper.findAll("[data-test=pull-request-card]").length).toBe(3);
    });
});
