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

import { describe, it, expect, beforeEach, vi } from "vitest";
import type { SpyInstance } from "vitest";
import { okAsync, errAsync } from "neverthrow";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount, flushPromises } from "@vue/test-utils";
import { Fault } from "@tuleap/fault";
import { PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN } from "@tuleap/tlp-relative-date";
import {
    PULL_REQUEST_COMMENT_ELEMENT_TAG_NAME,
    PULL_REQUEST_COMMENT_SKELETON_ELEMENT_TAG_NAME,
} from "@tuleap/plugin-pullrequest-comments";
import * as tuleap_api from "../../api/tuleap-rest-querier";
import { getGlobalTestOptions } from "../../tests-helpers/global-options-for-tests";
import {
    CURRENT_USER_AVATAR_URL,
    CURRENT_USER_ID,
    DISPLAY_TULEAP_API_ERROR,
    OVERVIEW_APP_BASE_URL_KEY,
    PULL_REQUEST_ID_KEY,
    USER_DATE_TIME_FORMAT_KEY,
    USER_LOCALE_KEY,
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
} from "../../constants";
import OverviewThreads from "./OverviewThreads.vue";
import OverviewThreadsEmptyState from "./OverviewThreadsEmptyState.vue";
import type { TimelineItem } from "@tuleap/plugin-pullrequest-rest-api-types";

describe("OverviewThreads", () => {
    let display_error_callback: SpyInstance;

    beforeEach(() => {
        display_error_callback = vi.fn();
    });

    const getWrapper = (): VueWrapper => {
        return shallowMount(OverviewThreads, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [OVERVIEW_APP_BASE_URL_KEY as symbol]: new URL("https://example.com"),
                    [DISPLAY_TULEAP_API_ERROR as symbol]: display_error_callback,
                    [PULL_REQUEST_ID_KEY as symbol]: "15",
                    [CURRENT_USER_ID as symbol]: 102,
                    [CURRENT_USER_AVATAR_URL as symbol]: "/url/to/user_102_profile_page.html",
                    [USER_DATE_TIME_FORMAT_KEY as symbol]: "d/m/Y H:i",
                    [USER_LOCALE_KEY as symbol]: "fr_FR",
                    [USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY as symbol]:
                        PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN,
                },
                stubs: {
                    [PULL_REQUEST_COMMENT_ELEMENT_TAG_NAME]: true,
                    [PULL_REQUEST_COMMENT_SKELETON_ELEMENT_TAG_NAME]: true,
                },
            },
        });
    };

    it("should fetch all the comments and display them as threads (only root comments)", async () => {
        vi.spyOn(tuleap_api, "fetchPullRequestTimelineItems").mockReturnValue(
            okAsync([
                { id: 102, parent_id: 0, content: "What do you think?" } as TimelineItem,
                { id: 103, parent_id: 102, content: "I'm ok with that" } as TimelineItem,
            ])
        );

        const wrapper = getWrapper();
        expect(wrapper.find("[data-test=pull-request-threads]").exists()).toBe(false);
        expect(wrapper.find("[data-test=pull-request-threads-spinner]").exists()).toBe(true);

        await flushPromises();

        expect(display_error_callback).not.toHaveBeenCalled();
        expect(wrapper.find("[data-test=pull-request-threads-spinner]").exists()).toBe(false);

        const threads = wrapper.find("[data-test=pull-request-threads]");
        expect(threads.exists()).toBe(true);
        expect(threads.element.childElementCount).toBe(1);

        const displayed_thread = wrapper.find("[data-test=pull-request-thread]");

        expect(displayed_thread.attributes("comment")).toBeDefined();
        expect(displayed_thread.attributes("controller")).toBeDefined();
        expect(displayed_thread.attributes("relativedatehelper")).toBeDefined();
    });

    it("When an error occurs while retrieving the comments, Then it should call the display_error_callback with the fault", async () => {
        const api_fault = Fault.fromMessage("Forbidden");

        vi.spyOn(tuleap_api, "fetchPullRequestTimelineItems").mockReturnValue(errAsync(api_fault));

        getWrapper();
        await flushPromises();

        expect(display_error_callback).toHaveBeenCalledWith(api_fault);
    });

    it("should display a placeholder when there is no thread to display", async () => {
        vi.spyOn(tuleap_api, "fetchPullRequestTimelineItems").mockReturnValue(okAsync([]));

        const wrapper = getWrapper();
        await flushPromises();

        expect(wrapper.findComponent(OverviewThreadsEmptyState).exists()).toBe(true);
    });
});
