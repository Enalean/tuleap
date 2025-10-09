/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
import { createGettext } from "vue3-gettext";
import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";
import {
    PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN,
    PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP,
    PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN,
    PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP,
} from "@tuleap/tlp-relative-date";
import type { CommitStatus } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { CommitBuildStatus } from "@tuleap/plugin-pullrequest-constants";
import {
    COMMIT_BUILD_STATUS_FAILURE,
    COMMIT_BUILD_STATUS_PENDING,
    COMMIT_BUILD_STATUS_SUCCESS,
} from "@tuleap/plugin-pullrequest-constants";
import { USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY } from "../../constants";
import CommitStatusBadge from "./CommitStatusBadge.vue";

const getWrapper = (
    relative_date_pref: RelativeDatesDisplayPreference,
    commit_status: CommitStatus,
): VueWrapper => {
    return mount(CommitStatusBadge, {
        global: {
            plugins: [createGettext({ silent: true })],
            stubs: {
                CommitRelativeDate: true,
            },
            provide: {
                [USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY.valueOf()]: relative_date_pref,
            },
        },
        props: {
            commit_status,
        },
    });
};

describe("CommitStatusBadge", () => {
    it.each([
        [COMMIT_BUILD_STATUS_PENDING, ["tlp-badge-outline", "tlp-badge-info"], "fa-hourglass"],
        [
            COMMIT_BUILD_STATUS_SUCCESS,
            ["tlp-badge-outline", "tlp-badge-success"],
            "fa-circle-check",
        ],
        [COMMIT_BUILD_STATUS_FAILURE, ["tlp-badge-danger"], "fa-circle-exclamation"],
    ])(
        "When the status is %s, then it should display a %s badge containing a %s icon",
        (last_build_status, expected_badge_classes, expected_icon_class) => {
            const wrapper = getWrapper(PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN, {
                name: last_build_status,
                date: "2025-10-10T10:00:00Z",
            });

            expect(wrapper.find("[data-test=ci-status-badge]").classes()).toStrictEqual(
                expected_badge_classes,
            );
            expect(wrapper.find("[data-test=ci-badge-icon]").classes()).toContain(
                expected_icon_class,
            );
            expect(wrapper.find("[data-test=ci-relative-date]").attributes("date")).toBe(
                "2025-10-10T10:00:00Z",
            );
        },
    );

    function* generateDateDisplayCases(): Generator<
        [RelativeDatesDisplayPreference, CommitBuildStatus, string]
    > {
        yield [
            PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN,
            COMMIT_BUILD_STATUS_PENDING,
            `Pending since`,
        ];
        yield [
            PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN,
            COMMIT_BUILD_STATUS_PENDING,
            `Pending since`,
        ];
        yield [
            PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP,
            COMMIT_BUILD_STATUS_PENDING,
            `Pending since`,
        ];
        yield [
            PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP,
            COMMIT_BUILD_STATUS_PENDING,
            `Pending since`,
        ];

        yield [PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN, COMMIT_BUILD_STATUS_SUCCESS, `Success`];
        yield [PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN, COMMIT_BUILD_STATUS_SUCCESS, `Success on`];
        yield [PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP, COMMIT_BUILD_STATUS_SUCCESS, `Success`];
        yield [
            PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP,
            COMMIT_BUILD_STATUS_SUCCESS,
            `Success on`,
        ];

        yield [PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN, COMMIT_BUILD_STATUS_FAILURE, `Failure`];
        yield [PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN, COMMIT_BUILD_STATUS_FAILURE, `Failure on`];
        yield [PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP, COMMIT_BUILD_STATUS_FAILURE, `Failure`];
        yield [
            PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP,
            COMMIT_BUILD_STATUS_FAILURE,
            `Failure on`,
        ];
    }

    it.each([...generateDateDisplayCases()])(
        `When the date display preference is %s
        And the status is %s
        Then it should display %s`,
        (date_display_preference, ci_status, expected_badge_text) => {
            const wrapper = getWrapper(date_display_preference, {
                name: ci_status,
                date: "2023-02-20T10:00:00Z",
            });
            expect(wrapper.find("[data-test=ci-badge-status-name]").text()).toContain(
                expected_badge_text,
            );
        },
    );
});
