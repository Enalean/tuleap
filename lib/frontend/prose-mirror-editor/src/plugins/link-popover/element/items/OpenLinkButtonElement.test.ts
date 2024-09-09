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

import { describe, it, expect, beforeEach } from "vitest";
import { createLocalDocument, gettext_provider } from "../../../../helpers/helper-for-test";
import type { HostElement, InternalOpenLinkButtonElement } from "./OpenLinkButtonElement";
import { renderOpenLinkButton } from "./OpenLinkButtonElement";

describe("OpenLinkButtonElement", () => {
    let doc: Document, target: ShadowRoot;

    beforeEach(() => {
        doc = createLocalDocument();
        target = doc.createElement("div") as unknown as ShadowRoot;
    });

    it("should contain a clickable link to open the link", () => {
        const host = Object.assign(doc.createElement("div"), {
            sanitized_link_href: "https://www.example.com/",
        } as InternalOpenLinkButtonElement) as HostElement;

        const render = renderOpenLinkButton(host, gettext_provider);
        render(host, target);

        expect(target.querySelector<HTMLLinkElement>("[data-test=open-link-button]")?.href).toBe(
            host.sanitized_link_href,
        );
    });
});
