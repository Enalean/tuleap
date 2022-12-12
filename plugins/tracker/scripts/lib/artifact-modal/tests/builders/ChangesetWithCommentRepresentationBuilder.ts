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
    AnonymousUserWithAvatar,
    ChangesetWithCommentRepresentation,
    RegisteredUserWithAvatar,
} from "@tuleap/plugin-tracker-rest-api-types";
import { ANONYMOUS_DISPLAY_NAME, DEFAULT_AVATAR_URI } from "../stubs/CommentAuthorStub";

export class ChangesetWithCommentRepresentationBuilder {
    readonly #comment_id: number;
    #post_processed_body = "<p>camphanyl Algerine <strong>schiller</strong> nosocomium</p>";
    #format: "text" | "html" = "html";
    #anonymous_email: string | null = null;
    #submission_date = "2022-10-23T02:56:58+04:00";
    #submitter: RegisteredUserWithAvatar = {
        is_anonymous: false,
        display_name: "Laronda Dibiasi (ldibiasi)",
        avatar_url: "https://tuleap.example.com/users/ldibiasi/avatar-0d29b3.png",
        user_url: "/users/ldibiasi",
        id: 159,
        ldap_id: "184",
        username: "ldibiasi",
        real_name: "Laronda Dibiasi",
        has_avatar: true,
        uri: "",
    };
    #last_update_date: string | null = null;
    #modifier: RegisteredUserWithAvatar | null = null;

    private constructor(id: number) {
        this.#comment_id = id;
    }

    static aComment(id: number): ChangesetWithCommentRepresentationBuilder {
        return new ChangesetWithCommentRepresentationBuilder(id);
    }

    withPostProcessedBody(content: string, format: "text" | "html"): this {
        this.#post_processed_body = content;
        this.#format = format;
        return this;
    }

    withSubmitter(submitter: RegisteredUserWithAvatar, submission_date: string): this {
        this.#submitter = submitter;
        this.#submission_date = submission_date;
        return this;
    }

    withAnonymousSubmitter(email: string, submission_date: string): this {
        this.#anonymous_email = email;
        this.#submission_date = submission_date;
        return this;
    }

    withUpdate(modifier: RegisteredUserWithAvatar, last_update_date: string): this {
        this.#modifier = modifier;
        this.#last_update_date = last_update_date;
        return this;
    }

    build(): ChangesetWithCommentRepresentation {
        if (this.#anonymous_email === null) {
            return {
                id: this.#comment_id,
                email: null,
                last_comment: {
                    post_processed_body: this.#post_processed_body,
                    format: this.#format,
                },
                submitted_on: this.#submission_date,
                submitted_by_details: this.#submitter,
                last_modified_date: this.#last_update_date ?? this.#submission_date,
                last_modified_by: this.#modifier ?? this.#submitter,
            };
        }
        const submitter: AnonymousUserWithAvatar = {
            is_anonymous: true,
            display_name: ANONYMOUS_DISPLAY_NAME,
            avatar_url: DEFAULT_AVATAR_URI,
            user_url: null,
            id: null,
            ldap_id: null,
            username: null,
            real_name: null,
            has_avatar: true,
            uri: null,
        };

        return {
            id: this.#comment_id,
            email: this.#anonymous_email,
            last_comment: {
                post_processed_body: this.#post_processed_body,
                format: this.#format,
            },
            submitted_on: this.#submission_date,
            submitted_by_details: submitter,
            last_modified_date: this.#last_update_date ?? this.#submission_date,
            last_modified_by: this.#modifier ?? submitter,
        };
    }
}
