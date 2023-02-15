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
import { shallowMount, flushPromises } from "@vue/test-utils";
import { okAsync } from "neverthrow";
import type { RouteLocationNormalizedLoaded } from "vue-router";
import * as router from "vue-router";
import * as tuleap_api from "../api/tuleap-rest-querier";
import { getGlobalTestOptions } from "../tests-helpers/global-options-for-tests";
import OverviewPane from "./OverviewPane.vue";

vi.mock("vue-router");

const PULLREQUEST_ID = "15";

describe("OverviewPane", () => {
    it("Should fetch the pullrequest info and display its title", async () => {
        vi.spyOn(router, "useRoute").mockImplementationOnce(
            () =>
                ({
                    params: {
                        id: PULLREQUEST_ID,
                    },
                } as unknown as RouteLocationNormalizedLoaded)
        );

        vi.spyOn(tuleap_api, "fetchPullRequestInfo").mockReturnValue(
            okAsync({
                title: "My pull request title",
            })
        );

        const wrapper = shallowMount(OverviewPane, {
            global: {
                ...getGlobalTestOptions(),
            },
        });

        expect(wrapper.find("[data-test=pullrequest-title-skeleton]").exists()).toBe(true);

        await flushPromises();

        expect(tuleap_api.fetchPullRequestInfo).toHaveBeenCalledWith(PULLREQUEST_ID);
        expect(wrapper.find("[data-test=pullrequest-title]").text()).toBe("My pull request title");
        expect(wrapper.find("[data-test=pullrequest-title-skeleton]").exists()).toBe(false);
    });
});
