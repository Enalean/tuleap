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
import { selectOrThrow } from "@tuleap/dom";
import type { HostElement } from "./PullRequestDescriptionComment";
import type { PullRequestDescriptionCommentPresenter } from "./PullRequestDescriptionCommentPresenter";
import { PullRequestDescriptionCommentFormPresenter } from "./PullRequestDescriptionCommentFormPresenter";
import { GettextProviderStub } from "../../tests/stubs/GettextProviderStub";
import { getDescriptionCommentFormTemplate } from "./PullRequestDescriptionCommentFormTemplate";
import { ControlPullRequestDescriptionCommentStub } from "../../tests/stubs/ControlPullRequestDescriptionCommentStub";

vi.mock("@tuleap/mention", () => ({
    initMentions(): void {
        // Mock @tuleap/mention because it needs jquery in tests
    },
}));

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
            controller: ControlPullRequestDescriptionCommentStub(),
        } as HostElement;
    });

    it("When the user clicks [Cancel], Then the controller should be asked to hide the reply form", () => {
        const hideEditionForm = vi.spyOn(host.controller, "hideEditionForm");
        const render = getDescriptionCommentFormTemplate(host, GettextProviderStub);
        render(host, target);

        selectOrThrow(target, "[data-test=button-cancel-edition]").click();

        expect(hideEditionForm).toHaveBeenCalledOnce();
        expect(hideEditionForm).toHaveBeenCalledWith(host);
    });

    it("When the user clicks [Save], Then the controller should be asked to save the description", () => {
        const saveDescriptionComment = vi.spyOn(host.controller, "saveDescriptionComment");
        const render = getDescriptionCommentFormTemplate(host, GettextProviderStub);
        render(host, target);

        selectOrThrow(target, "[data-test=button-save-edition]").click();

        expect(saveDescriptionComment).toHaveBeenCalledOnce();
        expect(saveDescriptionComment).toHaveBeenCalledWith(host);
    });
});
