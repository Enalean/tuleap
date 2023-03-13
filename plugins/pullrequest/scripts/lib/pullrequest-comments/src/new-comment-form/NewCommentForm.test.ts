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

import { describe, it, expect, beforeEach, vi } from "vitest";
import { Fault } from "@tuleap/fault";
import { selectOrThrow } from "@tuleap/dom";
import type { HostElement } from "./NewCommentForm";
import { form_height_descriptor, getCancelButton, getSubmitButton } from "./NewCommentForm";
import {
    INLINE_COMMENT_POSITION_RIGHT,
    TYPE_INLINE_COMMENT,
} from "@tuleap/plugin-pullrequest-constants";
import { SaveNewCommentStub } from "../../tests/stubs/SaveNewCommentStub";
import type { CommentOnFile } from "@tuleap/plugin-pullrequest-rest-api-types";

describe("NewCommentForm", () => {
    let target: ShadowRoot;

    beforeEach(() => {
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
    });

    const renderSubmitButton = (host: HostElement): HTMLButtonElement => {
        const renderButton = getSubmitButton(host);
        renderButton(host, target);

        return selectOrThrow(target, "[data-test=submit-new-comment-button]", HTMLButtonElement);
    };

    const renderCancelButton = (host: HostElement): HTMLButtonElement => {
        const renderButton = getCancelButton(host);
        renderButton(host, target);

        return selectOrThrow(target, "[data-test=cancel-new-comment-button]", HTMLButtonElement);
    };

    describe("Submit button", () => {
        it.each([
            ["disabled when the comment is empty", "", false, true],
            ["disabled when the comment is being saved", "Please rebase", true, true],
            [
                "enabled when the comment is not empty and not being saved",
                "Please rebase",
                false,
                false,
            ],
        ])(
            "The submit button should be %s",
            (expectation, comment, is_saving_comment, is_disabled) => {
                const submit_button = renderSubmitButton({
                    comment,
                    is_saving_comment,
                } as HostElement);

                expect(submit_button.disabled).toBe(is_disabled);
            }
        );

        it(`When the submit button is clicked
            Then the comment is saved
            And post_submit_callback is triggered`, async () => {
            const new_inline_comment_payload: CommentOnFile = {
                id: 13,
                content: "Please don't",
                user: {
                    id: 102,
                    avatar_url: "avatar.png",
                    display_name: "John Doe",
                    user_url: "user-url",
                },
                post_date: "right now",
                unidiff_offset: 55,
                file_path: "README.md",
                position: INLINE_COMMENT_POSITION_RIGHT,
                parent_id: 0,
                color: "",
                is_outdated: false,
                type: TYPE_INLINE_COMMENT,
            };

            const comment_saver = SaveNewCommentStub.withResponsePayload(
                new_inline_comment_payload
            );

            const host = {
                comment: "Please remove this line",
                post_submit_callback: vi.fn(),
                comment_saver,
            } as unknown as HostElement;

            await renderSubmitButton(host).click();

            expect(comment_saver.getLastCallParams()).toBe("Please remove this line");
            expect(host.post_submit_callback).toHaveBeenCalledWith(new_inline_comment_payload);
        });

        it("When the comment is being saved, then the submit button should display a spinner", () => {
            const submit_button = renderSubmitButton({
                comment: "YOLO does it",
                is_saving_comment: true,
            } as HostElement);

            expect(
                selectOrThrow(submit_button, "[data-test=comment-being-saved-spinner]")
            ).toBeDefined();
        });

        it("When the comment is being written, then the submit button should NOT display a spinner", () => {
            const submit_button = renderSubmitButton({
                comment: "The comment being written",
                is_saving_comment: false,
            } as HostElement);

            expect(
                submit_button.querySelector("[data-test=comment-being-saved-spinner]")
            ).toBeNull();
        });
    });

    describe("Cancel button", () => {
        it(`When the cancel button is clicked
            Then it should trigger the on_cancel_callback`, () => {
            const host = {
                on_cancel_callback: vi.fn(),
                config: {
                    is_cancel_allowed: true,
                },
            } as unknown as HostElement;

            const renderButton = getCancelButton(host);
            renderButton(host, target);

            renderCancelButton(host).click();

            expect(host.on_cancel_callback).toHaveBeenCalledTimes(1);
        });

        it(`When the comment is being saved
            Then it should disabled the cancel button`, () => {
            expect(
                renderCancelButton({
                    is_saving_comment: true,
                    config: {
                        is_cancel_allowed: true,
                    },
                } as unknown as HostElement).disabled
            ).toBe(true);
        });
    });

    it("should execute the post_rendering_callback each time the component height changes", () => {
        const host = { post_rendering_callback: vi.fn() } as unknown as HostElement;

        form_height_descriptor.observe(host);

        expect(host.post_rendering_callback).toHaveBeenCalledTimes(1);
    });

    it("should trigger the error_callback when it is defined and an error occurred while saving a new comment", async () => {
        const tuleap_api_fault = Fault.fromMessage("You cannot");
        const host = {
            error_callback: vi.fn(),
            comment: "Please rebase",
            is_saving_comment: false,
            comment_saver: SaveNewCommentStub.withFault(tuleap_api_fault),
        } as unknown as HostElement;

        await renderSubmitButton(host).click();

        expect(host.error_callback).toHaveBeenCalledWith(tuleap_api_fault);
    });
});
