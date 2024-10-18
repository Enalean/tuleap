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

import { describe, it, expect, beforeEach, vi } from "vitest";
import { buildToolbarBus } from "@tuleap/prose-mirror-editor";
import type { ToolbarBus } from "@tuleap/prose-mirror-editor";
import { createLocalDocument, gettext_provider } from "../../../helpers/helper-for-test";
import { renderLinkPopover } from "./link-popover-template";
import type { HostElement } from "./link";

describe("link-popover-template", () => {
    let target: ShadowRoot, toolbar_bus: ToolbarBus;

    beforeEach(() => {
        const doc = createLocalDocument();
        target = doc.createElement("div") as unknown as ShadowRoot;
        toolbar_bus = buildToolbarBus();
    });

    it.each([
        [true, "should be disabled"],
        [false, "should NOT be disabled"],
    ])("When host.is_disabled is %s then the submit button %s", (is_disabled) => {
        const host = { is_disabled } as unknown as HostElement;

        renderLinkPopover(host, gettext_provider)(host, target);
        const submit_button = target.querySelector("[data-test=submit-button]");
        if (!submit_button) {
            throw new Error("Expected a submit button");
        }

        expect(submit_button.hasAttribute("disabled")).toBe(is_disabled);
    });

    const getHost = (): HostElement =>
        ({
            link_href: "https://example.com",
            link_title: "See example",
            popover_instance: {
                hide: vi.fn(),
            },
            toolbar_bus,
        }) as unknown as HostElement;

    const submitForm = (host: HostElement, href: string, title: string): void => {
        renderLinkPopover(host, gettext_provider)(host, target);

        const form = target.querySelector<HTMLFormElement>("[data-test=toolbar-link-popover-form]");
        const href_input = target.querySelector<HTMLInputElement>("[data-test=input-href]");
        const title_input = target.querySelector<HTMLInputElement>("[data-test=input-title]");

        if (!form || !href_input || !title_input) {
            throw new Error("Missing elements in popover template");
        }

        href_input.value = href;
        href_input.dispatchEvent(new Event("input"));
        title_input.value = title;
        title_input.dispatchEvent(new Event("input"));

        form.dispatchEvent(new Event("submit"));
    };

    it("When the form is submitted, then it should call the toolbar_bus.link() method with the title and href, and finally hide the popover", () => {
        const host = getHost();
        const href = "https://www.example.com";
        const title = "See example HERE";
        const link = vi.spyOn(toolbar_bus, "link");

        submitForm(host, href, title);

        expect(link).toHaveBeenCalledOnce();
        expect(link).toHaveBeenCalledWith({
            href,
            title,
        });
        expect(host.popover_instance.hide).toHaveBeenCalledOnce();
    });

    it("When the title is empty, then the new title will be the current url", () => {
        const host = getHost();
        const href = "https://www.example.com";
        const title = "";
        const link = vi.spyOn(toolbar_bus, "link");

        submitForm(host, href, title);

        expect(link).toHaveBeenCalledOnce();
        expect(link).toHaveBeenCalledWith({
            href,
            title: href,
        });
        expect(host.popover_instance.hide).toHaveBeenCalledOnce();
    });
});
