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

import { describe, it, beforeEach, expect, vi } from "vitest";
import { selectOrThrow } from "@tuleap/dom";
import type { HostElement } from "./PullRequestDescriptionComment";
import type { PullRequestDescriptionCommentPresenter } from "./PullRequestDescriptionCommentPresenter";
import type { ControlPullRequestDescriptionComment } from "./PullRequestDescriptionCommentController";
import { PullRequestDescriptionCommentFormPresenter } from "./PullRequestDescriptionCommentFormPresenter";
import { GettextProviderStub } from "../../tests/stubs/GettextProviderStub";
import { getDescriptionCommentFormTemplate } from "./PullRequestDescriptionCommentFormTemplate";

describe("PullRequestDescriptionCommentFormTemplate", () => {
    let target: ShadowRoot, host: HostElement;

    beforeEach(() => {
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;

        host = {
            edition_form_presenter:
                PullRequestDescriptionCommentFormPresenter.fromCurrentDescription({
                    raw_content: "This is a description",
                } as PullRequestDescriptionCommentPresenter),
            controller: {
                showEditionForm: vi.fn(),
                hideEditionForm: vi.fn(),
                getRelativeDateHelper: vi.fn(),
            } as ControlPullRequestDescriptionComment,
        } as HostElement;
    });

    it("should put the current description in the textarea", () => {
        const render = getDescriptionCommentFormTemplate(host, GettextProviderStub);
        render(host, target);

        expect(
            selectOrThrow(
                target,
                "[data-test=pull-request-description-comment-form-textarea]",
                HTMLTextAreaElement
            ).value
        ).toBe("This is a description");
    });

    it("When the user clicks [Cancel], Then the controller should be asked to hide the reply form", () => {
        const render = getDescriptionCommentFormTemplate(host, GettextProviderStub);
        render(host, target);

        selectOrThrow(target, "[data-test=button-cancel-edition]").click();

        expect(host.controller.hideEditionForm).toHaveBeenCalledOnce();
        expect(host.controller.hideEditionForm).toHaveBeenCalledWith(host);
    });
});
