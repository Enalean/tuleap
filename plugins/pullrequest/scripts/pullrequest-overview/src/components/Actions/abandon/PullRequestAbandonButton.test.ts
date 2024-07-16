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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import {
    PULL_REQUEST_STATUS_ABANDON,
    PULL_REQUEST_STATUS_MERGED,
    PULL_REQUEST_STATUS_REVIEW,
} from "@tuleap/plugin-pullrequest-constants";
import PullRequestAbandonButton from "./PullRequestAbandonButton.vue";
import { getGlobalTestOptions } from "../../../../tests/helpers/global-options-for-tests";
import * as tuleap_api from "../../../api/tuleap-rest-querier";
import type { DisplayErrorCallback, PostPullRequestUpdateCallback } from "../../../constants";
import {
    DISPLAY_TULEAP_API_ERROR,
    POST_PULL_REQUEST_UPDATE_CALLBACK,
    PULL_REQUEST_ID_KEY,
} from "../../../constants";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";

vi.mock("./tuleap-rest-querier");

describe("PullRequestAbandonButton", () => {
    let current_pull_request_id: number,
        on_error_callback: DisplayErrorCallback,
        post_update_callback: PostPullRequestUpdateCallback;

    beforeEach(() => {
        current_pull_request_id = 15;
        on_error_callback = vi.fn();
        post_update_callback = vi.fn();
    });

    const getWrapper = (data: Partial<PullRequest> = {}): VueWrapper => {
        return shallowMount(PullRequestAbandonButton, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [DISPLAY_TULEAP_API_ERROR.valueOf()]: on_error_callback,
                    [POST_PULL_REQUEST_UPDATE_CALLBACK.valueOf()]: post_update_callback,
                    [PULL_REQUEST_ID_KEY.valueOf()]: current_pull_request_id,
                },
            },
            props: {
                pull_request: {
                    id: current_pull_request_id,
                    user_can_abandon: true,
                    status: PULL_REQUEST_STATUS_REVIEW,
                    ...data,
                } as PullRequest,
            },
        });
    };

    it.each([[PULL_REQUEST_STATUS_ABANDON], [PULL_REQUEST_STATUS_MERGED]])(
        "should not be displayed when the pull-request status is %s",
        (status) => {
            const wrapper = getWrapper({ status });
            expect(wrapper.element.children).toBeUndefined();
        },
    );

    it("should not be displayed when the pull-request status is review but the user cannot abandon", () => {
        const wrapper = getWrapper({ status: PULL_REQUEST_STATUS_REVIEW, user_can_abandon: false });
        expect(wrapper.element.children).toBeUndefined();
    });

    it("should be displayed when the pull-request status is review and the user can abandon", () => {
        const wrapper = getWrapper({ status: PULL_REQUEST_STATUS_REVIEW, user_can_abandon: true });
        expect(wrapper.find("[data-test=abandon-button]").exists()).toBe(true);
    });

    it("should be outlined when the git reference is not broken", () => {
        const wrapper = getWrapper({
            status: PULL_REQUEST_STATUS_REVIEW,
            user_can_abandon: true,
            is_git_reference_broken: false,
        });

        expect(wrapper.find("[data-test=abandon-button]").classes()).toContain(
            "tlp-button-outline",
        );
    });

    it("should not be outlined when the git reference is broken", () => {
        const wrapper = getWrapper({
            status: PULL_REQUEST_STATUS_REVIEW,
            user_can_abandon: true,
            is_git_reference_broken: true,
        });

        expect(wrapper.find("[data-test=abandon-button]").classes()).not.toContain(
            "tlp-button-outline",
        );
    });

    describe("abandon", () => {
        it("When the user clicks the button, Then it should abandon the pull-request", async () => {
            const updated_pull_request = {
                status: PULL_REQUEST_STATUS_ABANDON,
            } as PullRequest;

            vi.spyOn(tuleap_api, "abandonPullRequest").mockReturnValue(
                okAsync(updated_pull_request),
            );

            await getWrapper().find("[data-test=abandon-button]").trigger("click");

            expect(tuleap_api.abandonPullRequest).toHaveBeenCalledOnce();
            expect(tuleap_api.abandonPullRequest).toHaveBeenCalledWith(current_pull_request_id);

            expect(post_update_callback).toHaveBeenCalledOnce();
            expect(post_update_callback).toHaveBeenCalledWith(updated_pull_request);
        });

        it("When an error occurres, Then it should call the on_error_callback", async () => {
            const tuleap_api_error = Fault.fromMessage("Forbidden");

            vi.spyOn(tuleap_api, "abandonPullRequest").mockReturnValue(errAsync(tuleap_api_error));

            await getWrapper().find("[data-test=abandon-button]").trigger("click");

            expect(on_error_callback).toHaveBeenCalledOnce();
            expect(on_error_callback).toHaveBeenCalledWith(tuleap_api_error);
        });
    });
});
