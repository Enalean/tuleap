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

import { describe, beforeEach, it, expect, vi } from "vitest";
import type { GetText } from "@tuleap/gettext";
import type { HostElement } from "@/toolbar/HeadingsButton";
import { isUpdateSectionLevelEvent, renderHeadingsButton } from "@/toolbar/HeadingsButton";
import type { StoredArtidocSection } from "@/sections/SectionsCollection";
import { LEVEL_1, LEVEL_2, LEVEL_3 } from "@/sections/levels/SectionsNumberer";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { CreateStoredSections } from "@/sections/states/CreateStoredSections";

describe("HeadingsButton", () => {
    let doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    const getHost = (section: StoredArtidocSection | undefined): HostElement =>
        Object.assign(doc.createElement("div"), { section } as HostElement);

    const renderButton = (host: HostElement): ShadowRoot => {
        const target = doc.createElement("div") as unknown as ShadowRoot;
        const render = renderHeadingsButton(host, {
            gettext: (message: string) => message,
        } as GetText);

        render(host, target);

        return target;
    };

    it("should apply correct classes to dropdown items based on section level", () => {
        const section = CreateStoredSections.fromArtidocSection(
            FreetextSectionFactory.override({
                level: LEVEL_3,
            }),
        );

        const host = getHost(section);
        const items: NodeListOf<HTMLSpanElement> =
            renderButton(host).querySelectorAll<HTMLSpanElement>("[role=menuitem]");

        expect(items[0].classList.value).not.contains("artidoc-selected-level");
        expect(items[1].classList.value).not.contains("artidoc-selected-level");
        expect(items[2].classList.value).contains("artidoc-selected-level");
    });

    it.each([
        ["change-section-level-1", LEVEL_1],
        ["change-section-level-2", LEVEL_2],
        ["change-section-level-3", LEVEL_3],
    ])(
        "When the user click the %s item in the dropdown, then it should dispatch an update-section-level event with the correct heading level",
        (item_name, expected_level) => {
            const section = CreateStoredSections.fromArtidocSection(
                FreetextSectionFactory.create(),
            );
            const host = getHost(section);
            const button = renderButton(host);
            const dispatchEvent = vi.spyOn(host, "dispatchEvent");

            button.querySelector<HTMLElement>(`[data-test=${item_name}]`)?.click();

            const event = dispatchEvent.mock.calls[0][0];
            if (!isUpdateSectionLevelEvent(event)) {
                throw new Error("Expected an update-section-level event.");
            }

            expect(event.detail.level).toBe(expected_level);
            expect(dispatchEvent).toHaveBeenCalledOnce();
        },
    );
});
