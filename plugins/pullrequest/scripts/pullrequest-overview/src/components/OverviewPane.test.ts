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

import { describe, it, expect, vi, beforeEach } from "vitest";
import { shallowMount, flushPromises } from "@vue/test-utils";
import { okAsync, errAsync } from "neverthrow";
import type { RouteLocationNormalizedLoaded } from "vue-router";
import * as router from "vue-router";
import { Fault } from "@tuleap/fault";
import * as tuleap_api from "../api/tuleap-rest-querier";
import OverviewPane from "./OverviewPane.vue";
import type { PullRequest, User } from "@tuleap/plugin-pullrequest-rest-api-types";
import * as tooltip from "@tuleap/tooltip";

vi.mock("vue-router");

vi.mock("@tuleap/tooltip", () => ({
    loadTooltips: (): void => {
        // do nothing
    },
}));

const pull_request_id = 15;
const user_id = 102;
const mockFetchPullRequestSuccess = (): void => {
    vi.spyOn(tuleap_api, "fetchPullRequestInfo").mockReturnValue(
        okAsync({
            title: "My pull request title",
            user_id,
        } as PullRequest),
    );
};

describe("OverviewPane", () => {
    beforeEach(() => {
        vi.spyOn(tooltip, "loadTooltips").mockImplementation(() => {
            // do nothing
        });

        vi.spyOn(router, "useRoute").mockImplementationOnce(
            () =>
                ({
                    params: {
                        id: String(pull_request_id),
                    },
                }) as unknown as RouteLocationNormalizedLoaded,
        );
    });
    it(`Should fetch the pull request info using its id provided in the route parameters
        Then the pull request author using the id provided in the previous payload`, async () => {
        mockFetchPullRequestSuccess();

        vi.spyOn(tuleap_api, "fetchUserInfo").mockReturnValue(
            okAsync({
                id: user_id,
            } as User),
        );

        shallowMount(OverviewPane);

        await flushPromises();

        expect(tuleap_api.fetchPullRequestInfo).toHaveBeenCalledWith(pull_request_id);
        expect(tuleap_api.fetchUserInfo).toHaveBeenCalledWith(user_id);
    });

    it(`When an error occurres while retrieving the pull-request
        Then should not try to retrieve the pull-request author`, async () => {
        vi.spyOn(tuleap_api, "fetchPullRequestInfo").mockReturnValue(
            errAsync(Fault.fromMessage("Forbidden")),
        );

        vi.spyOn(tuleap_api, "fetchUserInfo");

        shallowMount(OverviewPane);
        await flushPromises();

        expect(tuleap_api.fetchUserInfo).not.toHaveBeenCalled();
    });
});
