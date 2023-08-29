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
import type { PullRequestDescriptionCommentPresenter } from "./PullRequestDescriptionCommentPresenter";
import { PullRequestDescriptionCommentFormPresenter } from "./PullRequestDescriptionCommentFormPresenter";
import { GettextProviderStub } from "../../tests/stubs/GettextProviderStub";
import { getDescriptionCommentFormTemplate } from "./PullRequestDescriptionCommentFormTemplate";
import { ControlPullRequestDescriptionCommentStub } from "../../tests/stubs/ControlPullRequestDescriptionCommentStub";
import { WritingZoneController } from "../writing-zone/WritingZoneController";
import {
    getWritingZoneElement,
    isWritingZoneElement,
    TAG as WRITING_ZONE_TAG_NAME,
} from "../writing-zone/WritingZone";

const project_id = 105;
const is_comments_markdown_mode_enabled = true;

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
            controller: ControlPullRequestDescriptionCommentStub,
        } as HostElement;
    });

    it("When the user clicks [Cancel], Then the controller should be asked to hide the reply form", () => {
        const render = getDescriptionCommentFormTemplate(host, GettextProviderStub);
        render(host, target);

        selectOrThrow(target, "[data-test=button-cancel-edition]").click();

        expect(host.controller.hideEditionForm).toHaveBeenCalledOnce();
        expect(host.controller.hideEditionForm).toHaveBeenCalledWith(host);
    });

    it("When the user clicks [Save], Then the controller should be asked to save the description", () => {
        const render = getDescriptionCommentFormTemplate(host, GettextProviderStub);
        render(host, target);

        selectOrThrow(target, "[data-test=button-save-edition]").click();

        expect(host.controller.saveDescriptionComment).toHaveBeenCalledOnce();
        expect(host.controller.saveDescriptionComment).toHaveBeenCalledWith(host);
    });

    it("When some content has been updated in the writing zone, then the controller should update the template", () => {
        const base_host = {
            ...host,
            writing_zone_controller: WritingZoneController({
                document: document.implementation.createHTMLDocument(),
                focus_writing_zone_when_connected: true,
                is_comments_markdown_mode_enabled,
                project_id,
            }),
        } as unknown as HostElement;

        const host_with_writing_zone = {
            ...base_host,
            writing_zone: getWritingZoneElement(base_host),
        };

        const render = getDescriptionCommentFormTemplate(
            host_with_writing_zone,
            GettextProviderStub
        );
        render(host_with_writing_zone, target);

        const writing_zone = target.querySelector(WRITING_ZONE_TAG_NAME);
        if (!writing_zone || !isWritingZoneElement(writing_zone)) {
            throw new Error("Can't find the WritingZone element in the DOM.");
        }

        writing_zone.dispatchEvent(
            new CustomEvent("writing-zone-input", { detail: { content: "Some comment" } })
        );

        expect(
            host_with_writing_zone.controller.handleWritingZoneContentChange
        ).toHaveBeenCalledOnce();
    });
});
