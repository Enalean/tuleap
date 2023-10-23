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

import { define, html } from "hybrids";
import DOMPurify from "dompurify";
import type { ActionOnPullRequestEvent } from "@tuleap/plugin-pullrequest-rest-api-types";
import { getHeaderTemplate } from "../templates/CommentHeaderTemplate";
import { getCommentAvatarTemplate } from "../templates/CommentAvatarTemplate";
import type { HelpRelativeDatesDisplay } from "../helpers/relative-dates-helper";
import { RelativeDatesHelper } from "../helpers/relative-dates-helper";
import { gettext_provider } from "../gettext-provider";
import type { CurrentPullRequestUserPresenter } from "../types";
import { TimelineEventPresenter } from "./TimelineEventPresenter";

export const TAG = "tuleap-pullrequest-timeline-event-comment";

export type TimelineEvent = {
    readonly event: ActionOnPullRequestEvent;
    readonly current_user: CurrentPullRequestUserPresenter;
};

type InternalTimelineEvent = Readonly<TimelineEvent> & {
    presenter: TimelineEventPresenter;
    relative_date_helper: HelpRelativeDatesDisplay;
};

export type HostElement = InternalTimelineEvent & HTMLElement;

export const TimelineEvent = define<InternalTimelineEvent>({
    tag: TAG,
    event: undefined,
    current_user: undefined,
    presenter: {
        get: (host, presenter) =>
            presenter ??
            TimelineEventPresenter.fromActionOnPullRequestEvent(host.event, gettext_provider),
    },
    relative_date_helper: {
        get: (host, relative_date_display) =>
            relative_date_display ??
            RelativeDatesHelper(
                host.current_user.preferred_date_format,
                host.current_user.preferred_relative_date_display,
                host.current_user.user_locale,
            ),
    },
    content: (host) => html`
        <div class="pull-request-comment pull-request-timeline-event timeline-event">
            ${getCommentAvatarTemplate(host.presenter.user)}
            <div class="pull-request-comment-content">
                <div class="pull-request-comment-content-info">
                    ${getHeaderTemplate(
                        host.presenter.user,
                        host.relative_date_helper,
                        host.presenter.post_date,
                    )}
                </div>
                <p
                    class="pull-request-comment-text"
                    data-test="pull-request-comment-text"
                    innerHTML="${DOMPurify.sanitize(host.presenter.message)}"
                ></p>
            </div>
        </div>
    `,
});
