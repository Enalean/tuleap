/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach } from "vitest";
import { PullRequestCommentPresenterStub } from "../../../tests/stubs/PullRequestCommentPresenterStub";
import { RelativeDateHelperStub } from "../../../tests/stubs/RelativeDateHelperStub";
import type { PullRequestCommentPresenter } from "../PullRequestCommentPresenter";
import type { HostElement } from "./PullRequestCommentReply";
import {
    getBodyClasses,
    getCommentContentClasses,
    getFollowUpClasses,
} from "./PullRequestCommentReplyTemplate";

describe("PullRequestCommentReplyTemplate", () => {
    let comment: PullRequestCommentPresenter,
        parent_element_color: string,
        is_in_edition_mode: boolean;

    beforeEach(() => {
        comment = PullRequestCommentPresenterStub.buildGlobalComment();
        parent_element_color = "red-wine";
        is_in_edition_mode = false;
    });

    const getHost = (): HostElement =>
        ({
            comment,
            is_in_edition_mode,
            relative_date_helper: RelativeDateHelperStub,
            parent_element: {
                comment: PullRequestCommentPresenterStub.buildGlobalCommentWithData({
                    color: parent_element_color,
                }),
            },
        }) as HostElement;

    describe("getCommentContentClasses()", () => {
        it("should assign color classes when the parent comment has a color and the comment is not being edited", () => {
            parent_element_color = "red-wine";
            is_in_edition_mode = false;

            expect(getCommentContentClasses(getHost())).toStrictEqual({
                "pull-request-comment-content": true,
                "pull-request-comment-content-color": true,
                "tlp-swatch-red-wine": true,
            });
        });

        it("should NOT assign color classes when the parent comment has NOT a color", () => {
            parent_element_color = "";
            is_in_edition_mode = false;

            expect(getCommentContentClasses(getHost())).toStrictEqual({
                "pull-request-comment-content": true,
            });
        });

        it("should NOT assign color classes when the comment is being edited", () => {
            parent_element_color = "red-wine";
            is_in_edition_mode = true;

            expect(getCommentContentClasses(getHost())).toStrictEqual({
                "pull-request-comment-content": true,
            });
        });
    });

    describe("getFollowUpClasses()", () => {
        it("should assign color classes when the parent comment has a color", () => {
            parent_element_color = "red-wine";

            expect(getFollowUpClasses(getHost())).toStrictEqual({
                "pull-request-comment-follow-up": true,
                "pull-request-comment-follow-up-color": true,
                "tlp-swatch-red-wine": true,
            });
        });

        it("should NOT assign color classes when the parent comment has NOT a color", () => {
            parent_element_color = "";

            expect(getFollowUpClasses(getHost())).toStrictEqual({
                "pull-request-comment-follow-up": true,
            });
        });
    });

    describe("getBodyClasses()", () => {
        it("should have an outdated class when the comment is inline and is outdated", () => {
            comment = PullRequestCommentPresenterStub.buildInlineCommentOutdated();

            expect(getBodyClasses(getHost())).toStrictEqual({
                "pull-request-comment-outdated": true,
            });
        });

        it("should NOT have an outdated class when the comment is inline but is not outdated", () => {
            comment = PullRequestCommentPresenterStub.buildInlineComment();

            expect(getBodyClasses(getHost())).toStrictEqual({
                "pull-request-comment-outdated": false,
            });
        });

        it("should NOT have an outdated class when the comment is a global comment", () => {
            comment = PullRequestCommentPresenterStub.buildGlobalComment();

            expect(getBodyClasses(getHost())).toStrictEqual({
                "pull-request-comment-outdated": false,
            });
        });
    });
});
