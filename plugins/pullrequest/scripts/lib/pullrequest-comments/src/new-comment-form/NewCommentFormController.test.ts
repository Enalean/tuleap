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

import { describe, it, expect, beforeEach, vi } from "vitest";
import { Fault } from "@tuleap/fault";
import type { PullRequestComment } from "@tuleap/plugin-pullrequest-rest-api-types";
import { TYPE_GLOBAL_COMMENT } from "@tuleap/plugin-pullrequest-constants";
import type { PullRequestCommentErrorCallback } from "../types";
import { SaveCommentStub } from "../../tests/stubs/SaveCommentStub";
import { NewCommentFormComponentConfigStub } from "../../tests/stubs/NewCommentFormComponentConfigStub";
import type { ControlWritingZone } from "../writing-zone/WritingZoneController";
import { NewCommentFormPresenter } from "./NewCommentFormPresenter";
import { NewCommentFormController } from "./NewCommentFormController";
import type { SaveComment, CommentContext } from "./types";
import type { NewCommentForm } from "./NewCommentForm";
import type {
    ControlNewCommentForm,
    NewCommentCancelCallback,
    NewCommentFormComponentConfig,
    NewCommentPostSubmitCallback,
} from "./NewCommentFormController";

const author = { avatar_url: "url/to/user_avatar.png" };

describe("NewCommentFormController", () => {
    let config: NewCommentFormComponentConfig,
        on_error_callback: PullRequestCommentErrorCallback,
        on_cancel_callback: NewCommentCancelCallback,
        post_submit_callback: NewCommentPostSubmitCallback,
        host_content: HTMLElement;

    const getController = (
        new_comment_saver: SaveComment = SaveCommentStub.withDefault(),
    ): ControlNewCommentForm =>
        NewCommentFormController(
            new_comment_saver,
            author,
            config,
            {} as CommentContext,
            post_submit_callback,
            on_error_callback,
            on_cancel_callback,
        );

    const getEmptyPresenter = (): NewCommentFormPresenter =>
        NewCommentFormPresenter.buildFromAuthor(author, config);

    beforeEach(() => {
        config = NewCommentFormComponentConfigStub.withCancelActionAllowed();
        on_error_callback = vi.fn();
        on_cancel_callback = vi.fn();
        post_submit_callback = vi.fn();
        host_content = document.implementation.createHTMLDocument().createElement("div");
    });

    describe("buildInitialPresenter()", () => {
        it.each([
            [true, NewCommentFormComponentConfigStub.withCancelActionAllowed(), true],
            [false, NewCommentFormComponentConfigStub.withCancelActionDisallowed(), false],
        ])(
            `Given that the config has is_cancel_allowed being %s, Then presenter.is_cancel_allowed should be %s`,
            (config_value, given_config, expected_value) => {
                config = given_config;
                const host = {
                    content: () => host_content,
                } as unknown as NewCommentForm;

                vi.useFakeTimers();
                getController().buildInitialPresenter(host);
                vi.advanceTimersToNextTimer();

                expect(host.presenter.is_cancel_allowed).toStrictEqual(expected_value);
            },
        );
    });

    describe("cancelNewComment()", () => {
        it("Should assign the host a new empty presenter, and call the on_cancel_callback", () => {
            const host = {} as NewCommentForm;

            getController().cancelNewComment(host);

            expect(host.presenter).toStrictEqual(getEmptyPresenter());
            expect(on_cancel_callback).toHaveBeenCalledOnce();
        });
    });

    describe("updateNewComment()", () => {
        it("Should assign the host a new empty presenter, and call the on_cancel_callback", () => {
            const host = { presenter: getEmptyPresenter() } as NewCommentForm;
            const new_comment = "This is a new comment";

            getController().handleWritingZoneContentChange(host, new_comment);

            expect(host.presenter).toStrictEqual(
                NewCommentFormPresenter.updateContent(getEmptyPresenter(), new_comment),
            );
        });
    });

    describe("saveNewComment()", () => {
        it(`When the comment is saved successfully
            Then it should call the post_submit_callback with the PullRequestComment
            And reset the WritingZone + the presenter`, async () => {
            const comment_content = "This is what I have to say";
            const host = {
                presenter: NewCommentFormPresenter.updateContent(
                    getEmptyPresenter(),
                    comment_content,
                ),
                content: () => host_content,
                writing_zone_controller: {
                    resetWritingZone: vi.fn(),
                } as unknown as ControlWritingZone,
            } as NewCommentForm;

            const comment_saver = SaveCommentStub.withResponsePayload({
                id: 15,
                type: TYPE_GLOBAL_COMMENT,
                content: comment_content,
            } as PullRequestComment);

            await getController(comment_saver).saveNewComment(host);

            expect(post_submit_callback).toHaveBeenCalledOnce();
            expect(host.writing_zone_controller.resetWritingZone).toHaveBeenCalledOnce();
            expect(host.presenter).toStrictEqual(getEmptyPresenter());
        });

        it(`When the comment is saved with error
            Then it should call the on_error_callback with the Fault`, async () => {
            const presenter = NewCommentFormPresenter.updateContent(
                getEmptyPresenter(),
                "This is a comment",
            );
            const host = {
                presenter,
            } as NewCommentForm;
            const tuleap_api_fault = Fault.fromMessage("Forbidden");

            await getController(SaveCommentStub.withFault(tuleap_api_fault)).saveNewComment(host);

            expect(on_error_callback).toHaveBeenCalledOnce();
            expect(on_error_callback).toHaveBeenCalledWith(tuleap_api_fault);
            expect(host.presenter).toStrictEqual(
                NewCommentFormPresenter.buildNotSubmitted(presenter),
            );
        });
    });

    it("getProjectId() should return the project id provided in the configuration", () => {
        expect(getController().getProjectId()).toBe(105);
    });
});
