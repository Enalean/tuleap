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

import type { ActionOnPullRequestEvent } from "@tuleap/plugin-pullrequest-rest-api-types";
import {
    TYPE_EVENT_PULLREQUEST_ACTION,
    EVENT_TYPE_UPDATE,
    EVENT_TYPE_ABANDON,
    EVENT_TYPE_MERGE,
    EVENT_TYPE_REBASE,
    EVENT_TYPE_REOPEN,
} from "@tuleap/plugin-pullrequest-constants";

const base_action = {
    type: TYPE_EVENT_PULLREQUEST_ACTION,
    event_type: EVENT_TYPE_UPDATE,
    user: {
        id: 5,
        user_url: "url/to/user_page.html",
        avatar_url: "url/to/user_avatar.png",
        display_name: "Johann Zarco (JZ5)",
    },
    post_date: "2023-10-21T06:50:00+01",
};

export const ActionEventStub = {
    buildActionUpdate: (): ActionOnPullRequestEvent => ({
        ...base_action,
        event_type: EVENT_TYPE_UPDATE,
    }),
    buildActionRebase: (): ActionOnPullRequestEvent => ({
        ...base_action,
        event_type: EVENT_TYPE_REBASE,
    }),
    buildActionMerge: (): ActionOnPullRequestEvent => ({
        ...base_action,
        event_type: EVENT_TYPE_MERGE,
    }),
    buildActionAbandon: (): ActionOnPullRequestEvent => ({
        ...base_action,
        event_type: EVENT_TYPE_ABANDON,
    }),
    buildActionReopen: (): ActionOnPullRequestEvent => ({
        ...base_action,
        event_type: EVENT_TYPE_REOPEN,
    }),
};
