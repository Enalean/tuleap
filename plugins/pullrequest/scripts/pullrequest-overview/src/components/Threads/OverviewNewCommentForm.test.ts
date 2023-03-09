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

import { describe, it, expect } from "vitest";
import { shallowMount } from "@vue/test-utils";
import OverviewNewCommentForm from "./OverviewNewCommentForm.vue";
import { getGlobalTestOptions } from "../../tests-helpers/global-options-for-tests";
import { PULL_REQUEST_NEW_COMMENT_FORM_ELEMENT_TAG_NAME } from "@tuleap/plugin-pullrequest-comments";
import {
    CURRENT_USER_AVATAR_URL,
    CURRENT_USER_ID,
    DISPLAY_NEWLY_CREATED_GLOBAL_COMMENT,
    DISPLAY_TULEAP_API_ERROR,
    OVERVIEW_APP_BASE_URL_KEY,
    PULL_REQUEST_ID_KEY,
} from "../../constants";

const current_user_id = 102;
const current_user_avatar_url = "url/to/user_avatar.png";
const noop = (): void => {
    // do nothing
};

describe("OverviewNewCommentForm", () => {
    it("should init a <tuleap-pullrequest-new-comment-form /> component", () => {
        const wrapper = shallowMount(OverviewNewCommentForm, {
            global: {
                ...getGlobalTestOptions(),
                stubs: {
                    [PULL_REQUEST_NEW_COMMENT_FORM_ELEMENT_TAG_NAME]: true,
                },
                provide: {
                    [CURRENT_USER_ID as symbol]: current_user_id,
                    [PULL_REQUEST_ID_KEY as symbol]: "15",
                    [OVERVIEW_APP_BASE_URL_KEY as symbol]: new URL("https://example.com"),
                    [CURRENT_USER_AVATAR_URL as symbol]: current_user_avatar_url,
                    [DISPLAY_TULEAP_API_ERROR as symbol]: noop,
                    [DISPLAY_NEWLY_CREATED_GLOBAL_COMMENT as symbol]: noop,
                },
            },
        });

        const component = wrapper.find("[data-test=pull-request-new-global-comment-component]");
        expect(component.attributes("comment_saver")).toBeDefined();
        expect(component.attributes("post_submit_callback")).toBeDefined();
        expect(component.attributes("error_callback")).toBeDefined();
        expect(component.attributes("config")).toBeDefined();
        expect(component.attributes("author_presenter")).toBeDefined();
    });
});
