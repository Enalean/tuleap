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

import type { DescriptionCommentFormPresenter } from "./PullRequestDescriptionCommentFormPresenter";
import type { HelpRelativeDatesDisplay } from "../helpers/relative-dates-helper";
import type { PullRequestDescriptionComment } from "./PullRequestDescriptionComment";
import type {
    CurrentPullRequestUserPresenter,
    PullRequestCommentErrorCallback,
    WritingZoneInteractionsHandler,
} from "../types";
import type { SaveDescriptionComment } from "./PullRequestDescriptionCommentSaver";
import { PullRequestDescriptionCommentFormPresenter } from "./PullRequestDescriptionCommentFormPresenter";
import { PullRequestDescriptionCommentPresenter } from "./PullRequestDescriptionCommentPresenter";
import { RelativeDatesHelper } from "../helpers/relative-dates-helper";

export type ControlPullRequestDescriptionComment =
    WritingZoneInteractionsHandler<PullRequestDescriptionComment> & {
        showEditionForm: (host: PullRequestDescriptionComment) => void;
        hideEditionForm: (host: PullRequestDescriptionComment) => void;
        saveDescriptionComment: (host: PullRequestDescriptionComment) => void;
        getRelativeDateHelper: () => HelpRelativeDatesDisplay;
    };

export const PullRequestDescriptionCommentController = (
    description_saver: SaveDescriptionComment,
    current_user: CurrentPullRequestUserPresenter,
    on_error_callback: PullRequestCommentErrorCallback
): ControlPullRequestDescriptionComment => ({
    showEditionForm(host: PullRequestDescriptionComment): void {
        host.edition_form_presenter =
            PullRequestDescriptionCommentFormPresenter.fromCurrentDescription(host.description);

        host.writing_zone_controller.setWritingZoneContent(
            host.writing_zone,
            host.description.raw_content
        );
    },
    hideEditionForm,
    handleWritingZoneContentChange: (
        host: PullRequestDescriptionComment,
        new_description: string
    ): void => {
        host.edition_form_presenter =
            PullRequestDescriptionCommentFormPresenter.updateDescriptionContent(
                getExistingEditionFormPresenter(host),
                new_description
            );
    },
    saveDescriptionComment: (host: PullRequestDescriptionComment): void => {
        host.edition_form_presenter = PullRequestDescriptionCommentFormPresenter.buildSubmitted(
            getExistingEditionFormPresenter(host)
        );

        description_saver
            .saveDescriptionComment(
                host.edition_form_presenter,
                host.is_comments_markdown_mode_enabled
            )
            .match(
                (pull_request) => {
                    host.description =
                        PullRequestDescriptionCommentPresenter.fromPullRequestWithUpdatedDescription(
                            host.description,
                            pull_request
                        );
                    hideEditionForm(host);
                },
                (fault) => {
                    host.edition_form_presenter =
                        PullRequestDescriptionCommentFormPresenter.buildNotSubmitted(
                            getExistingEditionFormPresenter(host)
                        );
                    on_error_callback(fault);
                }
            );
    },
    getRelativeDateHelper: (): HelpRelativeDatesDisplay =>
        RelativeDatesHelper(
            current_user.preferred_date_format,
            current_user.preferred_relative_date_display,
            current_user.user_locale
        ),
    shouldFocusWritingZoneOnceRendered: () => true,
});

function hideEditionForm(host: PullRequestDescriptionComment): void {
    host.edition_form_presenter = null;
    host.post_description_form_close_callback();
}

function getExistingEditionFormPresenter(
    host: PullRequestDescriptionComment
): DescriptionCommentFormPresenter {
    const edition_form_presenter = host.edition_form_presenter;
    if (edition_form_presenter === null) {
        throw new Error(
            "Attempting to get edition form state while component is not in edition mode."
        );
    }
    return edition_form_presenter;
}
