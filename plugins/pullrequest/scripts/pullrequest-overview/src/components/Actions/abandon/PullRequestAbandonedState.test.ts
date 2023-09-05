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
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { okAsync, errAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import * as strict_inject from "@tuleap/vue-strict-inject";
import * as tuleap_api from "../../../api/tuleap-rest-querier";
import {
    PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN,
    PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN,
    PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP,
    PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP,
} from "@tuleap/tlp-relative-date";
import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import PullRequestAbandonedState from "./PullRequestAbandonedState.vue";
import { getGlobalTestOptions } from "../../../../tests/helpers/global-options-for-tests";
import {
    PULL_REQUEST_STATUS_ABANDON,
    PULL_REQUEST_STATUS_MERGED,
    PULL_REQUEST_STATUS_REVIEW,
} from "@tuleap/plugin-pullrequest-constants";
import {
    DISPLAY_TULEAP_API_ERROR,
    POST_PULL_REQUEST_UPDATE_CALLBACK,
    PULL_REQUEST_ID_KEY,
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
} from "../../../constants";

import type { DisplayErrorCallback, PostPullRequestUpdateCallback } from "../../../constants";

vi.mock("@tuleap/vue-strict-inject");
vi.mock("./tuleap-rest-querier");

describe("PullRequestAbandonedState", () => {
    let current_pull_request_id: number,
        on_error_callback: DisplayErrorCallback,
        post_update_callback: PostPullRequestUpdateCallback;

    beforeEach(() => {
        current_pull_request_id = 15;
        on_error_callback = vi.fn();
        post_update_callback = vi.fn();
    });

    const getWrapper = (
        pull_request: PullRequest,
        relative_date_preference: RelativeDatesDisplayPreference = PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN,
    ): VueWrapper => {
        vi.spyOn(strict_inject, "strictInject").mockImplementation((key): unknown => {
            switch (key) {
                case DISPLAY_TULEAP_API_ERROR:
                    return on_error_callback;
                case POST_PULL_REQUEST_UPDATE_CALLBACK:
                    return post_update_callback;
                case PULL_REQUEST_ID_KEY:
                    return current_pull_request_id;
                case USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY:
                    return relative_date_preference;
                default:
                    throw new Error("Tried to strictInject a value while it was not mocked");
            }
        });

        return shallowMount(PullRequestAbandonedState, {
            global: {
                stubs: {
                    PullRequestRelativeDate: true,
                },
                ...getGlobalTestOptions(),
            },
            props: {
                pull_request,
            },
        });
    };

    it.each([[PULL_REQUEST_STATUS_MERGED], [PULL_REQUEST_STATUS_ABANDON]])(
        "Should not display itself when the pull-request status is %s",
        (status) => {
            const wrapper = getWrapper({ status } as PullRequest);

            expect(wrapper.element.children).toBeUndefined();
        },
    );

    it("Should display the pull-request abandon date and the user who abandoned it", () => {
        const status_info = {
            status_type: PULL_REQUEST_STATUS_ABANDON,
            status_date: "2023-03-27T10:45:00Z",
            status_updater: {
                avatar_url: "url/to/user_avatar.png",
                display_name: "Joe l'Asticot",
            },
        };

        const wrapper = getWrapper({
            status: PULL_REQUEST_STATUS_ABANDON,
            user_can_reopen: false,
            status_info,
        } as PullRequest);

        expect(wrapper.find("[data-test=status-updater-avatar]").attributes("src")).toStrictEqual(
            status_info.status_updater.avatar_url,
        );
        expect(wrapper.find("[data-test=status-updater-name]").text()).toStrictEqual(
            status_info.status_updater.display_name,
        );
        expect(
            wrapper.find("[data-test=pull-request-abandon-date]").attributes("date"),
        ).toStrictEqual(status_info.status_date);
        expect(wrapper.find("[data-test=pull-request-reopen-button]").exists()).toBe(false);
    });

    it.each([
        [PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN as RelativeDatesDisplayPreference, "Abandoned"],
        [PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP as RelativeDatesDisplayPreference, "Abandoned"],
        [
            PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN as RelativeDatesDisplayPreference,
            "Abandoned on",
        ],
        [
            PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP as RelativeDatesDisplayPreference,
            "Abandoned on",
        ],
    ])(
        "When the relative date preference is %s, Then it should be prefixed by %s",
        (preference, prefix) => {
            const wrapper = getWrapper(
                {
                    status: PULL_REQUEST_STATUS_ABANDON,
                    status_info: {
                        status_type: PULL_REQUEST_STATUS_ABANDON,
                        status_date: "2023-03-27T10:45:00Z",
                        status_updater: {
                            avatar_url: "url/to/user_avatar.png",
                            display_name: "Joe l'Asticot",
                        },
                    },
                } as PullRequest,
                preference,
            );

            expect(wrapper.find("[data-test=status-abandon-date]").text()).toContain(prefix);
        },
    );

    describe("reopen", () => {
        it("When the user can reopen, then it should also display a reopen button which reopens the pull-request when clicked", async () => {
            const wrapper = getWrapper({
                status: PULL_REQUEST_STATUS_ABANDON,
                user_can_reopen: true,
                status_info: {
                    status_type: PULL_REQUEST_STATUS_ABANDON,
                    status_date: "2023-03-27T10:45:00Z",
                    status_updater: {
                        avatar_url: "url/to/user_avatar.png",
                        display_name: "Joe l'Asticot",
                    },
                },
            } as PullRequest);

            const updated_pull_request = {
                status: PULL_REQUEST_STATUS_REVIEW,
            } as PullRequest;

            vi.spyOn(tuleap_api, "reopenPullRequest").mockReturnValue(
                okAsync(updated_pull_request),
            );

            const reopen_button = wrapper.find("[data-test=pull-request-reopen-button]");
            expect(reopen_button.exists()).toBe(true);

            await reopen_button.trigger("click");

            expect(tuleap_api.reopenPullRequest).toHaveBeenCalledOnce();
            expect(tuleap_api.reopenPullRequest).toHaveBeenCalledWith(current_pull_request_id);

            expect(post_update_callback).toHaveBeenCalledOnce();
            expect(post_update_callback).toHaveBeenCalledWith(updated_pull_request);
        });

        it("When an error occurres, then it should call the on_error_callback", async () => {
            const tuleap_api_error = Fault.fromMessage("Forbidden");

            vi.spyOn(tuleap_api, "reopenPullRequest").mockReturnValue(errAsync(tuleap_api_error));
            const wrapper = getWrapper({
                status: PULL_REQUEST_STATUS_ABANDON,
                user_can_reopen: true,
                status_info: {
                    status_type: PULL_REQUEST_STATUS_ABANDON,
                    status_date: "2023-03-27T10:45:00Z",
                    status_updater: {
                        avatar_url: "url/to/user_avatar.png",
                        display_name: "Joe l'Asticot",
                    },
                },
            } as PullRequest);

            await wrapper.find("[data-test=pull-request-reopen-button]").trigger("click");

            expect(on_error_callback).toHaveBeenCalledOnce();
            expect(on_error_callback).toHaveBeenCalledWith(tuleap_api_error);
        });
    });
});
