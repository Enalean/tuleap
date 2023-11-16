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

import { describe, beforeEach, it, expect, vi } from "vitest";
import { shallowMount, RouterLinkStub } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import OverviewTabs from "./OverviewTabs.vue";
import { getGlobalTestOptions } from "../../tests/helpers/global-options-for-tests";
import { OVERVIEW_APP_BASE_URL_KEY, PULL_REQUEST_ID_KEY, VIEW_OVERVIEW_NAME } from "../constants";
import * as strict_inject from "@tuleap/vue-strict-inject";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";

vi.mock("@tuleap/vue-strict-inject");

const APP_BASE_URL = "https://example.com/";
const PULLREQUEST_ID = 15;

describe("OverviewTabs", () => {
    let is_git_reference_broken: boolean;

    const getWrapper = (): VueWrapper => {
        return shallowMount(OverviewTabs, {
            global: {
                stubs: {
                    RouterLink: RouterLinkStub,
                },
                ...getGlobalTestOptions(),
            },
            props: {
                pull_request: {
                    is_git_reference_broken,
                } as PullRequest,
            },
        });
    };
    beforeEach(() => {
        is_git_reference_broken = false;

        vi.spyOn(strict_inject, "strictInject").mockImplementation((key) => {
            switch (key) {
                case OVERVIEW_APP_BASE_URL_KEY:
                    return APP_BASE_URL;
                case PULL_REQUEST_ID_KEY:
                    return PULLREQUEST_ID;
                default:
                    throw new Error("Tried to strictInject a value while it was not mocked");
            }
        });
    });

    it("should build the tabs with proper urls", () => {
        const wrapper = getWrapper();

        expect(
            wrapper.findComponent<typeof RouterLinkStub>("[data-test=tab-overview]").props().to,
        ).toStrictEqual({
            name: VIEW_OVERVIEW_NAME,
        });
        expect(wrapper.find("[data-test=tab-commits]").attributes("href")).toBe(
            `${APP_BASE_URL}#/pull-requests/${PULLREQUEST_ID}/commits`,
        );
        expect(wrapper.find("[data-test=tab-changes]").attributes("href")).toBe(
            `${APP_BASE_URL}#/pull-requests/${PULLREQUEST_ID}/files`,
        );
    });

    it("should not display the [Commits] nor [Changes] tabs when the pull-request git reference is broken", () => {
        is_git_reference_broken = true;

        const wrapper = getWrapper();

        expect(
            wrapper.findComponent<typeof RouterLinkStub>("[data-test=tab-overview]").exists(),
        ).toBe(true);
        expect(wrapper.find("[data-test=tab-commits]").exists()).toBe(false);
        expect(wrapper.find("[data-test=tab-changes]").exists()).toBe(false);
    });
});
