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

import { describe, it, beforeEach, expect } from "vitest";
import type { HostElement } from "./NewCommentForm";
import { NewCommentFormPresenter } from "./NewCommentFormPresenter";
import {
    getNewCommentFormContent,
    getCancelButton,
    getSubmitButton,
} from "./NewCommentFormTemplate";
import { GettextProviderStub } from "../../tests/stubs/GettextProviderStub";
import { selectOrThrow } from "@tuleap/dom";
import { ControlNewCommentFormStub } from "../../tests/stubs/ControlNewCommentFormStub";
import { NewCommentFormComponentConfigStub } from "../../tests/stubs/NewCommentFormComponentConfigStub";
import { WritingZoneController } from "../writing-zone/WritingZoneController";
import {
    getWritingZoneElement,
    isWritingZoneElement,
    TAG as WRITING_ZONE_TAG_NAME,
} from "../writing-zone/WritingZone";

const project_id = 105;
const is_comments_markdown_mode_enabled = true;

describe("NewCommentFormTemplate", () => {
    let target: ShadowRoot;

    beforeEach(() => {
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
    });

    const getPresenter = (
        config = NewCommentFormComponentConfigStub.withCancelActionAllowed()
    ): NewCommentFormPresenter =>
        NewCommentFormPresenter.buildWithUpdatedComment(
            NewCommentFormPresenter.buildFromAuthor(
                { avatar_url: "url/to/user_avatar.png" },
                config
            ),
            "This is a new comment"
        );

    it("When some content has been updated in the writing zone, then the controller should update the template", () => {
        const host = {
            presenter: getPresenter(),
            controller: ControlNewCommentFormStub(),
            writing_zone_controller: WritingZoneController({
                document: document.implementation.createHTMLDocument(),
                focus_writing_zone_when_connected: true,
                is_comments_markdown_mode_enabled,
                project_id,
            }),
        } as HostElement;

        const host_with_writing_zone = {
            ...host,
            writing_zone: getWritingZoneElement(host),
        };

        const render = getNewCommentFormContent(host_with_writing_zone, GettextProviderStub);
        render(host_with_writing_zone, target);

        const writing_zone = target.querySelector(WRITING_ZONE_TAG_NAME);
        if (!writing_zone || !isWritingZoneElement(writing_zone)) {
            throw new Error("Can't find the WritingZone element in the DOM.");
        }

        writing_zone.dispatchEvent(
            new CustomEvent("writing-zone-input", { detail: { content: "Some comment" } })
        );

        expect(host.controller.handleWritingZoneContentChange).toHaveBeenCalledOnce();
    });

    describe("Cancel button", () => {
        it("When the Cancel action is not allowed, then it should not be rendered", () => {
            const host = {
                presenter: getPresenter(
                    NewCommentFormComponentConfigStub.withCancelActionDisallowed()
                ),
            } as HostElement;

            const render = getCancelButton(host, GettextProviderStub);
            render(host, target);

            expect(target.querySelector("[data-test=cancel-new-comment-button]")).toBeNull();
        });

        it("When the Cancel button is clicked, Then the controller should cancel the new comment", () => {
            const host = {
                presenter: getPresenter(),
                controller: ControlNewCommentFormStub(),
            } as HostElement;

            const render = getCancelButton(host, GettextProviderStub);
            render(host, target);

            selectOrThrow(target, "[data-test=cancel-new-comment-button]").click();

            expect(host.controller.cancelNewComment).toHaveBeenCalledOnce();
        });

        it("When the comment is being saved, Then the cancel button should be disabled", () => {
            const host = {
                presenter: NewCommentFormPresenter.buildSavingComment(getPresenter()),
            } as HostElement;

            const render = getCancelButton(host, GettextProviderStub);
            render(host, target);

            expect(
                selectOrThrow(target, "[data-test=cancel-new-comment-button]").getAttribute(
                    "disabled"
                )
            ).toBeDefined();
        });
    });

    describe("Submit button", () => {
        it("Should be disabled and have a spinner when the comment is being saved", () => {
            const host = {
                presenter: NewCommentFormPresenter.buildSavingComment(getPresenter()),
            } as HostElement;

            const render = getSubmitButton(host, GettextProviderStub);
            render(host, target);

            expect(
                selectOrThrow(target, "[data-test=submit-new-comment-button]").getAttribute(
                    "disabled"
                )
            ).toBeDefined();
            expect(selectOrThrow(target, "[data-test=comment-being-saved-spinner]")).toBeDefined();
        });

        it("Should be disabled when the comment empty", () => {
            const host = {
                presenter: NewCommentFormPresenter.buildWithUpdatedComment(getPresenter(), ""),
            } as HostElement;

            const render = getSubmitButton(host, GettextProviderStub);
            render(host, target);

            expect(
                selectOrThrow(target, "[data-test=submit-new-comment-button]").getAttribute(
                    "disabled"
                )
            ).toBeDefined();
        });

        it("When the Submit button is clicked, Then the controller should save the new comment", () => {
            const host = {
                presenter: getPresenter(),
                controller: ControlNewCommentFormStub(),
            } as HostElement;

            const render = getSubmitButton(host, GettextProviderStub);
            render(host, target);

            selectOrThrow(target, "[data-test=submit-new-comment-button]").click();

            expect(host.controller.saveNewComment).toHaveBeenCalledOnce();
        });
    });
});
