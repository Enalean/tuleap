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

import type { FollowUpComment } from "./FollowUpComment";
import type { RetrieveComments } from "./RetrieveComments";
import type { CurrentArtifactIdentifier } from "../CurrentArtifactIdentifier";
import type { NotifyFault } from "../NotifyFault";
import type { CommentUserPreferences } from "./CommentUserPreferences";
import type { ProjectIdentifier } from "../ProjectIdentifier";
import { CommentsRetrievalFault } from "./CommentsRetrievalFault";

export type CommentsControllerType = {
    getPreferences(): CommentUserPreferences;
    getComments(): PromiseLike<readonly FollowUpComment[]>;
    getProjectIdentifier(): ProjectIdentifier;
};

export const CommentsController = (
    comments_retriever: RetrieveComments,
    fault_notifier: NotifyFault,
    current_artifact_identifier: CurrentArtifactIdentifier,
    project_id: ProjectIdentifier,
    user_preferences: CommentUserPreferences
): CommentsControllerType => ({
    getPreferences: () => user_preferences,
    getProjectIdentifier: () => project_id,

    getComments: (): PromiseLike<readonly FollowUpComment[]> => {
        return comments_retriever
            .getComments(current_artifact_identifier, user_preferences.is_comment_order_inverted)
            .match(
                (comments) => comments,
                (fault) => {
                    fault_notifier.onFault(CommentsRetrievalFault(fault));
                    return [];
                }
            );
    },
});
