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
import { createLocalDocument, gettext_provider } from "../../../helpers";
import {
    CrossReferenceLinkPopoverButtonsRenderer,
    RegularLinkPopoverButtonsRenderer,
} from "./LinkPopoverButtonsRenderers";
import type { HostElement } from "./LinkPopoverElement";

describe("LinkPopoverButtonsRenderers", () => {
    let target: ShadowRoot, host: HTMLElement;

    beforeEach(() => {
        const doc = createLocalDocument();
        target = doc.createElement("div") as unknown as ShadowRoot;
        host = doc.createElement("div");
    });

    it("RegularLinkPopoverButtonsRenderer should return a render function for regular link popover buttons", () => {
        const noop = (): void => {
            // Do nothing
        };
        const renderer = RegularLinkPopoverButtonsRenderer(
            gettext_provider,
            {
                href: "https://example.com",
                title: "Example website",
            },
            noop,
        );

        renderer.render({} as HostElement)(host, target);

        expect(target.children.length).toBe(4);
        expect(target.querySelector("[data-test=open-link-button]")).not.toBeNull();
        expect(target.querySelector("[data-test=copy-to-clipboard-button]")).not.toBeNull();
        expect(target.querySelector("[data-test=edit-link-button]")).not.toBeNull();
        expect(target.querySelector("[data-test=remove-link-button]")).not.toBeNull();
    });

    it("CrossReferenceLinkPopoverButtonsRenderer should return a render function for cross reference link popover buttons", () => {
        const renderer = CrossReferenceLinkPopoverButtonsRenderer(gettext_provider, {
            href: "https://example.com",
            title: "art #123",
        });

        renderer.render({} as HostElement)(host, target);

        expect(target.children.length).toBe(3);
        expect(target.querySelector("[data-test=open-link-button]")).not.toBeNull();
        expect(target.querySelector("[data-test=copy-to-clipboard-button]")).not.toBeNull();
        expect(target.querySelector("[data-test=edit-reference-button]")).not.toBeNull();
    });
});
