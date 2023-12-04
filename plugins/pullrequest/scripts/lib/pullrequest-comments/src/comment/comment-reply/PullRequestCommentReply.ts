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

import { define, parent } from "hybrids";
import { loadTooltips } from "@tuleap/tooltip";
import { gettext_provider } from "../../gettext-provider";
import type { HelpRelativeDatesDisplay } from "../../helpers/relative-dates-helper";
import type { PullRequestCommentPresenter } from "../PullRequestCommentPresenter";
import type { PullRequestCommentComponentType } from "../PullRequestComment";
import { PullRequestCommentComponent } from "../PullRequestComment";
import type { ControlPullRequestCommentReply } from "./PullRequestCommentReplyController";
import { getCommentReplyTemplate } from "./PullRequestCommentReplyTemplate";

export const TAG = "tuleap-pullrequest-comment-reply";

type PullRequestCommentReply = {
    readonly controller: ControlPullRequestCommentReply;
    readonly is_last_reply: boolean;
};

export type InternalPullRequestCommentReply = Readonly<PullRequestCommentReply> & {
    content: () => HTMLElement;
    comment: PullRequestCommentPresenter;
    relative_date_helper: HelpRelativeDatesDisplay;
    parent_element: PullRequestCommentComponentType;
    after_render_once: unknown;
    element_height: unknown;
    is_in_edition_mode: boolean;
};

export type HostElement = InternalPullRequestCommentReply & HTMLElement;

export const after_render_once_descriptor = {
    get: (host: InternalPullRequestCommentReply): unknown => host.content(),
    observe(host: HostElement): void {
        loadTooltips(host, false);
    },
};

export const element_height_descriptor = {
    get: (host: InternalPullRequestCommentReply): number =>
        host.content().getBoundingClientRect().height,
    observe(host: InternalPullRequestCommentReply): void {
        setTimeout(() => {
            host.parent_element.post_rendering_callback?.();
        });
    },
};

export const PullRequestCommentReply = define<InternalPullRequestCommentReply>({
    tag: TAG,
    is_last_reply: false,
    is_in_edition_mode: false,
    comment: undefined,
    relative_date_helper: undefined,
    parent_element: parent(PullRequestCommentComponent),
    after_render_once: after_render_once_descriptor,
    element_height: element_height_descriptor,
    controller: {
        set: (
            host: InternalPullRequestCommentReply,
            controller: ControlPullRequestCommentReply,
        ) => {
            host.relative_date_helper = controller.getRelativeDateHelper();

            return controller;
        },
    },
    content: (host) => getCommentReplyTemplate(host, gettext_provider),
});
