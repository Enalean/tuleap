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
import type { DescriptionCommentFormPresenter } from "./PullRequestDescriptionCommentFormPresenter";
import type { ControlPullRequestDescriptionComment } from "./PullRequestDescriptionCommentController";
import type { PullRequestDescriptionCommentPresenter } from "./PullRequestDescriptionCommentPresenter";
import { getCommentAvatarTemplate } from "../templates/CommentAvatarTemplate";
import { getDescriptionContentTemplate } from "./PullRequestDescriptionContentTemplate";
import { getDescriptionCommentFormTemplate } from "./PullRequestDescriptionCommentFormTemplate";
import { gettext_provider } from "../gettext-provider";

export const PULL_REQUEST_COMMENT_DESCRIPTION_ELEMENT_TAG_NAME =
    "tuleap-pullrequest-description-comment";
export type HostElement = PullRequestDescriptionComment & HTMLElement;

export interface PullRequestDescriptionComment {
    readonly content: () => HTMLElement;
    readonly after_render_once: unknown;
    readonly controller: ControlPullRequestDescriptionComment;
    readonly description: PullRequestDescriptionCommentPresenter;
    edition_form_presenter: DescriptionCommentFormPresenter | null;
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
    controller: undefined,
    after_render_once: after_render_once_descriptor,
    edition_form_presenter: {
        set: (host, presenter: DescriptionCommentFormPresenter | undefined) => presenter ?? null,
    },
    content: (host) => html`
        <div class="pull-request-comment pull-request-description-comment">
            ${getCommentAvatarTemplate(host.description.author)}
            ${getDescriptionContentTemplate(host, gettext_provider)}
            ${getDescriptionCommentFormTemplate(host, gettext_provider)}
        </div>
    `,
});
