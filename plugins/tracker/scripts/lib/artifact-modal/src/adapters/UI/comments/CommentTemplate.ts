/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { UpdateFunction } from "hybrids";
import { html } from "hybrids";
import moment from "moment";
import { sprintf } from "sprintf-js";
import { sanitize } from "dompurify";
import type { FirstDateShown, OtherDatePlacement } from "@tuleap/tlp-relative-date";
import { relativeDatePlacement, relativeDatePreference } from "@tuleap/tlp-relative-date";
import { formatFromPhpToMoment } from "@tuleap/date-helper";
import type { CommentUserPreferences } from "../../../domain/comments/CommentUserPreferences";
import type { FollowUpComment } from "../../../domain/comments/FollowUpComment";
import { getFollowupEditedBy } from "../../../gettext-catalog";
import type { ModalCommentsSection } from "./ModalCommentsSection";

type MapOfClasses = Record<string, boolean>;

const getSubmittedByTemplate = (comment: FollowUpComment): UpdateFunction<ModalCommentsSection> => {
    if ("email" in comment) {
        return html`<a href="mailto:${comment.email}" data-test="anonymous-submitter"
            >${comment.email}</a
        >`;
    }
    return html`<a href="${comment.submitted_by.profile_uri}" data-test="comment-submitter"
        >${comment.submitted_by.display_name}</a
    >`;
};

const getAuthorsClasses = (comment: FollowUpComment): MapOfClasses => ({
    "tuleap-artifact-modal-followups-comment-header-authors": true,
    "tlp-text-muted": true,
    "multiple-authors": comment.submission_date !== comment.last_modified_date,
});

const getAbsoluteDate = (date: string, preferences: CommentUserPreferences): string =>
    moment(date).format(formatFromPhpToMoment(preferences.date_time_format));

const getFirstDateShown = (preferences: CommentUserPreferences): FirstDateShown =>
    relativeDatePreference(preferences.relative_dates_display);

const getPlacement = (preferences: CommentUserPreferences): OtherDatePlacement =>
    relativeDatePlacement(preferences.relative_dates_display, "right");

const isEdited = (comment: FollowUpComment): boolean =>
    comment.submission_date !== comment.last_modified_date;

export const getCommentTemplate = (
    comment: FollowUpComment,
    preferences: CommentUserPreferences,
): UpdateFunction<ModalCommentsSection> => {
    return html`<div class="tuleap-artifact-modal-followups-comment">
        <div class="tuleap-artifact-modal-followups-comment-header">
            <div class="tlp-avatar">
                <img src="${comment.submitted_by.avatar_uri}" data-test="submitter-avatar" />
            </div>
            <div class="${getAuthorsClasses(comment)}" data-test="comment-authors">
                <div class="tuleap-artifact-modal-followups-comment-header-author">
                    ${getSubmittedByTemplate(comment)}
                    <tlp-relative-date
                        class="tuleap-artifact-modal-followups-comment-header-time"
                        data-test="comment-submission-date"
                        date="${comment.submission_date}"
                        absolute-date="${getAbsoluteDate(comment.submission_date, preferences)}"
                        preference="${getFirstDateShown(preferences)}"
                        placement="${getPlacement(preferences)}"
                        locale="${preferences.locale}"
                        >${getAbsoluteDate(comment.submission_date, preferences)}
                    </tlp-relative-date>
                </div>
                ${isEdited(comment) &&
                html`<div class="tuleap-artifact-modal-followups-comment-header-author">
                    <span data-test="comment-modifier"
                        >${sprintf(getFollowupEditedBy(), {
                            user: comment.last_modified_by.display_name,
                        })}</span
                    >
                    <tlp-relative-date
                        class="tuleap-artifact-modal-followups-comment-header-time"
                        data-test="comment-modification-date"
                        date="${comment.last_modified_date}"
                        absolute-date="${getAbsoluteDate(comment.last_modified_date, preferences)}"
                        preference="${getFirstDateShown(preferences)}"
                        placement="${getPlacement(preferences)}"
                        locale="${preferences.locale}"
                        >${getAbsoluteDate(
                            comment.last_modified_date,
                            preferences,
                        )}</tlp-relative-date
                    >
                </div>`}
            </div>
        </div>
        <div
            class="tuleap-artifact-modal-followups-comment-content"
            data-test="comment-body"
            innerHTML="${sanitize(comment.body)}"
        ></div>
    </div>`;
};
