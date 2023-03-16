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

import { describe, it, expect } from "vitest";
import { PullRequestDescriptionCommentController } from "./PullRequestDescriptionCommentController";
import type { ControlPullRequestDescriptionComment } from "./PullRequestDescriptionCommentController";
import type { PullRequestDescriptionComment } from "./PullRequestDescriptionComment";
import type { PullRequestDescriptionCommentPresenter } from "./PullRequestDescriptionCommentPresenter";
import { FocusTextareaStub } from "../../tests/stubs/FocusTextareaStub";
import { CurrentPullRequestUserPresenterStub } from "../../tests/stubs/CurrentPullRequestUserPresenterStub";
import { PullRequestDescriptionCommentFormPresenter } from "./PullRequestDescriptionCommentFormPresenter";

const getController = (): ControlPullRequestDescriptionComment =>
    PullRequestDescriptionCommentController(
        FocusTextareaStub(),
        CurrentPullRequestUserPresenterStub.withDefault()
    );

describe("PullRequestDescriptionCommentController", () => {
    it("showEditionForm() should assign a DescriptionCommentFormPresenter to the given host", () => {
        const content = document.implementation.createHTMLDocument().createElement("div");
        const host = {
            edition_form_presenter: null,
            description: {
                raw_content: "This commit fixes bug #123",
            },
            content: () => content,
        } as unknown as PullRequestDescriptionComment;

        getController().showEditionForm(host);

        expect(host.edition_form_presenter).toStrictEqual(
            PullRequestDescriptionCommentFormPresenter.fromCurrentDescription(host.description)
        );
    });

    it("hideEditionForm() should replace the current DescriptionCommentFormPresenter with null", () => {
        const host = {
            edition_form_presenter:
                PullRequestDescriptionCommentFormPresenter.fromCurrentDescription({
                    raw_content: "This commit fixes bug #123",
                } as PullRequestDescriptionCommentPresenter),
        } as unknown as PullRequestDescriptionComment;

        getController().hideEditionForm(host);

        expect(host.edition_form_presenter).toBeNull();
    });
});
