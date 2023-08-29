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

import { describe, beforeEach, it, expect } from "vitest";
import { selectOrThrow } from "@tuleap/dom";
import { getWritingZoneTemplate } from "./WritingZoneTemplate";
import { GettextProviderStub } from "../../tests/stubs/GettextProviderStub";
import type { HostElement } from "./WritingZone";
import { WritingZonePresenter } from "./WritingZonePresenter";
import type { ControlWritingZone } from "./WritingZoneController";
import { WritingZoneController } from "./WritingZoneController";

const project_id = 105;
const is_comments_markdown_mode_enabled = true;

describe("WritingZoneTemplate", () => {
    let controller: ControlWritingZone, textarea: HTMLTextAreaElement;

    beforeEach(() => {
        controller = WritingZoneController({
            document: document.implementation.createHTMLDocument(),
            focus_writing_zone_when_connected: false,
            project_id,
            is_comments_markdown_mode_enabled,
        });

        textarea = document.implementation.createHTMLDocument().createElement("textarea");
        textarea.setAttribute("data-test", "writing-zone-textarea");
    });

    const renderWritingZone = (host: HostElement): ShadowRoot => {
        const doc = document.implementation.createHTMLDocument();
        const target = doc.createElement("div") as unknown as ShadowRoot;

        const render = getWritingZoneTemplate(host, GettextProviderStub);

        render(host, target);

        return target;
    };

    it("should display tabs", () => {
        const writing_zone = renderWritingZone({
            controller,
            presenter: WritingZonePresenter.buildInitial(
                project_id,
                is_comments_markdown_mode_enabled
            ),
        } as HostElement);

        const writing_tab = selectOrThrow(writing_zone, "[data-test=writing-tab]");
        const preview_tab = selectOrThrow(writing_zone, "[data-test=preview-tab]");

        expect(writing_tab).toBeDefined();
        expect(preview_tab).toBeDefined();
    });

    it("when the WritingZone is in writing mode, then only the textarea is displayed", () => {
        const writing_zone = renderWritingZone({
            controller,
            presenter: WritingZonePresenter.buildWritingMode(
                WritingZonePresenter.buildInitial(project_id, is_comments_markdown_mode_enabled)
            ),
            textarea,
        } as HostElement);

        const textarea_element = selectOrThrow(writing_zone, "[data-test=writing-zone-textarea]");

        expect(textarea_element).toBeDefined();
        expect(writing_zone.querySelector("[data-test=writing-zone-preview]")).toBeNull();
    });

    it("when the WritingZone is in preview mode, then only the preview is displayed", () => {
        const writing_zone = renderWritingZone({
            controller,
            presenter: WritingZonePresenter.buildPreviewMode(
                WritingZonePresenter.buildInitial(project_id, is_comments_markdown_mode_enabled),
                "<p>Previewed content</p>"
            ),
            textarea,
        } as HostElement);

        const preview_element = selectOrThrow(writing_zone, "[data-test=writing-zone-preview]");

        expect(preview_element).toBeDefined();
        expect(preview_element.innerHTML).toBe("<p>Previewed content</p>");
        expect(writing_zone.querySelector("[data-test=writing-zone-textarea]")).toBeNull();
    });

    it("when the WritingZone is in preview mode with error, then the preview should display an error", () => {
        const writing_zone = renderWritingZone({
            controller,
            presenter: WritingZonePresenter.buildPreviewWithError(
                WritingZonePresenter.buildInitial(project_id, is_comments_markdown_mode_enabled)
            ),
            textarea,
        } as HostElement);

        const preview_element = selectOrThrow(
            writing_zone,
            "[data-test=writing-zone-preview-error]"
        );

        expect(preview_element).toBeDefined();
    });
});
