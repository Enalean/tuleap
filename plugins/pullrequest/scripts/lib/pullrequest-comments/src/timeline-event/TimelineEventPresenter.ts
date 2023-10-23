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

import type { GettextProvider } from "@tuleap/gettext";
import type { ActionOnPullRequestEvent, User } from "@tuleap/plugin-pullrequest-rest-api-types";
import {
    EVENT_TYPE_ABANDON,
    EVENT_TYPE_MERGE,
    EVENT_TYPE_REBASE,
    EVENT_TYPE_REOPEN,
    EVENT_TYPE_UPDATE,
} from "@tuleap/plugin-pullrequest-constants";

export type TimelineEventPresenter = {
    user: User;
    post_date: string;
    message: string;
};

function getActionEventMessage(
    event: ActionOnPullRequestEvent,
    gettext_provider: GettextProvider,
): string {
    switch (event.event_type) {
        case EVENT_TYPE_UPDATE:
            return gettext_provider.gettext("Has updated the pull request.");
        case EVENT_TYPE_REBASE:
            return gettext_provider.gettext("Has rebased the pull request.");
        case EVENT_TYPE_MERGE:
            return gettext_provider.gettext("Has merged the pull request.");
        case EVENT_TYPE_ABANDON:
            return gettext_provider.gettext("Has abandoned the pull request.");
        case EVENT_TYPE_REOPEN:
            return gettext_provider.gettext("Has reopened the pull request.");
        default:
            return "";
    }
}

export const TimelineEventPresenter = {
    fromActionOnPullRequestEvent: (
        action: ActionOnPullRequestEvent,
        gettext_provider: GettextProvider,
    ): TimelineEventPresenter => ({
        user: action.user,
        post_date: action.post_date,
        message: getActionEventMessage(action, gettext_provider),
    }),
};
