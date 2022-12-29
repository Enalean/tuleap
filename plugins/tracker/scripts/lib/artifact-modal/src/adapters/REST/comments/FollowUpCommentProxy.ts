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

import type {
    ChangesetWithCommentRepresentation,
    AnonymousUserWithAvatar,
    RegisteredUserWithAvatar,
} from "@tuleap/plugin-tracker-rest-api-types";
import type {
    FollowUpComment,
    RegisteredCommentAuthor,
    AnonymousCommentAuthor,
} from "../../../domain/comments/FollowUpComment";

const mapAnonymousUser = (representation: AnonymousUserWithAvatar): AnonymousCommentAuthor => ({
    display_name: representation.display_name,
    avatar_uri: representation.avatar_url,
});

const mapRegisteredUser = (representation: RegisteredUserWithAvatar): RegisteredCommentAuthor => ({
    display_name: representation.display_name,
    avatar_uri: representation.avatar_url,
    profile_uri: representation.user_url,
});

export const FollowUpCommentProxy = {
    fromRepresentation: (comment: ChangesetWithCommentRepresentation): FollowUpComment => {
        const modification_author = comment.last_modified_by.is_anonymous
            ? mapAnonymousUser(comment.last_modified_by)
            : mapRegisteredUser(comment.last_modified_by);

        const mapping = {
            body: comment.last_comment.post_processed_body,
            submission_date: comment.submitted_on,
            last_modified_date: comment.last_modified_date,
            last_modified_by: modification_author,
        };
        if (comment.email !== null) {
            return {
                ...mapping,
                email: comment.email,
                submitted_by: mapAnonymousUser(comment.submitted_by_details),
            };
        }
        return {
            ...mapping,
            submitted_by: mapRegisteredUser(comment.submitted_by_details),
        };
    },
};
