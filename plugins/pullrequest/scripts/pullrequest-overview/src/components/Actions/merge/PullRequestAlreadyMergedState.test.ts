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

import { describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";
import {
    PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN,
    PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP,
    PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN,
    PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP,
} from "@tuleap/tlp-relative-date";
import {
    PULL_REQUEST_STATUS_ABANDON,
    PULL_REQUEST_STATUS_MERGED,
    PULL_REQUEST_STATUS_REVIEW,
} from "@tuleap/plugin-pullrequest-constants";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import { getGlobalTestOptions } from "../../../../tests/helpers/global-options-for-tests";
import PullRequestAlreadyMergedState from "./PullRequestAlreadyMergedState.vue";
import { USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY } from "../../../constants";

const getWrapper = (
    pull_request: PullRequest,
    relative_date_preference: RelativeDatesDisplayPreference = PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN,
): VueWrapper => {
    return shallowMount(PullRequestAlreadyMergedState, {
        global: {
            ...getGlobalTestOptions(),
            stubs: {
                PullRequestRelativeDate: true,
            },
            provide: {
                [USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY.valueOf()]: relative_date_preference,
            },
        },
        props: {
            pull_request,
        },
    });
};

describe("PullRequestAlreadyMergedState", () => {
    it.each([[PULL_REQUEST_STATUS_REVIEW], [PULL_REQUEST_STATUS_ABANDON]])(
        "Should not display itself when the pull-request status is %s",
        (status) => {
            const wrapper = getWrapper({ status } as PullRequest);

            expect(wrapper.element.children).toBeUndefined();
        },
    );

    it("Should display the pull-request merge date and the user who merged it", () => {
        const status_info = {
            status_type: PULL_REQUEST_STATUS_MERGED,
            status_date: "2023-03-27T10:45:00Z",
            status_updater: {
                avatar_url: "url/to/user_avatar.png",
                display_name: "Joe l'Asticot",
            },
        };

        const wrapper = getWrapper({
            status: PULL_REQUEST_STATUS_MERGED,
            status_info,
        } as PullRequest);

        expect(wrapper.find("[data-test=status-updater-avatar]").attributes("src")).toStrictEqual(
            status_info.status_updater.avatar_url,
        );
        expect(wrapper.find("[data-test=status-updater-name]").text()).toStrictEqual(
            status_info.status_updater.display_name,
        );
        expect(
            wrapper.find("[data-test=pull-request-merge-date]").attributes("date"),
        ).toStrictEqual(status_info.status_date);
    });

    function* generateDateDisplayCases(): Generator<[RelativeDatesDisplayPreference, string]> {
        yield [PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN, "Merged"];
        yield [PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP, "Merged"];
        yield [PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN, "Merged on"];
        yield [PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP, "Merged on"];
    }

    it.each([...generateDateDisplayCases()])(
        "When the relative date preference is %s, Then it should be prefixed by %s",
        (preference, prefix) => {
            const wrapper = getWrapper(
                {
                    status: PULL_REQUEST_STATUS_MERGED,
                    status_info: {
                        status_type: PULL_REQUEST_STATUS_MERGED,
                        status_date: "2023-03-27T10:45:00Z",
                        status_updater: {
                            avatar_url: "url/to/user_avatar.png",
                            display_name: "Joe l'Asticot",
                        },
                    },
                } as PullRequest,
                preference,
            );

            expect(wrapper.find("[data-test=status-merged-date]").text()).toContain(prefix);
        },
    );
});
