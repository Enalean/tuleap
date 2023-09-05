/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
import type { RouteLocationNormalized } from "vue-router";
import { ensureStepsHaveBeenCompletedInTheRightOrder } from "./steps-order-guard";
import {
    NO_GROUP_LINKED_EMPTY_STATE,
    STEP_GITLAB_CONFIGURATION,
    STEP_GITLAB_GROUP,
    STEP_GITLAB_SERVER,
} from "../types";

describe("step-order-guard", () => {
    it.each([
        [
            "from nowhere to the GitLab server pane",
            { name: undefined } as RouteLocationNormalized,
            { name: STEP_GITLAB_SERVER } as RouteLocationNormalized,
        ],
        [
            "from the GitLab configuration pane to the GitLab server pane",
            { name: STEP_GITLAB_CONFIGURATION } as RouteLocationNormalized,
            { name: STEP_GITLAB_SERVER } as RouteLocationNormalized,
        ],
        [
            "from nowhere to the GitLab group pane",
            { name: undefined } as RouteLocationNormalized,
            { name: STEP_GITLAB_GROUP } as RouteLocationNormalized,
        ],
        [
            "from the empty state to the GitLab group pane",
            { name: NO_GROUP_LINKED_EMPTY_STATE } as RouteLocationNormalized,
            { name: STEP_GITLAB_GROUP } as RouteLocationNormalized,
        ],
        [
            "from nowhere to the GitLab configuration pane",
            { name: undefined } as RouteLocationNormalized,
            { name: STEP_GITLAB_CONFIGURATION } as RouteLocationNormalized,
        ],
        [
            "from the empty state to the GitLab configuration pane",
            { name: NO_GROUP_LINKED_EMPTY_STATE } as RouteLocationNormalized,
            { name: STEP_GITLAB_CONFIGURATION } as RouteLocationNormalized,
        ],
        [
            "from the GitLab server pane to the GitLab configuration pane",
            { name: STEP_GITLAB_SERVER } as RouteLocationNormalized,
            { name: STEP_GITLAB_CONFIGURATION } as RouteLocationNormalized,
        ],
        [
            "from an unknown route to another unknown route",
            { name: "an-unknown-route" } as RouteLocationNormalized,
            { name: "another-one" } as RouteLocationNormalized,
        ],
    ])(
        `should redirect to the empty state when the navigation occurs %s`,
        (
            navigation_description: string,
            from: RouteLocationNormalized,
            to: RouteLocationNormalized,
        ) => {
            const redirect = ensureStepsHaveBeenCompletedInTheRightOrder(to, from);

            expect(redirect).toStrictEqual({
                name: NO_GROUP_LINKED_EMPTY_STATE,
            });
        },
    );

    it.each([
        [
            "from the empty state to the GitLab server pane",
            { name: NO_GROUP_LINKED_EMPTY_STATE } as RouteLocationNormalized,
            { name: STEP_GITLAB_SERVER } as RouteLocationNormalized,
        ],
        [
            "from the GitLab server pane to the empty state (Cancel)",
            { name: NO_GROUP_LINKED_EMPTY_STATE } as RouteLocationNormalized,
            { name: STEP_GITLAB_SERVER } as RouteLocationNormalized,
        ],
        [
            "from the GitLab server pane to the Gitlab group pane",
            { name: STEP_GITLAB_SERVER } as RouteLocationNormalized,
            { name: STEP_GITLAB_GROUP } as RouteLocationNormalized,
        ],
        [
            "from the Gitlab group pane to the GitLab server pane (going back)",
            { name: STEP_GITLAB_GROUP } as RouteLocationNormalized,
            { name: STEP_GITLAB_SERVER } as RouteLocationNormalized,
        ],
        [
            "from the Gitlab group pane to the Gitlab configuration pane",
            { name: STEP_GITLAB_GROUP } as RouteLocationNormalized,
            { name: STEP_GITLAB_CONFIGURATION } as RouteLocationNormalized,
        ],
        [
            "from the Gitlab configuration pane to the Gitlab group pane (going back)",
            { name: STEP_GITLAB_CONFIGURATION } as RouteLocationNormalized,
            { name: STEP_GITLAB_GROUP } as RouteLocationNormalized,
        ],
        [
            "already a redirection to the empty state",
            { name: undefined } as RouteLocationNormalized,
            { name: NO_GROUP_LINKED_EMPTY_STATE } as RouteLocationNormalized,
        ],
    ])(
        "should let the navigation occur when it is %s",
        (
            navigation_description: string,
            from: RouteLocationNormalized,
            to: RouteLocationNormalized,
        ) => {
            const redirect = ensureStepsHaveBeenCompletedInTheRightOrder(to, from);

            expect(redirect).toBeUndefined();
        },
    );
});
