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

import type { RegisteredUserWithAvatar } from "@tuleap/plugin-tracker-rest-api-types";
import { FollowUpCommentProxy } from "./FollowUpCommentProxy";
import type { FollowUpComment } from "../../../domain/comments/FollowUpComment";
import { ChangesetWithCommentRepresentationBuilder } from "../../../../tests/builders/ChangesetWithCommentRepresentationBuilder";
import {
    ANONYMOUS_DISPLAY_NAME,
    DEFAULT_AVATAR_URI,
} from "../../../../tests/stubs/CommentAuthorStub";

describe(`FollowUpCommentProxy`, () => {
    const FIRST_USER_NAME = "Louisa Shau (lshau)",
        FIRST_AVATAR_URI = "https://tuleap.example.com/users/lshau/avatar-89d46c.png",
        FIRST_PROFILE_URI = "/users/lshau",
        SUBMISSION_DATE = "2018-03-14T07:00:54+02:00",
        COMMENT_BODY = `<p>A <strong>CommonMark</strong> comment</p>`;

    it(`maps a Changeset comment representation to a FollowUpComment`, () => {
        const representation = ChangesetWithCommentRepresentationBuilder.aComment(41)
            .withPostProcessedBody(COMMENT_BODY, "html")
            .withSubmitter(
                {
                    display_name: FIRST_USER_NAME,
                    avatar_url: FIRST_AVATAR_URI,
                    user_url: FIRST_PROFILE_URI,
                    is_anonymous: false,
                } as RegisteredUserWithAvatar,
                SUBMISSION_DATE,
            )
            .build();

        const expected_user = {
            display_name: FIRST_USER_NAME,
            avatar_uri: FIRST_AVATAR_URI,
            profile_uri: FIRST_PROFILE_URI,
        };
        const expected: FollowUpComment = {
            body: COMMENT_BODY,
            submission_date: SUBMISSION_DATE,
            submitted_by: expected_user,
            last_modified_date: SUBMISSION_DATE,
            last_modified_by: expected_user,
        };
        expect(FollowUpCommentProxy.fromRepresentation(representation)).toStrictEqual(expected);
    });

    it(`maps a comment edited by a second user to a FollowUpComment`, () => {
        const SECOND_USER_NAME = "Laverne Nihart (lnihart)",
            SECOND_AVATAR_URI = "https://tuleap.example.com/users/lnihart/avatar-1c5dda.png",
            SECOND_PROFILE_URI = "/users/lnihart",
            MODIFICATION_DATE = "2019-06-06T03:00:57-03:00";

        const representation = ChangesetWithCommentRepresentationBuilder.aComment(85)
            .withPostProcessedBody(COMMENT_BODY, "html")
            .withSubmitter(
                {
                    display_name: FIRST_USER_NAME,
                    avatar_url: FIRST_AVATAR_URI,
                    user_url: FIRST_PROFILE_URI,
                    is_anonymous: false,
                } as RegisteredUserWithAvatar,
                SUBMISSION_DATE,
            )
            .withUpdate(
                {
                    display_name: SECOND_USER_NAME,
                    avatar_url: SECOND_AVATAR_URI,
                    user_url: SECOND_PROFILE_URI,
                    is_anonymous: false,
                } as RegisteredUserWithAvatar,
                MODIFICATION_DATE,
            )
            .build();

        const expected: FollowUpComment = {
            body: COMMENT_BODY,
            submission_date: SUBMISSION_DATE,
            submitted_by: {
                display_name: FIRST_USER_NAME,
                avatar_uri: FIRST_AVATAR_URI,
                profile_uri: FIRST_PROFILE_URI,
            },
            last_modified_date: MODIFICATION_DATE,
            last_modified_by: {
                display_name: SECOND_USER_NAME,
                avatar_uri: SECOND_AVATAR_URI,
                profile_uri: SECOND_PROFILE_URI,
            },
        };
        expect(FollowUpCommentProxy.fromRepresentation(representation)).toStrictEqual(expected);
    });

    it(`maps a comment from an anonymous user to a FollowUpComment`, () => {
        const EMAIL = "protend@recordative.example.com";

        const representation = ChangesetWithCommentRepresentationBuilder.aComment(56)
            .withPostProcessedBody(COMMENT_BODY, "html")
            .withAnonymousSubmitter(EMAIL, SUBMISSION_DATE)
            .build();

        const expected_user = {
            display_name: ANONYMOUS_DISPLAY_NAME,
            avatar_uri: DEFAULT_AVATAR_URI,
        };
        const expected: FollowUpComment = {
            body: COMMENT_BODY,
            email: EMAIL,
            submission_date: SUBMISSION_DATE,
            submitted_by: expected_user,
            last_modified_date: SUBMISSION_DATE,
            last_modified_by: expected_user,
        };
        expect(FollowUpCommentProxy.fromRepresentation(representation)).toStrictEqual(expected);
    });
});
