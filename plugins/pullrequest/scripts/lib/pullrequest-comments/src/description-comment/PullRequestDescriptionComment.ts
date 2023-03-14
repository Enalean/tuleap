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
import { loadTooltips } from "@tuleap/tooltip";
import type { PullRequestDescriptionCommentPresenter } from "./PullRequestDescriptionCommentPresenter";
import type { CurrentPullRequestUserPresenter } from "../types";
import type { HelpRelativeDatesDisplay } from "../helpers/relative-dates-helper";
import { RelativeDatesHelper } from "../helpers/relative-dates-helper";
import { getCommentAvatarTemplate } from "../templates/CommentAvatarTemplate";
import { getHeaderTemplate } from "../templates/CommentHeaderTemplate";
import { gettext_provider } from "../gettext-provider";
import { getDescriptionContentTemplate } from "./PullRequestDescriptionContentTemplate";

export const PULL_REQUEST_COMMENT_DESCRIPTION_ELEMENT_TAG_NAME =
    "tuleap-pullrequest-description-comment";
export type HostElement = PullRequestDescriptionComment & HTMLElement;

export interface PullRequestDescriptionComment {
    readonly content: () => HTMLElement;
    readonly description: PullRequestDescriptionCommentPresenter;
    readonly current_user: CurrentPullRequestUserPresenter;
    readonly after_render_once: unknown;
    relative_date_helper: HelpRelativeDatesDisplay;
}

export const after_render_once_descriptor = {
    get: (host: PullRequestDescriptionComment): unknown => host.content(),
    observe(host: HostElement): void {
        loadTooltips(host, false);
    },
};

export const PullRequestCommentDescriptionComponent = define<PullRequestDescriptionComment>({
    tag: PULL_REQUEST_COMMENT_DESCRIPTION_ELEMENT_TAG_NAME,
    description: undefined,
    current_user: undefined,
    after_render_once: after_render_once_descriptor,
    relative_date_helper: {
        get: (host) => {
            return RelativeDatesHelper(
                host.current_user.preferred_date_format,
                host.current_user.preferred_relative_date_display,
                host.current_user.user_locale
            );
        },
    },
    content: (host) => html`
        <div class="pull-request-comment pull-request-description-comment">
            ${getCommentAvatarTemplate(host.description.author)}

            <div class="pull-request-comment-content">
                <div class="pull-request-comment-content-info">
                    ${getHeaderTemplate(
                        host.description.author,
                        host.relative_date_helper,
                        host.description.post_date
                    )}
                </div>

                ${getDescriptionContentTemplate(host, gettext_provider)}
            </div>
        </div>
    `,
});
