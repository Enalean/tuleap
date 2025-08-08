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
import { mount } from "@vue/test-utils";
import type { BuildStatus } from "@tuleap/plugin-pullrequest-constants";
import PullRequestCIStatus from "./PullRequestCIStatus.vue";
import { getGlobalTestOptions } from "../../../tests/helpers/global-options-for-tests";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";
import {
    PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN,
    PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP,
    PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN,
    PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP,
} from "@tuleap/tlp-relative-date";

import {
    BUILD_STATUS_FAILED,
    BUILD_STATUS_PENDING,
    BUILD_STATUS_SUCCESS,
    BUILD_STATUS_UNKNOWN,
} from "@tuleap/plugin-pullrequest-constants";
import { USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY } from "../../constants";

const getWrapper = (
    relative_date_pref: RelativeDatesDisplayPreference,
    pull_request_info: PullRequest | null,
): VueWrapper => {
    return mount(PullRequestCIStatus, {
        global: {
            ...getGlobalTestOptions(),
            stubs: {
                PullRequestRelativeDate: true,
            },
            provide: {
                [USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY.valueOf()]: relative_date_pref,
            },
        },
        props: {
            pull_request_info,
        },
    });
};

describe("PullRequestCIStatus", () => {
    it.each([
        [BUILD_STATUS_PENDING, ["tlp-badge-outline", "tlp-badge-info"], "fa-hourglass"],
        [BUILD_STATUS_SUCCESS, ["tlp-badge-outline", "tlp-badge-success"], "fa-circle-check"],
        [BUILD_STATUS_FAILED, ["tlp-badge-danger"], "fa-circle-exclamation"],
        [BUILD_STATUS_UNKNOWN, ["tlp-badge-warning"], "fa-exclamation-triangle"],
    ])(
        "When the status is %s, then it should display a %s badge containing a %s icon",
        async (last_build_status, expected_badge_classes, expected_icon_class) => {
            const wrapper = getWrapper(PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN, null);

            expect(wrapper.find("[data-test=pullrequest-property-skeleton]").exists()).toBe(true);
            expect(wrapper.find("[data-test=pullrequest-ci-status-badge]").exists()).toBe(false);

            await wrapper.setProps({
                pull_request_info: {
                    last_build_status,
                    last_build_date: "2023-02-20T10:00:00Z",
                } as PullRequest,
            });

            expect(wrapper.find("[data-test=pullrequest-property-skeleton]").exists()).toBe(false);

            const badge = wrapper.find("[data-test=pullrequest-ci-status-badge]");
            expect(badge.exists()).toBe(true);
            expect(badge.classes()).toStrictEqual(expected_badge_classes);
            expect(wrapper.find("[data-test=pullrequest-ci-badge-icon]").classes()).toContain(
                expected_icon_class,
            );
            expect(
                wrapper
                    .find("[data-test=pullrequest-ci-status-as-relative-date]")
                    .attributes("date"),
            ).toBe("2023-02-20T10:00:00Z");
        },
    );

    function* generateDateDisplayCases(): Generator<
        [RelativeDatesDisplayPreference, BuildStatus, string]
    > {
        yield [PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN, BUILD_STATUS_PENDING, `Pending since`];
        yield [PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN, BUILD_STATUS_PENDING, `Pending since`];
        yield [PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP, BUILD_STATUS_PENDING, `Pending since`];
        yield [PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP, BUILD_STATUS_PENDING, `Pending since`];

        yield [PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN, BUILD_STATUS_SUCCESS, `Success`];
        yield [PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN, BUILD_STATUS_SUCCESS, `Success on`];
        yield [PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP, BUILD_STATUS_SUCCESS, `Success`];
        yield [PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP, BUILD_STATUS_SUCCESS, `Success on`];

        yield [PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN, BUILD_STATUS_FAILED, `Failure`];
        yield [PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN, BUILD_STATUS_FAILED, `Failure on`];
        yield [PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP, BUILD_STATUS_FAILED, `Failure`];
        yield [PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP, BUILD_STATUS_FAILED, `Failure on`];

        yield [PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN, BUILD_STATUS_UNKNOWN, `Unknown`];
        yield [PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN, BUILD_STATUS_UNKNOWN, `Unknown`];
        yield [PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP, BUILD_STATUS_UNKNOWN, `Unknown`];
        yield [PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP, BUILD_STATUS_UNKNOWN, `Unknown`];
    }

    it.each([...generateDateDisplayCases()])(
        `When the date display preference is %s
        And the status is %s
        Then it should display %s`,
        (date_display_preference, ci_status, expected_badge_text) => {
            const wrapper = getWrapper(date_display_preference, {
                last_build_status: ci_status,
                last_build_date: "2023-02-20T10:00:00Z",
            } as PullRequest);
            expect(wrapper.find("[data-test=pullrequest-ci-badge-status-name]").text()).toContain(
                expected_badge_text,
            );
        },
    );
});
