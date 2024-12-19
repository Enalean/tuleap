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

import { describe, expect, it, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import OverviewNewCommentForm from "./OverviewNewCommentForm.vue";
import { getGlobalTestOptions } from "../../../tests/helpers/global-options-for-tests";
import { PULL_REQUEST_NEW_COMMENT_FORM_ELEMENT_TAG_NAME } from "@tuleap/plugin-pullrequest-comments";
import {
    CURRENT_USER_AVATAR_URL,
    CURRENT_USER_ID,
    DISPLAY_NEWLY_CREATED_GLOBAL_COMMENT,
    DISPLAY_TULEAP_API_ERROR,
    OVERVIEW_APP_BASE_URL_KEY,
    PROJECT_ID,
    PULL_REQUEST_ID_KEY,
} from "../../constants";

const current_user_id = 102;
const current_user_avatar_url = "url/to/user_avatar.png";
const noop = (): void => {
    // do nothing
};

vi.mock("@tuleap/mention", () => ({
    initMentions(): void {
        // Mock @tuleap/mention because it needs jquery in tests
    },
}));

describe("OverviewNewCommentForm", () => {
    it("should init a <tuleap-pullrequest-new-comment-form /> component", () => {
        const wrapper = shallowMount(OverviewNewCommentForm, {
            global: {
                ...getGlobalTestOptions(),
                stubs: {
                    [PULL_REQUEST_NEW_COMMENT_FORM_ELEMENT_TAG_NAME]: true,
                },
                provide: {
                    [CURRENT_USER_ID.valueOf()]: current_user_id,
                    [PULL_REQUEST_ID_KEY.valueOf()]: 15,
                    [OVERVIEW_APP_BASE_URL_KEY.valueOf()]: new URL("https://example.com"),
                    [CURRENT_USER_AVATAR_URL.valueOf()]: current_user_avatar_url,
                    [DISPLAY_TULEAP_API_ERROR.valueOf()]: noop,
                    [DISPLAY_NEWLY_CREATED_GLOBAL_COMMENT.valueOf()]: noop,
                    [PROJECT_ID.valueOf()]: 105,
                },
            },
        });

        const component = wrapper.find("[data-test=pull-request-new-global-comment-component]");
        expect(component.exists()).toBe(true);
    });
});
