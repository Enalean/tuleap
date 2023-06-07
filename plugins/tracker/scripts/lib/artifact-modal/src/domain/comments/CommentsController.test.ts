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

import { Fault } from "@tuleap/fault";
import type { CommentsControllerType } from "./CommentsController";
import { CommentsController } from "./CommentsController";
import type { RetrieveComments } from "./RetrieveComments";
import { RetrieveCommentsStub } from "../../../tests/stubs/RetrieveCommentsStub";
import { CurrentArtifactIdentifierStub } from "../../../tests/stubs/CurrentArtifactIdentifierStub";
import { CurrentProjectIdentifierStub } from "../../../tests/stubs/CurrentProjectIdentifierStub";
import { CommentUserPreferencesBuilder } from "../../../tests/builders/CommentUserPreferencesBuilder";
import type { CommentUserPreferences } from "./CommentUserPreferences";
import { FollowUpCommentBuilder } from "../../../tests/builders/FollowUpCommentBuilder";
import { DispatchEventsStub } from "../../../tests/stubs/DispatchEventsStub";

const PROJECT_ID = 196;
const FIRST_COMMENT_BODY = "<p>An HTML comment</p>";
const SECOND_COMMENT_BODY = "Plain text comment";

describe(`CommentsController`, () => {
    let comments_retriever: RetrieveComments,
        event_dispatcher: DispatchEventsStub,
        user_preferences: CommentUserPreferences;

    beforeEach(() => {
        comments_retriever = RetrieveCommentsStub.withComments(
            FollowUpCommentBuilder.aComment().withBody(FIRST_COMMENT_BODY).build(),
            FollowUpCommentBuilder.aComment().withBody(SECOND_COMMENT_BODY).build()
        );
        event_dispatcher = DispatchEventsStub.withRecordOfEventTypes();
        user_preferences = CommentUserPreferencesBuilder.userPreferences().build();
    });

    const getController = (): CommentsControllerType =>
        CommentsController(
            comments_retriever,
            event_dispatcher,
            CurrentArtifactIdentifierStub.withId(45),
            CurrentProjectIdentifierStub.withId(PROJECT_ID),
            user_preferences
        );

    describe(`getPreferences()`, () => {
        it(`returns the user preferences passed to the controller`, () => {
            expect(getController().getPreferences()).toBe(user_preferences);
        });
    });

    describe(`getProjectIdentifier()`, () => {
        it(`returns the project id passed to the controller`, () => {
            expect(getController().getProjectIdentifier().id).toBe(PROJECT_ID);
        });
    });

    describe(`getComments()`, () => {
        it(`returns an array of FollowUpComments`, async () => {
            const comments = await getController().getComments();

            expect(comments).toHaveLength(2);
            const bodies = comments.map((comment) => comment.body);
            expect(bodies).toContain(FIRST_COMMENT_BODY);
            expect(bodies).toContain(SECOND_COMMENT_BODY);
        });

        it(`when there is an error, it will notify the fault and return an empty array`, async () => {
            comments_retriever = RetrieveCommentsStub.withFault(
                Fault.fromMessage("Internal server error")
            );
            const comments = await getController().getComments();

            expect(comments).toHaveLength(0);
            expect(event_dispatcher.getDispatchedEventTypes()).toContain("WillNotifyFault");
        });
    });
});
