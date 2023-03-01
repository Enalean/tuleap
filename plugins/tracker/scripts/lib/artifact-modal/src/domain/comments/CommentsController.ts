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
import type { CommentUserPreferences } from "./CommentUserPreferences";
import { CommentsRetrievalFault } from "./CommentsRetrievalFault";
import type { DispatchEvents } from "../DispatchEvents";
import { WillNotifyFault } from "../WillNotifyFault";

export type CommentsControllerType = {
    getPreferences(): CommentUserPreferences;
    getComments(): PromiseLike<readonly FollowUpComment[]>;
};

export const CommentsController = (
    comments_retriever: RetrieveComments,
    event_dispatcher: DispatchEvents,
    current_artifact_identifier: CurrentArtifactIdentifier,
    user_preferences: CommentUserPreferences
): CommentsControllerType => ({
    getPreferences: () => user_preferences,

    getComments: (): PromiseLike<readonly FollowUpComment[]> => {
        return comments_retriever
            .getComments(current_artifact_identifier, user_preferences.is_comment_order_inverted)
            .match(
                (comments) => comments,
                (fault) => {
                    event_dispatcher.dispatch(WillNotifyFault(CommentsRetrievalFault(fault)));
                    return [];
                }
            );
    },
});
