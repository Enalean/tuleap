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

import { dispatch } from "hybrids";
import type { CurrentPullRequestUserPresenter } from "../../types";
import type { HelpRelativeDatesDisplay } from "../../helpers/relative-dates-helper";
import { RelativeDatesHelper } from "../../helpers/relative-dates-helper";
import type { PullRequestPresenter } from "../PullRequestPresenter";
import type { HostElement, InternalPullRequestCommentReply } from "./PullRequestCommentReply";

export type ControlPullRequestCommentReply = {
    showReplyForm(host: HostElement): void;
    hideReplyForm(host: HostElement): void;
    showEditionForm(host: InternalPullRequestCommentReply): void;
    hideEditionForm(host: InternalPullRequestCommentReply): void;
    getRelativeDateHelper(): HelpRelativeDatesDisplay;
    getProjectId(): number;
    getCurrentUserId(): number;
};

export const PullRequestCommentReplyController = (
    current_user: CurrentPullRequestUserPresenter,
    current_pull_request: PullRequestPresenter,
): ControlPullRequestCommentReply => ({
    showReplyForm: (host: HostElement): void => {
        dispatch(host, "show-reply-form");
    },
    hideReplyForm: (host: HostElement): void => {
        dispatch(host, "hide-reply-form");
    },
    showEditionForm: (host: InternalPullRequestCommentReply): void => {
        host.is_in_edition_mode = true;
    },
    hideEditionForm: (host: InternalPullRequestCommentReply): void => {
        host.is_in_edition_mode = false;
    },
    getRelativeDateHelper: (): HelpRelativeDatesDisplay =>
        RelativeDatesHelper(
            current_user.preferred_date_format,
            current_user.preferred_relative_date_display,
            current_user.user_locale,
        ),
    getProjectId: () => current_pull_request.project_id,
    getCurrentUserId: (): number => current_user.user_id,
});
