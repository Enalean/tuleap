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

import type { SaveNewComment } from "./NewCommentSaver";
import type { PullRequestCommentErrorCallback, WritingZoneInteractionsHandler } from "../types";
import type { NewCommentForm } from "./NewCommentForm";
import type { PullRequestComment } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { NewCommentFormAuthorPresenter } from "./NewCommentFormPresenter";
import { NewCommentFormPresenter } from "./NewCommentFormPresenter";

export interface NewCommentFormComponentConfig {
    readonly is_cancel_allowed: boolean;
    readonly is_autofocus_enabled: boolean;
}

export type ControlNewCommentForm = WritingZoneInteractionsHandler<NewCommentForm> & {
    buildInitialPresenter: (host: NewCommentForm) => void;
    saveNewComment: (host: NewCommentForm) => void;
    cancelNewComment: (host: NewCommentForm) => void;
    triggerPostSubmitCallback: NewCommentPostSubmitCallback;
    shouldFocusWritingZoneOnceRendered: () => boolean;
};

export type NewCommentCancelCallback = () => void;
export type NewCommentPostSubmitCallback = (new_comment_payload: PullRequestComment) => void;

export const NewCommentFormController = (
    comment_saver: SaveNewComment,
    author: NewCommentFormAuthorPresenter,
    config: NewCommentFormComponentConfig,
    post_submit_callback: NewCommentPostSubmitCallback,
    on_error_callback: PullRequestCommentErrorCallback,
    on_cancel_callback?: NewCommentCancelCallback
): ControlNewCommentForm => ({
    buildInitialPresenter: (host: NewCommentForm): void => {
        host.presenter = NewCommentFormPresenter.buildFromAuthor(author, config);
    },
    cancelNewComment: (host: NewCommentForm): void => {
        host.presenter = NewCommentFormPresenter.buildFromAuthor(author, config);
        on_cancel_callback?.();
    },
    saveNewComment: (host: NewCommentForm): void => {
        host.presenter = NewCommentFormPresenter.buildSavingComment(host.presenter);

        comment_saver
            .postComment(host.presenter.comment, host.is_comments_markdown_mode_enabled)
            .match(
                (payload: PullRequestComment) => {
                    post_submit_callback(payload);

                    host.writing_zone_controller.resetWritingZone(host.writing_zone);
                    host.presenter = NewCommentFormPresenter.buildFromAuthor(author, config);
                },
                (fault) => {
                    host.presenter = NewCommentFormPresenter.buildNotSavingComment(host.presenter);
                    on_error_callback(fault);
                }
            );
    },
    handleWritingZoneContentChange: (host: NewCommentForm, new_comment: string): void => {
        host.presenter = NewCommentFormPresenter.buildWithUpdatedComment(
            host.presenter,
            new_comment
        );
    },
    triggerPostSubmitCallback: (new_comment_payload: PullRequestComment): void => {
        /**
         * Expose a method to manually trigger the post_submit_callback.
         * For testing purpose only
         */
        post_submit_callback(new_comment_payload);
    },
    shouldFocusWritingZoneOnceRendered: () => config.is_autofocus_enabled,
});
