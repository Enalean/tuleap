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
    FollowUpComment,
    RegisteredCommentAuthor,
} from "../../src/domain/comments/FollowUpComment";
import { CommentAuthorStub } from "../stubs/CommentAuthorStub";

export class FollowUpCommentBuilder {
    #body = "<p>subnuvolar slitter <strong>piscine</strong> underpick</p>";
    #anonymous_email: string | null = null;
    #submission_date = "2021-12-27T14:49:41-02:00";
    #submitter = CommentAuthorStub.aRegisteredUser("Estela Hergenrader", "ehergenrader");
    #last_update_date: string | null = null;
    #modifier: RegisteredCommentAuthor | null = null;

    private constructor() {
        // Prefer static method for instantiation
    }

    static aComment(): FollowUpCommentBuilder {
        return new FollowUpCommentBuilder();
    }

    withBody(content: string): this {
        this.#body = content;
        return this;
    }

    withSubmitter(submitter: RegisteredCommentAuthor, submission_date: string): this {
        this.#submitter = submitter;
        this.#submission_date = submission_date;
        return this;
    }

    withAnonymousSubmitter(email: string, submission_date: string): this {
        this.#anonymous_email = email;
        this.#submission_date = submission_date;
        return this;
    }

    withUpdate(modifier: RegisteredCommentAuthor, last_update_date: string): this {
        this.#modifier = modifier;
        this.#last_update_date = last_update_date;
        return this;
    }

    build(): FollowUpComment {
        if (this.#anonymous_email === null) {
            return {
                body: this.#body,
                submission_date: this.#submission_date,
                submitted_by: this.#submitter,
                last_modified_date: this.#last_update_date ?? this.#submission_date,
                last_modified_by: this.#modifier ?? this.#submitter,
            };
        }

        const submitter = CommentAuthorStub.anAnonymousUser();
        return {
            body: this.#body,
            submission_date: this.#submission_date,
            email: this.#anonymous_email,
            submitted_by: submitter,
            last_modified_date: this.#last_update_date ?? this.#submission_date,
            last_modified_by: this.#modifier ?? submitter,
        };
    }
}
