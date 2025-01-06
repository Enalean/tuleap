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

import type { MockInstance } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { errAsync, okAsync } from "neverthrow";
import type { VueWrapper } from "@vue/test-utils";
import { flushPromises, shallowMount } from "@vue/test-utils";
import { Fault } from "@tuleap/fault";
import { PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN } from "@tuleap/tlp-relative-date";
import {
    PULL_REQUEST_COMMENT_DESCRIPTION_ELEMENT_TAG_NAME,
    PULL_REQUEST_COMMENT_ELEMENT_TAG_NAME,
    PULL_REQUEST_COMMENT_SKELETON_ELEMENT_TAG_NAME,
} from "@tuleap/plugin-pullrequest-comments";
import type { TimelineItem } from "@tuleap/plugin-pullrequest-rest-api-types";
import {
    EVENT_TYPE_MERGE,
    TYPE_EVENT_PULLREQUEST_ACTION,
    TYPE_GLOBAL_COMMENT,
} from "@tuleap/plugin-pullrequest-constants";
import { getGlobalTestOptions } from "../../../tests/helpers/global-options-for-tests";
import * as tuleap_api from "../../api/tuleap-rest-querier";
import {
    CURRENT_USER_AVATAR_URL,
    CURRENT_USER_ID,
    DISPLAY_TULEAP_API_ERROR,
    OVERVIEW_APP_BASE_URL_KEY,
    PROJECT_ID,
    PULL_REQUEST_ID_KEY,
    USER_LOCALE_KEY,
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
    USER_TIMEZONE_KEY,
} from "../../constants";
import OverviewThreads from "./OverviewThreads.vue";

vi.mock("@tuleap/mention", () => ({
    initMentions(): void {
        // Mock @tuleap/mention because it needs jquery in tests
    },
}));

async function setWrapperProps(wrapper: VueWrapper): Promise<void> {
    await wrapper.setProps({
        pull_request_info: {
            user_id: 102,
        },
        pull_request_author: {
            id: 102,
        },
    });
}

describe("OverviewThreads", () => {
    let display_error_callback: MockInstance;

    beforeEach(() => {
        display_error_callback = vi.fn();
    });

    const getWrapper = (): VueWrapper => {
        return shallowMount(OverviewThreads, {
            global: {
                ...getGlobalTestOptions(),
                stubs: {
                    [PULL_REQUEST_COMMENT_ELEMENT_TAG_NAME]: true,
                    [PULL_REQUEST_COMMENT_SKELETON_ELEMENT_TAG_NAME]: true,
                    [PULL_REQUEST_COMMENT_DESCRIPTION_ELEMENT_TAG_NAME]: true,
                },
                provide: {
                    [OVERVIEW_APP_BASE_URL_KEY.valueOf()]: new URL("https://example.com"),
                    [DISPLAY_TULEAP_API_ERROR.valueOf()]: display_error_callback,
                    [PULL_REQUEST_ID_KEY.valueOf()]: 15,
                    [CURRENT_USER_ID.valueOf()]: 102,
                    [CURRENT_USER_AVATAR_URL.valueOf()]: "/url/to/user_102_profile_page.html",
                    [USER_TIMEZONE_KEY.valueOf()]: "Europe/Paris",
                    [USER_LOCALE_KEY.valueOf()]: "fr_FR",
                    [USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY.valueOf()]:
                        PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN,
                    [PROJECT_ID.valueOf()]: 105,
                },
            },
            props: {
                pull_request_info: null,
                pull_request_author: null,
            },
        });
    };

    it(`When the pull-request and the pull-request author have loaded
        Then it should fetch the comments
        And display the description comment, the root comments as threads, and action events`, async () => {
        vi.spyOn(tuleap_api, "fetchPullRequestTimelineItems").mockReturnValue(
            okAsync([
                {
                    id: 102,
                    type: TYPE_GLOBAL_COMMENT,
                    parent_id: 0,
                    content: "What do you think?",
                    post_date: "2023-10-23T10:00:00Z",
                } as TimelineItem,
                {
                    id: 103,
                    type: TYPE_GLOBAL_COMMENT,
                    parent_id: 102,
                    content: "I'm ok with that",
                    post_date: "2023-10-23T10:01:00Z",
                } as TimelineItem,
                {
                    type: TYPE_EVENT_PULLREQUEST_ACTION,
                    event_type: EVENT_TYPE_MERGE,
                    post_date: "2023-10-23T10:03:00Z",
                } as TimelineItem,
            ]),
        );

        const wrapper = getWrapper();
        expect(wrapper.find("[data-test=pull-request-threads]").exists()).toBe(false);
        expect(wrapper.find("[data-test=pull-request-threads-skeleton]").exists()).toBe(true);
        expect(wrapper.find("[data-test=pull-request-description-comment-skeleton]").exists()).toBe(
            true,
        );

        await setWrapperProps(wrapper);
        await flushPromises();

        expect(display_error_callback).not.toHaveBeenCalled();
        expect(wrapper.find("[data-test=pull-request-threads-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=pull-request-description-comment-skeleton]").exists()).toBe(
            false,
        );

        const threads = wrapper.find("[data-test=pull-request-threads]");
        expect(threads.exists()).toBe(true);
        expect(threads.element.childElementCount).toBe(3);

        const displayed_thread = wrapper.find("[data-test=pull-request-thread]");
        expect(displayed_thread.exists()).toBe(true);

        const displayed_description = wrapper.find("[data-test=pull-request-overview-description]");
        expect(displayed_description.exists()).toBe(true);

        const displayed_event = wrapper.find("[data-test=pull-request-overview-action-event]");
        expect(displayed_event.exists()).toBe(true);
    });

    it("When an error occurs while retrieving the comments, Then it should call the display_error_callback with the fault", async () => {
        const api_fault = Fault.fromMessage("Forbidden");

        vi.spyOn(tuleap_api, "fetchPullRequestTimelineItems").mockReturnValue(errAsync(api_fault));

        await setWrapperProps(getWrapper());
        await flushPromises();

        expect(display_error_callback).toHaveBeenCalledWith(api_fault);
    });
});
