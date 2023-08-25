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
import { WritingZoneController } from "../writing-zone/WritingZoneController";
import type { ControlWritingZone } from "../writing-zone/WritingZoneController";
import type { InternalWritingZone } from "../writing-zone/WritingZone";
import { getWritingZoneElement } from "../writing-zone/WritingZone";
import type { ElementContainingAWritingZone } from "../types";

export const PULL_REQUEST_COMMENT_DESCRIPTION_ELEMENT_TAG_NAME =
    "tuleap-pullrequest-description-comment";
export type HostElement = PullRequestDescriptionComment &
    ElementContainingAWritingZone<PullRequestDescriptionComment> &
    HTMLElement;

export type PullRequestDescriptionComment = {
    readonly content: () => HTMLElement;
    readonly after_render_once: unknown;
    readonly controller: ControlPullRequestDescriptionComment;
    readonly post_description_form_close_callback: () => void;
    readonly writing_zone_controller: ControlWritingZone;
    readonly writing_zone: HTMLElement & InternalWritingZone;
    readonly is_comments_markdown_mode_enabled: boolean;
    description: PullRequestDescriptionCommentPresenter;
    edition_form_presenter: DescriptionCommentFormPresenter | null;
};

export const after_render_once_descriptor = {
    get: (host: PullRequestDescriptionComment): unknown => host.content(),
    observe(host: HostElement): void {
        loadTooltips(host, false);
    },
};

export const post_description_form_close_callback_descriptor = {
    get: (host: HostElement) => (): void => {
        setTimeout(() => {
            loadTooltips(host, false);
        });
    },
};

export const PullRequestCommentDescriptionComponent = define<PullRequestDescriptionComment>({
    tag: PULL_REQUEST_COMMENT_DESCRIPTION_ELEMENT_TAG_NAME,
    is_comments_markdown_mode_enabled: false,
    description: undefined,
    controller: undefined,
    after_render_once: after_render_once_descriptor,
    post_description_form_close_callback: post_description_form_close_callback_descriptor,
    edition_form_presenter: {
        set: (host, presenter: DescriptionCommentFormPresenter | undefined) => presenter ?? null,
    },
    writing_zone_controller: {
        get: (host, controller: ControlWritingZone | undefined) =>
            controller ??
            WritingZoneController({
                document,
                focus_writing_zone_when_connected: true,
                is_comments_markdown_mode_enabled: host.is_comments_markdown_mode_enabled,
            }),
    },
    writing_zone: {
        get: getWritingZoneElement,
    },
    content: (host) => html`
        <div class="pull-request-comment pull-request-description-comment">
            ${getCommentAvatarTemplate(host.description.author)}
            ${getDescriptionContentTemplate(host, gettext_provider)}
            ${getDescriptionCommentFormTemplate(host, gettext_provider)}
        </div>
    `,
});
