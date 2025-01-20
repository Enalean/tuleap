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

import type { PullRequestComment } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { PullRequestCommentErrorCallback } from "../types";
import type { NewCommentForm } from "./NewCommentForm";
import { NewCommentFormPresenter } from "./NewCommentFormPresenter";
import type { NewCommentFormAuthorPresenter } from "./NewCommentFormPresenter";
import type { CommentContext, SaveComment } from "./types";

export interface NewCommentFormComponentConfig {
    readonly is_cancel_allowed: boolean;
    readonly is_autofocus_enabled: boolean;
    readonly project_id: number;
}

export type ControlNewCommentForm = {
    shouldFocusWritingZoneOnceRendered(): boolean;
    buildInitialPresenter(): NewCommentFormPresenter;
    saveNewComment(host: NewCommentForm): Promise<void>;
    cancelNewComment(host: NewCommentForm): void;
    triggerPostSubmitCallback: NewCommentPostSubmitCallback;
    getProjectId(): number;
};

export type NewCommentCancelCallback = () => void;
export type NewCommentPostSubmitCallback = (new_comment_payload: PullRequestComment) => void;

export const NewCommentFormController = (
    comment_saver: SaveComment,
    author: NewCommentFormAuthorPresenter,
    config: NewCommentFormComponentConfig,
    comment_creation_context: CommentContext,
    post_submit_callback: NewCommentPostSubmitCallback,
    on_error_callback: PullRequestCommentErrorCallback,
    on_cancel_callback?: NewCommentCancelCallback,
): ControlNewCommentForm => ({
    buildInitialPresenter(): NewCommentFormPresenter {
        return NewCommentFormPresenter.buildFromAuthor(author, config);
    },
    cancelNewComment(host: NewCommentForm): void {
        host.presenter = NewCommentFormPresenter.buildFromAuthor(author, config);
        on_cancel_callback?.();
    },
    saveNewComment(host: NewCommentForm): Promise<void> {
        host.presenter = NewCommentFormPresenter.buildSubmitted(host.presenter);

        return comment_saver.saveComment(host.presenter, comment_creation_context).match(
            (payload: PullRequestComment) => {
                post_submit_callback(payload);

                host.writing_zone_controller.resetWritingZone(host.writing_zone);
                host.presenter = NewCommentFormPresenter.buildFromAuthor(author, config);
            },
            (fault) => {
                host.presenter = NewCommentFormPresenter.buildNotSubmitted(host.presenter);
                on_error_callback(fault);
            },
        );
    },
    triggerPostSubmitCallback(new_comment_payload: PullRequestComment): void {
        /**
         * Expose a method to manually trigger the post_submit_callback.
         * For testing purpose only
         */
        post_submit_callback(new_comment_payload);
    },
    shouldFocusWritingZoneOnceRendered: () => config.is_autofocus_enabled,
    getProjectId: () => config.project_id,
});
