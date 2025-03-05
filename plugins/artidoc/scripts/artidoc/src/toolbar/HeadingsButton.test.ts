/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { describe, it, expect, vi } from "vitest";
import type { GetText } from "@tuleap/gettext";
import type { HostElement } from "@/toolbar/HeadingsButton";
import { renderHeadingsButton } from "@/toolbar/HeadingsButton";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { LEVEL_3 } from "@/sections/levels/SectionsNumberer";
import { createHeadingButton } from "@/toolbar/create-heading-button";

const gettext_provider = {
    gettext: (english: string) => english,
} as GetText;

const freetext_section = ReactiveStoredArtidocSectionStub.fromSection(
    FreetextSectionFactory.override({
        level: LEVEL_3,
    }),
);

describe("HeadingsButton", () => {
    const headings_button_element = createHeadingButton(freetext_section);
    if (headings_button_element === null) {
        throw new Error("Unable to find headings button element");
    }

    const items: NodeListOf<HTMLSpanElement> =
        headings_button_element.dropdown_menu.querySelectorAll<HTMLSpanElement>("[role=menuitem]");

    it("should apply correct classes to dropdown items based on section level", () => {
        expect(items[0].classList.value).not.contains("artidoc-selected-level");
        expect(items[1].classList.value).not.contains("artidoc-selected-level");
        expect(items[2].classList.value).contains("artidoc-selected-level");
    });

    describe("renderHeadingsButton", () => {
        const doc = document.implementation.createHTMLDocument();

        const getHost = (): HostElement =>
            Object.assign(doc.createElement("button"), {
                section: freetext_section.value,
                is_disabled: false,
            }) as unknown as HostElement;

        it("should dispatch event when click on a different Headings", () => {
            const host = getHost();
            const dispatchEvent = vi.spyOn(host, "dispatchEvent");

            host.dispatchEvent(new Event("update-section-level"));
            renderHeadingsButton(host, gettext_provider);

            const event = dispatchEvent.mock.calls[0][0];

            expect(event.type).toBe("update-section-level");
            expect(dispatchEvent).toHaveBeenCalledOnce();
        });
    });
});
