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
import { PullRequestCommentPresenterStub } from "../../tests/stubs/PullRequestCommentPresenterStub";
import type { HostElement } from "./PullRequestComment";
import { getReplyFormTemplate } from "./PullRequestCommentReplyFormTemplate";
import { selectOrThrow } from "@tuleap/dom";
import { PullRequestCommentControllerStub } from "../../tests/stubs/PullRequestCommentControllerStub";
import { CurrentPullRequestUserPresenterStub } from "../../tests/stubs/CurrentPullRequestUserPresenterStub";
import { ReplyCommentFormPresenterStub } from "../../tests/stubs/ReplyCommentFormPresenterStub";
import { GettextProviderStub } from "../../tests/stubs/GettextProviderStub";
import {
    getWritingZoneElement,
    isWritingZoneElement,
    TAG as WRITING_ZONE_TAG_NAME,
} from "../writing-zone/WritingZone";
import { WritingZoneController } from "../writing-zone/WritingZoneController";

describe("PullRequestCommentReplyFormTemplate", () => {
    let target: ShadowRoot;

    beforeEach(() => {
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
    });

    it(`Should render the comment reply form when it is toggled`, () => {
        const host = {
            comment: PullRequestCommentPresenterStub.buildPullRequestEventComment(),
            currentUser: CurrentPullRequestUserPresenterStub.withDefault(),
            reply_comment_presenter: ReplyCommentFormPresenterStub.buildEmpty(),
            controller: PullRequestCommentControllerStub(),
        } as unknown as HostElement;
        const render = getReplyFormTemplate(host, GettextProviderStub);
        render(host, target);

        expect(target.querySelector("[data-test=pull-request-comment-reply-form]")).not.toBeNull();
    });

    it(`Should NOT render the comment reply form when it is NOT toggled`, () => {
        const host = {
            comment: PullRequestCommentPresenterStub.buildPullRequestEventComment(),
            currentUser: CurrentPullRequestUserPresenterStub.withDefault(),
            reply_comment_presenter: null,
        } as unknown as HostElement;
        const render = getReplyFormTemplate(host, GettextProviderStub);
        render(host, target);

        expect(target.querySelector("[data-test=pull-request-comment-reply-form]")).toBeNull();
    });

    it("Should hide the form when the [Cancel] button is clicked", () => {
        const controller = PullRequestCommentControllerStub();
        const host = {
            comment: PullRequestCommentPresenterStub.buildGlobalComment(),
            currentUser: CurrentPullRequestUserPresenterStub.withDefault(),
            reply_comment_presenter: ReplyCommentFormPresenterStub.buildEmpty(),
            controller,
        } as unknown as HostElement;

        const render = getReplyFormTemplate(host, GettextProviderStub);
        render(host, target);

        selectOrThrow(target, "[data-test=button-cancel-reply]").click();

        expect(controller.hideReplyForm).toHaveBeenCalledTimes(1);
    });

    it("Should disable the buttons and add a spinner on the [Reply] one when the comment has been submitted", () => {
        const controller = PullRequestCommentControllerStub();
        const host = {
            comment: PullRequestCommentPresenterStub.buildGlobalComment(),
            currentUser: CurrentPullRequestUserPresenterStub.withDefault(),
            reply_comment_presenter:
                ReplyCommentFormPresenterStub.buildBeingSubmitted("Some comment"),
            controller,
        } as unknown as HostElement;

        const render = getReplyFormTemplate(host, GettextProviderStub);
        render(host, target);

        const reply_button = selectOrThrow(target, "[data-test=button-save-reply]");
        const spinner = target.querySelector("[data-test=reply-being-saved-spinner]");

        expect(reply_button.hasAttribute("disabled")).toBe(true);
        expect(spinner).not.toBeNull();
        expect(
            selectOrThrow(target, "[data-test=button-cancel-reply]").hasAttribute("disabled"),
        ).toBe(true);
    });

    it("Should disable the [Reply] button when the comment is not submittable yet (empty comment)", () => {
        const controller = PullRequestCommentControllerStub();
        const host = {
            comment: PullRequestCommentPresenterStub.buildGlobalComment(),
            currentUser: CurrentPullRequestUserPresenterStub.withDefault(),
            reply_comment_presenter: ReplyCommentFormPresenterStub.buildEmpty(),
            controller,
        } as unknown as HostElement;

        const render = getReplyFormTemplate(host, GettextProviderStub);
        render(host, target);

        expect(
            selectOrThrow(target, "[data-test=button-save-reply]").hasAttribute("disabled"),
        ).toBe(true);
    });

    it("Should update the new comment presenter when user types something in the textArea", () => {
        const controller = PullRequestCommentControllerStub();
        const host = {
            comment: PullRequestCommentPresenterStub.buildGlobalComment(),
            currentUser: CurrentPullRequestUserPresenterStub.withDefault(),
            reply_comment_presenter: ReplyCommentFormPresenterStub.buildEmpty(),
            controller,
            writing_zone_controller: WritingZoneController({
                document: document.implementation.createHTMLDocument(),
                focus_writing_zone_when_connected: true,
                is_comments_markdown_mode_enabled: true,
                project_id: 105,
            }),
        } as unknown as HostElement;

        const host_with_writing_zone = {
            ...host,
            writing_zone: getWritingZoneElement(host),
        };
        const render = getReplyFormTemplate(host_with_writing_zone, GettextProviderStub);
        render(host_with_writing_zone, target);

        const writing_zone = target.querySelector(WRITING_ZONE_TAG_NAME);
        if (!writing_zone || !isWritingZoneElement(writing_zone)) {
            throw new Error("Can't find the WritingZone element in the DOM.");
        }

        writing_zone.dispatchEvent(
            new CustomEvent("writing-zone-input", { detail: { content: "Some comment" } }),
        );

        expect(controller.handleWritingZoneContentChange).toHaveBeenCalledOnce();
    });

    it("Should save the new comment presenter when user clicks on [Reply]", () => {
        const controller = PullRequestCommentControllerStub();
        const host = {
            comment: PullRequestCommentPresenterStub.buildGlobalComment(),
            currentUser: CurrentPullRequestUserPresenterStub.withDefault(),
            reply_comment_presenter: ReplyCommentFormPresenterStub.buildWithContent("Some content"),
            controller,
        } as unknown as HostElement;

        const render = getReplyFormTemplate(host, GettextProviderStub);
        render(host, target);

        selectOrThrow(target, "[data-test=button-save-reply]").click();

        expect(controller.saveReply).toHaveBeenCalledOnce();
    });
});
