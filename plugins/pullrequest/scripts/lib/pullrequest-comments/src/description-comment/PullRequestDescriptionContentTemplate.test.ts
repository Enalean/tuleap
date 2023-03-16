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
import { selectOrThrow } from "@tuleap/dom";
import type { HostElement } from "./PullRequestDescriptionComment";
import { getDescriptionContentTemplate } from "./PullRequestDescriptionContentTemplate";
import { GettextProviderStub } from "../../tests/stubs/GettextProviderStub";
import { DescriptionAuthorStub } from "../../tests/stubs/DescriptionAuthorStub";
import { ControlPullRequestDescriptionCommentStub } from "../../tests/stubs/ControlPullRequestDescriptionCommentStub";

describe("PullRequestDescriptionContentTemplate", () => {
    let target: ShadowRoot, base_host: HostElement;

    beforeEach(() => {
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;

        base_host = {
            edition_form_presenter: null,
            controller: ControlPullRequestDescriptionCommentStub,
        } as HostElement;
    });

    it("Given that the description comment is NOT empty, Then it should return it", () => {
        const host = {
            ...base_host,
            description: {
                author: DescriptionAuthorStub.withDefault(),
                post_date: "2023-03-15T11:20:00Z",
                content: "This commit fixes an old bug.",
            },
        } as HostElement;

        const render = getDescriptionContentTemplate(host, GettextProviderStub);
        render(host, target);

        expect(selectOrThrow(target, "[data-test=description-content]").textContent?.trim()).toBe(
            "This commit fixes an old bug."
        );
    });

    it.each([
        ["IS NOT allowed to update the description", "a generic empty state", false],
        [
            "IS allowed to update the description",
            "an empty state asking him to provide a description",
            true,
        ],
    ])(
        `Given that the description comment is empty,
        When the current user is %s
        Then it should return %s`,
        (when, what, can_user_update_description) => {
            const host = {
                ...base_host,
                description: {
                    author: DescriptionAuthorStub.withDefault(),
                    post_date: "2023-03-15T11:20:00Z",
                    content: "",
                    can_user_update_description,
                },
            } as HostElement;

            const render = getDescriptionContentTemplate(host, GettextProviderStub);
            render(host, target);

            const empty_state_text = selectOrThrow(
                target,
                "[data-test=description-empty-state]"
            ).textContent;
            expect(empty_state_text).toContain("No commit description has been provided yet.");
            expect(empty_state_text?.includes("Please add one.")).toBe(can_user_update_description);
        }
    );

    it("When the user clicks on [Edit], then the controller should be asked to show the edition form", () => {
        const host = {
            ...base_host,
            description: {
                author: DescriptionAuthorStub.withDefault(),
                post_date: "2023-03-15T11:20:00Z",
                content: "",
                raw_content: "",
            },
        } as HostElement;

        const render = getDescriptionContentTemplate(host, GettextProviderStub);
        render(host, target);

        selectOrThrow(target, "[data-test=button-edit-description-comment]").click();

        expect(host.controller.showEditionForm).toHaveBeenCalledOnce();
        expect(host.controller.showEditionForm).toHaveBeenCalledWith(host);
    });
});
