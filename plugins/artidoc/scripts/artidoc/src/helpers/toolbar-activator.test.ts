/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import type { MockInstance } from "vitest";
import { describe, it, expect, beforeEach, vi } from "vitest";
import { onClickActivateOrDeactivateToolbar } from "@/helpers/toolbar-activator";
import type { ToolbarBus } from "@tuleap/prose-mirror-editor";
import { buildToolbarBus } from "@tuleap/prose-mirror-editor";
import { getHeadingsButtonState } from "@/toolbar/HeadingsButtonState";

describe("toolbar-activator", () => {
    let doc: Document;
    let toolbar_element: HTMLElement;
    let toolbar_bus: ToolbarBus;

    let disableToolbar: MockInstance;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        toolbar_element = doc.createElement("div");
        toolbar_bus = buildToolbarBus();

        disableToolbar = vi.spyOn(toolbar_bus, "disableToolbar");

        doc.body.insertAdjacentHTML(
            "afterbegin",
            `
                <div class="editor">
                    <artidoc-section>
                        <artidoc-section-title>Lorem Ipsum</artidoc-section-title>
                        <artidoc-section-description>Lorem Ipsum dolor sit amet</artidoc-section-description>
                    </artidoc-section>
                </div>
                <div class="table-of-contents"></div>
                <div class="prose-mirror-toolbar-popover"></div>"
            `,
        );
        doc.body.insertAdjacentElement("afterbegin", toolbar_element);

        onClickActivateOrDeactivateToolbar(
            doc,
            toolbar_element,
            toolbar_bus,
            getHeadingsButtonState(),
        );
    });

    const clickOnElement = (element: Element | null): void => {
        if (!element) {
            throw new Error("Unable to find the element to click on.");
        }

        const event = Object.defineProperties(new MouseEvent("click"), {
            target: { value: element },
            composedPath: {
                value: () => [element],
            },
        });

        doc.dispatchEvent(event);
    };

    const clickOnEditor = (): void => {
        const section_description = doc.querySelector(".editor");
        clickOnElement(section_description);
    };

    it("When you click in an editor and then click outside the editor, then the toolbar should be disabled", () => {
        const table_of_contents = doc.querySelector(".table-of-contents");

        clickOnEditor();
        clickOnElement(table_of_contents);

        expect(disableToolbar).toHaveBeenCalledOnce();
    });

    it("When you click in an editor and then click in the toolbar, then the toolbar should not be disabled", () => {
        clickOnEditor();
        clickOnElement(toolbar_element);

        expect(disableToolbar).not.toHaveBeenCalled();
    });

    it("When you click in an editor and then click in a toolbar popover, then the toolbar should not be disabled", () => {
        const popover = doc.querySelector(".prose-mirror-toolbar-popover");

        clickOnEditor();
        clickOnElement(popover);

        expect(disableToolbar).not.toHaveBeenCalled();
    });
});
