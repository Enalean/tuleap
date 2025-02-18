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

import { render, html } from "lit-html";
import { unsafeHTML } from "lit-html/directives/unsafe-html.js";
import DOMPurify from "dompurify";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";

export const renderArtidocSectionNode = (section: ReactiveStoredArtidocSection): HTMLElement => {
    const node = document.createDocumentFragment();

    const template = html`
        <artidoc-section>
            <artidoc-section-title>${section.value.title}</artidoc-section-title>
            <artidoc-section-description>
                ${unsafeHTML(DOMPurify.sanitize(section.value.description))}
            </artidoc-section-description>
        </artidoc-section>
    `;

    render(template, node);
    if (!(node.firstElementChild instanceof HTMLElement)) {
        throw new Error("Unable to render an <artidoc-section> element.");
    }

    return node.firstElementChild;
};
