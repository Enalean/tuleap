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

import { describe, it, expect } from "vitest";
import { renderArtidocSectionNode } from "./render-artidoc-section-node";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";

describe("render-artidoc-section-node", () => {
    it("Given a title and a description, Then it should render an artidoc-section node", () => {
        const title = "The title";
        const description = "<p>The description</p>";
        const section = ReactiveStoredArtidocSectionStub.fromSection(
            FreetextSectionFactory.override({
                title,
                description,
            }),
        );
        const section_node = renderArtidocSectionNode(section);

        const title_element = section_node.querySelector<HTMLElement>("artidoc-section-title");
        const description_element = section_node.querySelector<HTMLElement>(
            "artidoc-section-description",
        );
        if (
            !(title_element instanceof HTMLElement) ||
            !(description_element instanceof HTMLElement)
        ) {
            throw new Error("Unable to find the section title or the section description.");
        }

        expect(title_element.textContent).toBe(title);
        expect(
            description_element.innerHTML.trim().replace(/<!--\?lit\$[0-9]+\$-->|<!--\??-->/g, ""),
        ).toBe(description);
    });
});
