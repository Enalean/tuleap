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

import { describe, beforeEach, it, expect, vi } from "vitest";
import { Fault } from "@tuleap/fault";
import type { EditedComment } from "@tuleap/plugin-pullrequest-rest-api-types";
import { SaveEditedCommentStub } from "../../../tests/stubs/SaveEditedCommentStub";
import { PullRequestCommentPresenterStub } from "../../../tests/stubs/PullRequestCommentPresenterStub";
import { EditionFormPresenterStub } from "../../../tests/stubs/EditionFormPresenterStub";
import type { ControlWritingZone } from "../../writing-zone/WritingZoneController";
import type { ControlEditionForm } from "./EditionFormController";
import { EditionFormController } from "./EditionFormController";
import type { HostElement, InternalEditionForm } from "./EditionForm";
import { EditionFormPresenter } from "./EditionFormPresenter";

describe("EditionFormController", () => {
    let post_submit_callback: () => void,
        on_cancel_callback: () => void,
        on_error_callback: () => void;

    beforeEach(() => {
        post_submit_callback = vi.fn();
        on_cancel_callback = vi.fn();
        on_error_callback = vi.fn();
    });

    const getController = (
        save_edited_comment = SaveEditedCommentStub.withDefault(),
    ): ControlEditionForm =>
        EditionFormController(
            save_edited_comment,
            post_submit_callback,
            on_cancel_callback,
            on_error_callback,
        );

    it("shouldFocusWritingZoneOnceRendered() should return true", () => {
        expect(getController().shouldFocusWritingZoneOnceRendered()).toBe(true);
    });

    it("initEditionForm() should assign a presenter to the current host", () => {
        const setWritingZoneContent = vi.fn();
        const host = {
            comment: PullRequestCommentPresenterStub.buildGlobalComment(),
            writing_zone_controller: {
                setWritingZoneContent,
            } as unknown as ControlWritingZone,
        } as InternalEditionForm;

        getController().initEditionForm(host);

        expect(host.presenter).toStrictEqual(EditionFormPresenter.fromComment(host.comment));
        expect(setWritingZoneContent).toHaveBeenCalledOnce();
    });

    it("handleWritingZoneContentChange() should assign a new presenter to the current host with the currently typed content", () => {
        const host = {
            presenter: EditionFormPresenterStub.buildInitial("Please rebase"),
        } as InternalEditionForm;

        const new_content = "Please rebase onto the master branch";
        getController().handleWritingZoneContentChange(host, new_content);

        expect(host.presenter.edited_content).toBe(new_content);
    });

    it("cancelEdition() should call the on_cancel_callback", () => {
        getController().cancelEdition();

        expect(on_cancel_callback).toHaveBeenCalledOnce();
    });

    describe("saveEditedContent()", () => {
        let host: HostElement;

        beforeEach(() => {
            host = {
                presenter: EditionFormPresenterStub.buildInitial("Please rebase"),
                comment: PullRequestCommentPresenterStub.buildGlobalCommentWithData({
                    content: "**Please rebase**",
                    post_processed_content: `<em>Please rebase</em>`,
                    raw_content: "**Please rebase**",
                }),
            } as HostElement;
        });

        it("should assign a submitted presenter to the current host", () => {
            getController().saveEditedContent(host);

            expect(host.presenter.is_being_submitted).toBe(true);
        });

        it("should call the post_submit_callback with a presenter when the comment has been saved successfully", async () => {
            const updated_comment_payload = {
                last_edition_date: "2023-11-21T14:45:00Z",
                content: "Please rebase on top of the **master** branch",
                post_processed_content: `Please rebase on top of the <em>master</em> branch`,
                raw_content: "Please rebase on top of the **master** branch",
            } as EditedComment;

            await getController(
                SaveEditedCommentStub.withSuccessPayload(updated_comment_payload),
            ).saveEditedContent(host);

            expect(post_submit_callback).toHaveBeenCalledOnce();

            if (!vi.isMockFunction(post_submit_callback)) {
                throw new Error("Unable to get post_submit_callback arguments");
            }
            const presenter = post_submit_callback.mock.calls[0][0];

            expect(presenter.last_edition_date.unwrapOr(null)).toBe(
                updated_comment_payload.last_edition_date,
            );
            expect(presenter.content).toBe(updated_comment_payload.content);
            expect(presenter.post_processed_content).toBe(
                updated_comment_payload.post_processed_content,
            );
            expect(presenter.raw_content).toBe(updated_comment_payload.raw_content);
        });

        it("should call the on_error_callback when the comment has NOT been saved successfully", async () => {
            const tuleap_api_fault = Fault.fromMessage("Woops, try again!");
            await getController(
                SaveEditedCommentStub.withFault(tuleap_api_fault),
            ).saveEditedContent(host);

            expect(on_error_callback).toHaveBeenCalledOnce();
            expect(on_error_callback).toHaveBeenCalledWith(tuleap_api_fault);
        });
    });
});
