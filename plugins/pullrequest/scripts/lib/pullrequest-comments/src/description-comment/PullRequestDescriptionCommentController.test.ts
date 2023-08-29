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
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import { PullRequestDescriptionCommentController } from "./PullRequestDescriptionCommentController";
import type { ControlPullRequestDescriptionComment } from "./PullRequestDescriptionCommentController";
import type { PullRequestDescriptionComment } from "./PullRequestDescriptionComment";
import type { PullRequestDescriptionCommentPresenter } from "./PullRequestDescriptionCommentPresenter";
import { CurrentPullRequestUserPresenterStub } from "../../tests/stubs/CurrentPullRequestUserPresenterStub";
import { PullRequestDescriptionCommentFormPresenter } from "./PullRequestDescriptionCommentFormPresenter";
import type { PullRequestCommentErrorCallback } from "../types";
import { SaveDescriptionCommentStub } from "../../tests/stubs/SaveDescriptionCommentStub";
import type { SaveDescriptionComment } from "./PullRequestDescriptionCommentSaver";
import { WritingZoneController } from "../writing-zone/WritingZoneController";

const project_id = 105;
const is_comments_markdown_mode_enabled = true;

describe("PullRequestDescriptionCommentController", () => {
    let onErrorCallback: PullRequestCommentErrorCallback;

    beforeEach(() => {
        onErrorCallback = vi.fn();
    });

    const getController = (
        description_saver: SaveDescriptionComment
    ): ControlPullRequestDescriptionComment =>
        PullRequestDescriptionCommentController(
            description_saver,
            CurrentPullRequestUserPresenterStub.withDefault(),
            onErrorCallback
        );

    it("showEditionForm() should assign a DescriptionCommentFormPresenter to the given host", () => {
        const doc = document.implementation.createHTMLDocument();
        const content = doc.createElement("div");
        const writing_zone = doc.createElement("div");
        const writing_zone_controller = WritingZoneController({
            document: doc,
            focus_writing_zone_when_connected: true,
            is_comments_markdown_mode_enabled,
            project_id,
        });
        const setWritingZoneContent = vi.spyOn(writing_zone_controller, "setWritingZoneContent");
        const host = {
            edition_form_presenter: null,
            description: {
                raw_content: "This commit fixes bug #123",
            },
            writing_zone_controller,
            writing_zone,
            content: () => content,
        } as unknown as PullRequestDescriptionComment;

        getController(SaveDescriptionCommentStub.withDefault()).showEditionForm(host);

        expect(host.edition_form_presenter).toStrictEqual(
            PullRequestDescriptionCommentFormPresenter.fromCurrentDescription(host.description)
        );
        expect(setWritingZoneContent).toHaveBeenCalledWith(
            writing_zone,
            host.description.raw_content
        );
    });

    it("hideEditionForm() should replace the current DescriptionCommentFormPresenter with null", () => {
        const host = {
            edition_form_presenter:
                PullRequestDescriptionCommentFormPresenter.fromCurrentDescription({
                    raw_content: "This commit fixes bug #123",
                } as PullRequestDescriptionCommentPresenter),
            post_description_form_close_callback: vi.fn(),
        } as unknown as PullRequestDescriptionComment;

        getController(SaveDescriptionCommentStub.withDefault()).hideEditionForm(host);

        expect(host.edition_form_presenter).toBeNull();
        expect(host.post_description_form_close_callback).toHaveBeenCalledOnce();
    });

    describe("updateCurrentlyEditedDescription()", () => {
        it(`should update the host's form presenter with the currently typed description`, () => {
            const host = {
                edition_form_presenter:
                    PullRequestDescriptionCommentFormPresenter.fromCurrentDescription({
                        raw_content: "This commit fixes bug #",
                    } as PullRequestDescriptionCommentPresenter),
            } as PullRequestDescriptionComment;

            const new_description = "This commit fixes bug #123";
            getController(SaveDescriptionCommentStub.withDefault()).handleWritingZoneContentChange(
                host,
                new_description
            );

            expect(host.edition_form_presenter?.description_content).toStrictEqual(new_description);
        });
    });

    describe("saveDescriptionComment()", () => {
        it(`should save the new description, update the description presenter and display back the read mode`, async () => {
            const edition_form_presenter =
                PullRequestDescriptionCommentFormPresenter.fromCurrentDescription({
                    raw_content: "This commit fixes bug #456",
                } as PullRequestDescriptionCommentPresenter);
            const updated_pullrequest = {
                id: 15,
                description: 'This commit fixes <a class="cross-reference">bug #456</a>',
                raw_description: "This commit fixes bug #456",
            } as PullRequest;

            const host = {
                edition_form_presenter,
                post_description_form_close_callback: vi.fn(),
            } as unknown as PullRequestDescriptionComment;

            const description_saver =
                SaveDescriptionCommentStub.withResponsePayload(updated_pullrequest);
            await getController(description_saver).saveDescriptionComment(host);

            expect(description_saver.getLastCallParams()).toStrictEqual(
                PullRequestDescriptionCommentFormPresenter.buildSubmitted(edition_form_presenter)
            );
            expect(host.edition_form_presenter).toBeNull();
            expect(host.description.raw_content).toStrictEqual(updated_pullrequest.raw_description);
            expect(host.description.content).toStrictEqual(updated_pullrequest.description);
            expect(host.post_description_form_close_callback).toHaveBeenCalledOnce();
        });

        it(`should trigger the onErrorCallback when an error occurres while saving the description`, async () => {
            const edition_form_presenter =
                PullRequestDescriptionCommentFormPresenter.fromCurrentDescription({
                    raw_content: "This commit fixes bug #456",
                } as PullRequestDescriptionCommentPresenter);

            const host = {
                edition_form_presenter,
            } as PullRequestDescriptionComment;

            const tuleap_api_fault = Fault.fromMessage("Forbidden");
            const description_saver = SaveDescriptionCommentStub.withFault(tuleap_api_fault);
            await getController(description_saver).saveDescriptionComment(host);

            expect(onErrorCallback).toHaveBeenCalledOnce();
            expect(onErrorCallback).toHaveBeenCalledWith(tuleap_api_fault);
            expect(host.edition_form_presenter).toStrictEqual(
                PullRequestDescriptionCommentFormPresenter.buildNotSubmitted(edition_form_presenter)
            );
        });
    });
});
