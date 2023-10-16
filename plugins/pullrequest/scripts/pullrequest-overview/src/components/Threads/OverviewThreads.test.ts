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
    PULL_REQUEST_COMMENT_DESCRIPTION_ELEMENT_TAG_NAME,
} from "@tuleap/plugin-pullrequest-comments";
import * as tuleap_api from "../../api/tuleap-rest-querier";
import { getGlobalTestOptions } from "../../../tests/helpers/global-options-for-tests";
import {
    CURRENT_USER_AVATAR_URL,
    CURRENT_USER_ID,
    DISPLAY_TULEAP_API_ERROR,
    IS_COMMENT_EDITION_ENABLED,
    OVERVIEW_APP_BASE_URL_KEY,
    PROJECT_ID,
    PULL_REQUEST_ID_KEY,
    USER_DATE_TIME_FORMAT_KEY,
    USER_LOCALE_KEY,
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
} from "../../constants";
import OverviewThreads from "./OverviewThreads.vue";
import type { TimelineItem } from "@tuleap/plugin-pullrequest-rest-api-types";
import * as strict_inject from "@tuleap/vue-strict-inject";

vi.mock("@tuleap/vue-strict-inject");

async function setWrapperProps(wrapper: VueWrapper): Promise<void> {
    wrapper.setProps({
        pull_request_info: {
            user_id: 102,
        },
        pull_request_author: {
            id: 102,
        },
    });

    await wrapper.vm.$nextTick();
}

const is_comment_edition_enabled = true;

describe("OverviewThreads", () => {
    let display_error_callback: SpyInstance;

    beforeEach(() => {
        display_error_callback = vi.fn();
    });

    const getWrapper = (): VueWrapper => {
        vi.spyOn(strict_inject, "strictInject").mockImplementation((key) => {
            switch (key) {
                case OVERVIEW_APP_BASE_URL_KEY:
                    return new URL("https://example.com");
                case DISPLAY_TULEAP_API_ERROR:
                    return display_error_callback;
                case PULL_REQUEST_ID_KEY:
                    return 15;
                case CURRENT_USER_ID:
                    return 102;
                case CURRENT_USER_AVATAR_URL:
                    return "/url/to/user_102_profile_page.html";
                case USER_DATE_TIME_FORMAT_KEY:
                    return "d/m/Y H:i";
                case USER_LOCALE_KEY:
                    return "fr_FR";
                case USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY:
                    return PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN;
                case PROJECT_ID:
                    return 105;
                case IS_COMMENT_EDITION_ENABLED:
                    return is_comment_edition_enabled;
                default:
                    throw new Error("Tried to strictInject a value while it was not mocked");
            }
        });
        return shallowMount(OverviewThreads, {
            global: {
                ...getGlobalTestOptions(),
                stubs: {
                    [PULL_REQUEST_COMMENT_ELEMENT_TAG_NAME]: true,
                    [PULL_REQUEST_COMMENT_SKELETON_ELEMENT_TAG_NAME]: true,
                    [PULL_REQUEST_COMMENT_DESCRIPTION_ELEMENT_TAG_NAME]: true,
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
        And display the description comment + the root comments as threads`, async () => {
        vi.spyOn(tuleap_api, "fetchPullRequestTimelineItems").mockReturnValue(
            okAsync([
                { id: 102, parent_id: 0, content: "What do you think?" } as TimelineItem,
                { id: 103, parent_id: 102, content: "I'm ok with that" } as TimelineItem,
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
        expect(threads.element.childElementCount).toBe(2);

        const displayed_thread = wrapper.find("[data-test=pull-request-thread]");
        expect(displayed_thread.exists()).toBe(true);

        const displayed_description = wrapper.find("[data-test=pull-request-overview-description]");
        expect(displayed_description.exists()).toBe(true);
    });

    it("When an error occurs while retrieving the comments, Then it should call the display_error_callback with the fault", async () => {
        const api_fault = Fault.fromMessage("Forbidden");

        vi.spyOn(tuleap_api, "fetchPullRequestTimelineItems").mockReturnValue(errAsync(api_fault));

        await setWrapperProps(getWrapper());
        await flushPromises();

        expect(display_error_callback).toHaveBeenCalledWith(api_fault);
    });
});
