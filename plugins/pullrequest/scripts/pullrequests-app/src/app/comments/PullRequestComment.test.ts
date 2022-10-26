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

import { selectOrThrow } from "@tuleap/dom";
import type { HostElement, PullRequestCommentPresenter } from "./PullRequestComment";
import { PullRequestComment } from "./PullRequestComment";

describe("PullRequestComment", () => {
    let target: ShadowRoot;

    function getComment(
        is_inline_comment: boolean,
        is_outdated: boolean,
        has_file: boolean
    ): PullRequestCommentPresenter {
        const file = has_file
            ? {
                  file: {
                      file_path: "README.md",
                      file_url: "url/to/readme.md",
                  },
              }
            : {};

        return {
            user: {
                avatar_url: "https://example.com/John/Doe/avatar.png",
                display_name: "John Doe",
                user_url: "https://example.com/John/Doe/profile.html",
            },
            post_date: "a moment ago",
            content: "Please rebase",
            type: is_inline_comment ? "inline-comment" : "comment",
            is_outdated,
            is_inline_comment,
            ...file,
        };
    }

    beforeEach(() => {
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
    });

    describe("Display", () => {
        it(`Given a not outdated inline comment,
            Then it should display the file name on which the comment has been written with a link to it.`, () => {
            const host = { comment: getComment(true, false, true) } as unknown as HostElement;
            const update = PullRequestComment.content(host);

            update(host, target);

            const root = selectOrThrow(target, "[data-test=pullrequest-comment]");
            const displayed_file = selectOrThrow(
                target,
                "[data-test=pullrequest-comment-with-link-to-file]"
            );
            const link_to_file = selectOrThrow(displayed_file, "a", HTMLAnchorElement);

            expect(root.classList).toContain("pull-request-comment");
            expect(root.classList).toContain("is-inline-comment");
            expect(root.classList).toContain("inline-comment");

            expect(link_to_file.href).toBe("url/to/readme.md");
            expect(link_to_file.textContent?.trim()).toBe("README.md");
        });

        it(`Given an outdated inline comment,
            Then it should display only the file name on which the comment has been written with no link to it.`, () => {
            const host = { comment: getComment(true, true, true) } as unknown as HostElement;
            const update = PullRequestComment.content(host);

            update(host, target);

            const root = selectOrThrow(target, "[data-test=pullrequest-comment]");
            const displayed_file = selectOrThrow(
                target,
                "[data-test=pullrequest-comment-only-file-name]"
            );

            expect(root.classList).toContain("pull-request-comment");
            expect(root.classList).toContain("is-inline-comment");
            expect(root.classList).toContain("inline-comment");
            expect(root.classList).toContain("is-outdated");

            expect(displayed_file.querySelector("a")).toBeNull();
            expect(displayed_file.textContent?.trim()).toBe("README.md");
        });

        it(`Given the comment has no file, Then it should not display a file name`, () => {
            const host = { comment: getComment(false, false, false) } as unknown as HostElement;
            const update = PullRequestComment.content(host);

            update(host, target);

            const root = selectOrThrow(target, "[data-test=pullrequest-comment]");

            expect(root.classList).toContain("pull-request-comment");
            expect(root.classList).toContain("comment");

            expect(
                target.querySelector("[data-test=pullrequest-comment-with-link-to-file]")
            ).toBeNull();
            expect(
                target.querySelector("[data-test=pullrequest-comment-only-file-name]")
            ).toBeNull();
        });

        it("should execute the post_rendering_callback each time the component renders", () => {
            const post_rendering_callback = jest.fn();
            const host = {
                comment: getComment(false, false, false),
                post_rendering_callback,
            } as unknown as HostElement;

            jest.useFakeTimers();

            const update = PullRequestComment.content(host);
            update(host, target);

            jest.advanceTimersByTime(1);

            expect(post_rendering_callback).toHaveBeenCalledTimes(1);
        });
    });
});
