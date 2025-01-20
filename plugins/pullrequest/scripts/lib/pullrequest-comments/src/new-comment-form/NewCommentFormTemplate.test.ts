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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { HostElement } from "./NewCommentForm";
import { NewCommentFormPresenter } from "./NewCommentFormPresenter";
import { getCancelButton, getSubmitButton } from "./NewCommentFormTemplate";
import { GettextProviderStub } from "../../tests/stubs/GettextProviderStub";
import { selectOrThrow } from "@tuleap/dom";
import { ControlNewCommentFormStub } from "../../tests/stubs/ControlNewCommentFormStub";
import { NewCommentFormComponentConfigStub } from "../../tests/stubs/NewCommentFormComponentConfigStub";

vi.mock("@tuleap/mention", () => ({
    initMentions(): void {
        // Mock @tuleap/mention because it needs jquery in tests
    },
}));

describe("NewCommentFormTemplate", () => {
    let target: ShadowRoot;

    beforeEach(() => {
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
    });

    const getPresenter = (
        config = NewCommentFormComponentConfigStub.withCancelActionAllowed(),
    ): NewCommentFormPresenter =>
        NewCommentFormPresenter.updateContent(
            NewCommentFormPresenter.buildFromAuthor(
                { avatar_url: "url/to/user_avatar.png" },
                config,
            ),
            "This is a new comment",
        );

    describe("Cancel button", () => {
        it("When the Cancel action is not allowed, then it should not be rendered", () => {
            const host = {
                presenter: getPresenter(
                    NewCommentFormComponentConfigStub.withCancelActionDisallowed(),
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

            const cancelNewComment = vi.spyOn(host.controller, "cancelNewComment");
            const render = getCancelButton(host, GettextProviderStub);
            render(host, target);

            selectOrThrow(target, "[data-test=cancel-new-comment-button]").click();

            expect(cancelNewComment).toHaveBeenCalledOnce();
        });

        it("When the comment is being saved, Then the cancel button should be disabled", () => {
            const host = {
                presenter: NewCommentFormPresenter.buildSubmitted(getPresenter()),
            } as HostElement;

            const render = getCancelButton(host, GettextProviderStub);
            render(host, target);

            expect(
                selectOrThrow(target, "[data-test=cancel-new-comment-button]").getAttribute(
                    "disabled",
                ),
            ).toBeDefined();
        });
    });

    describe("Submit button", () => {
        it("Should be disabled and have a spinner when the comment is being saved", () => {
            const host = {
                presenter: NewCommentFormPresenter.buildSubmitted(getPresenter()),
            } as HostElement;

            const render = getSubmitButton(host, GettextProviderStub);
            render(host, target);

            expect(
                selectOrThrow(target, "[data-test=submit-new-comment-button]").getAttribute(
                    "disabled",
                ),
            ).toBeDefined();
            expect(selectOrThrow(target, "[data-test=comment-being-saved-spinner]")).toBeDefined();
        });

        it("Should be disabled when the comment empty", () => {
            const host = {
                presenter: NewCommentFormPresenter.updateContent(getPresenter(), ""),
            } as HostElement;

            const render = getSubmitButton(host, GettextProviderStub);
            render(host, target);

            expect(
                selectOrThrow(target, "[data-test=submit-new-comment-button]").getAttribute(
                    "disabled",
                ),
            ).toBeDefined();
        });

        it("When the Submit button is clicked, Then the controller should save the new comment", () => {
            const host = {
                presenter: getPresenter(),
                controller: ControlNewCommentFormStub(),
            } as HostElement;

            const saveNewComment = vi.spyOn(host.controller, "saveNewComment");
            const render = getSubmitButton(host, GettextProviderStub);
            render(host, target);

            selectOrThrow(target, "[data-test=submit-new-comment-button]").click();

            expect(saveNewComment).toHaveBeenCalledOnce();
        });
    });
});
