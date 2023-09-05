/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { describe, beforeEach, expect, it } from "vitest";
import { selectOrThrow } from "@tuleap/dom";
import {
    TYPE_INLINE_COMMENT,
    FORMAT_COMMONMARK,
    FORMAT_TEXT,
} from "@tuleap/plugin-pullrequest-constants";
import { PullRequestCommentPresenterStub } from "../../tests/stubs/PullRequestCommentPresenterStub";
import { RelativeDateHelperStub } from "../../tests/stubs/RelativeDateHelperStub";
import { GettextProviderStub } from "../../tests/stubs/GettextProviderStub";
import { getCommentBody } from "./PullRequestCommentBodyTemplate";
import type { HostElement } from "./PullRequestComment";
import type { CommonComment } from "./PullRequestCommentPresenter";

describe("PullRequestCommentBodyTemplate", () => {
    let target: ShadowRoot;

    beforeEach(() => {
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
    });

    it(`Given a not outdated inline comment,
        Then it should display the file name on which the comment has been written with a link to it.`, () => {
        const host = {
            comment: PullRequestCommentPresenterStub.buildInlineComment(),
            relative_date_helper: RelativeDateHelperStub,
        } as unknown as HostElement;
        const render = getCommentBody(host, GettextProviderStub);

        render(host, target);

        const displayed_file = selectOrThrow(
            target,
            "[data-test=pullrequest-comment-with-link-to-file]",
        );
        const link_to_file = selectOrThrow(displayed_file, "a", HTMLAnchorElement);

        expect(link_to_file.href).toBe("url/to/readme.md");
        expect(link_to_file.textContent?.trim()).toBe("README.md");
    });

    it(`Given an outdated inline comment,
        Then it should display only the file name on which the comment has been written with no link to it
        And a badge flagging it as outdated`, () => {
        const host = {
            comment: PullRequestCommentPresenterStub.buildInlineCommentOutdated(),
            relative_date_helper: RelativeDateHelperStub,
        } as unknown as HostElement;
        const render = getCommentBody(host, GettextProviderStub);

        render(host, target);

        const displayed_file = selectOrThrow(
            target,
            "[data-test=pullrequest-comment-only-file-name]",
        );

        const body = selectOrThrow(target, "[data-test=pull-request-comment-body]");
        const outdated_badge = selectOrThrow(target, "[data-test=comment-outdated-badge]");

        expect(displayed_file.querySelector("a")).toBeNull();
        expect(displayed_file.textContent?.trim()).toBe("README.md");
        expect(Array.from(body.classList)).toContain("pull-request-comment-outdated");
        expect(outdated_badge).not.toBeNull();
    });

    it.each([
        ["a global comment", PullRequestCommentPresenterStub.buildGlobalComment()],
        [
            "a pull-request event comment",
            PullRequestCommentPresenterStub.buildPullRequestEventComment(),
        ],
        [
            "an inline-comment which is a reply to another inline-comment",
            PullRequestCommentPresenterStub.buildInlineCommentWithData({
                parent_id: 12,
                type: TYPE_INLINE_COMMENT,
                file: {
                    file_url: "an/url/to/README.md",
                    file_path: "README.md",
                    unidiff_offset: 8,
                    position: "right",
                    is_displayed: true,
                },
            }),
        ],
    ])(`Given %s, Then it should not display a file name`, (expectation, comment) => {
        const host = {
            comment,
            relative_date_helper: RelativeDateHelperStub,
        } as unknown as HostElement;
        const render = getCommentBody(host, GettextProviderStub);

        render(host, target);

        expect(
            target.querySelector("[data-test=pullrequest-comment-with-link-to-file]"),
        ).toBeNull();
        expect(target.querySelector("[data-test=pullrequest-comment-only-file-name]")).toBeNull();
    });

    it.each([
        ["post_processed_content" as keyof CommonComment, FORMAT_COMMONMARK],
        ["content" as keyof CommonComment, FORMAT_TEXT],
    ])(`should display the comment's %s when its format is %s`, (expected_content_key, format) => {
        const host = {
            relative_date_helper: RelativeDateHelperStub,
            comment: PullRequestCommentPresenterStub.buildInlineCommentWithData({
                content: "Text content",
                post_processed_content: "Processed commonmark content",
                format,
            }),
        } as unknown as HostElement;

        const render = getCommentBody(host, GettextProviderStub);

        render(host, target);

        const content = selectOrThrow(target, "[data-test=pull-request-comment-text]");

        expect(content.textContent?.trim()).toBe(host.comment[expected_content_key]);
    });
});
