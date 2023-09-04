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

import type { RouteLocationNormalized, RouteLocationRaw, RouteRecordName } from "vue-router";
import {
    STEP_GITLAB_GROUP,
    STEP_GITLAB_SERVER,
    NO_GROUP_LINKED_EMPTY_STATE,
    STEP_GITLAB_CONFIGURATION,
} from "../types";

import type { GitlabGroupLinkStepName } from "../types";

const authorized_navigations = new Map<GitlabGroupLinkStepName, readonly GitlabGroupLinkStepName[]>(
    [
        [NO_GROUP_LINKED_EMPTY_STATE, [STEP_GITLAB_SERVER]],
        [STEP_GITLAB_SERVER, [NO_GROUP_LINKED_EMPTY_STATE, STEP_GITLAB_GROUP]],
        [STEP_GITLAB_GROUP, [STEP_GITLAB_SERVER, STEP_GITLAB_CONFIGURATION]],
        [STEP_GITLAB_CONFIGURATION, [STEP_GITLAB_GROUP]],
    ],
);

function isAValidStepName(
    name: RouteRecordName | null | undefined,
): name is GitlabGroupLinkStepName {
    if (!name) {
        return false;
    }

    return [
        "no-group-linked-empty-state",
        "gitlab-server",
        "gitlab-group",
        "gitlab-configuration",
    ].includes(String(name));
}

function isARedirectionToEmptyState(
    from: RouteLocationNormalized,
    to: RouteLocationNormalized,
): boolean {
    return from.name === undefined && to.name === NO_GROUP_LINKED_EMPTY_STATE;
}

export const ensureStepsHaveBeenCompletedInTheRightOrder = (
    to: RouteLocationNormalized,
    from: RouteLocationNormalized,
): RouteLocationRaw | void => {
    if (isARedirectionToEmptyState(from, to)) {
        return;
    }

    if (!isAValidStepName(to.name) || !isAValidStepName(from.name)) {
        return { name: NO_GROUP_LINKED_EMPTY_STATE };
    }

    const allowed_origins = authorized_navigations.get(to.name);
    if (!allowed_origins || !allowed_origins.includes(from.name)) {
        return { name: NO_GROUP_LINKED_EMPTY_STATE };
    }

    return;
};
