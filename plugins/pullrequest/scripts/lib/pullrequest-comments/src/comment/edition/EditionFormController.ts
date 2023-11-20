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

import type { WritingZoneInteractionsHandler, PullRequestCommentErrorCallback } from "../../types";
import type { HostElement, InternalEditionForm } from "./EditionForm";
import type { SaveEditedComment } from "./EditedCommentSaver";
import { EditionFormPresenter } from "./EditionFormPresenter";
import { PullRequestCommentPresenter } from "../PullRequestCommentPresenter";

export type ControlEditionForm = WritingZoneInteractionsHandler<InternalEditionForm> & {
    initEditionForm(host: InternalEditionForm): void;
    cancelEdition(): void;
    saveEditedContent(host: HostElement): Promise<void>;
};

export const EditionFormController = (
    save_edited_comment: SaveEditedComment,
    post_submit_callback: (updated_comment: PullRequestCommentPresenter) => void,
    on_cancel_callback: () => void,
    on_error_callback: PullRequestCommentErrorCallback,
): ControlEditionForm => ({
    initEditionForm(host: InternalEditionForm): void {
        host.presenter = EditionFormPresenter.fromComment(host.comment);
        host.writing_zone_controller.setWritingZoneContent(
            host.writing_zone,
            host.presenter.edited_content,
        );
    },

    handleWritingZoneContentChange(host: InternalEditionForm, content: string): void {
        host.presenter = EditionFormPresenter.buildUpdated(host.presenter, content);
    },

    shouldFocusWritingZoneOnceRendered: () => true,

    cancelEdition: on_cancel_callback,

    saveEditedContent(host: HostElement): Promise<void> {
        host.presenter = EditionFormPresenter.buildSubmitted(host.presenter);

        return save_edited_comment.saveEditedComment(host.presenter).match(
            (edited_comment) => {
                post_submit_callback(
                    PullRequestCommentPresenter.fromEditedComment(host.comment, edited_comment),
                );
            },
            (fault) => {
                host.presenter = EditionFormPresenter.buildNotSubmitted(host.presenter);

                on_error_callback(fault);
            },
        );
    },
});
