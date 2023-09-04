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

import { selectOrThrow } from "@tuleap/dom";
import { RelativeDateElement } from "@tuleap/tlp-relative-date";
import { setCatalog } from "../../../gettext-catalog";
import type { FollowUpComment } from "../../../domain/comments/FollowUpComment";
import type { HostElement } from "./ModalCommentsSection";
import { getCommentTemplate } from "./CommentTemplate";
import { CommentUserPreferencesBuilder } from "../../../../tests/builders/CommentUserPreferencesBuilder";
import { FollowUpCommentBuilder } from "../../../../tests/builders/FollowUpCommentBuilder";
import { CommentAuthorStub } from "../../../../tests/stubs/CommentAuthorStub";

// Moment does not actually "export default" which leads to `"moment_1.default" is not a function` error (but only in jest)
jest.mock("moment", () => ({
    default: jest.requireActual("moment"),
}));

describe(`CommentTemplate`, () => {
    let target: ShadowRoot;
    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
    });

    const render = (comment: FollowUpComment): void => {
        const host = {} as HostElement;
        const updateFunction = getCommentTemplate(
            comment,
            CommentUserPreferencesBuilder.userPreferences()
                .withLocale("fr_FR")
                .withRelativeDatesDisplay("relative_first-absolute_tooltip")
                .build(),
        );
        updateFunction(host, target);
    };

    it(`will render a follow-up comment submitted by registered user`, () => {
        const comment = FollowUpCommentBuilder.aComment()
            .withBody("<p>Rendered <strong>Markdown</strong></p>")
            .withSubmitter(
                CommentAuthorStub.aRegisteredUser("Drema Biedermann", "dbiedermann"),
                "2019-02-01T04:42:06+05:00",
            )
            .build();

        render(comment);

        const body = selectOrThrow(target, "[data-test=comment-body]");
        const avatar = selectOrThrow(target, "[data-test=submitter-avatar]", HTMLImageElement);
        const authors = selectOrThrow(target, "[data-test=comment-authors]");
        const submitter = selectOrThrow(target, "[data-test=comment-submitter]");
        const submission_date = selectOrThrow(
            target,
            "[data-test=comment-submission-date]",
            RelativeDateElement,
        );

        expect(body.innerHTML).toBe(comment.body);
        expect(avatar.src).toBe(comment.submitted_by.avatar_uri);
        expect(submitter.textContent?.trim()).toBe(comment.submitted_by.display_name);
        expect(authors.classList.contains("multiple-authors")).toBe(false);
        expect(submission_date.date).toBe(comment.submission_date);
        expect(submission_date.preference).toBe("relative");
        expect(submission_date.placement).toBe("tooltip");
        expect(submission_date.locale).toContain("FR");
    });

    it(`will render a follow-up comment edited by a user`, () => {
        const comment = FollowUpCommentBuilder.aComment()
            .withBody("Plain text comment")
            .withSubmitter(
                CommentAuthorStub.aRegisteredUser("Alisa Davidian", "adavidian"),
                "2014-08-26T05:57:44+10:00",
            )
            .withUpdate(
                CommentAuthorStub.aRegisteredUser("Kassandra Beekman", "kbeekman"),
                "2015-12-26T20:54:47-05:00",
            )
            .build();

        render(comment);

        const body = selectOrThrow(target, "[data-test=comment-body]");
        const authors = selectOrThrow(target, "[data-test=comment-authors]");
        const modifier = selectOrThrow(target, "[data-test=comment-modifier]");
        const modification_date = selectOrThrow(
            target,
            "[data-test=comment-modification-date]",
            RelativeDateElement,
        );

        expect(body.innerHTML).toBe(comment.body);
        expect(authors.classList.contains("multiple-authors")).toBe(true);
        expect(modifier.textContent?.trim()).toContain(comment.last_modified_by.display_name);
        expect(modification_date.date).toBe(comment.last_modified_date);
        expect(modification_date.preference).toBe("relative");
        expect(modification_date.placement).toBe("tooltip");
        expect(modification_date.locale).toContain("FR");
    });

    it(`will render a follow-up comment submitted by anonymous user`, () => {
        const EMAIL = "nereida.laplante@example.com";

        const comment = FollowUpCommentBuilder.aComment()
            .withAnonymousSubmitter(EMAIL, "2019-10-09T05:33:39+12:00")
            .build();

        render(comment);

        const email_link = selectOrThrow(
            target,
            "[data-test=anonymous-submitter]",
            HTMLAnchorElement,
        );

        expect(email_link.href).toContain(EMAIL);
    });
});
