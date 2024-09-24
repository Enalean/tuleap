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
import { createLocalDocument, gettext_provider } from "../../../../helpers";
import type { HostElement } from "./EditLinkFormElement";
import { renderEditLinkForm } from "./EditLinkFormTemplate";
import type { LinkProperties } from "../../../../types/internal-types";

describe("EditLinkFormTemplate", () => {
    let target: ShadowRoot;

    beforeEach(() => {
        const doc = createLocalDocument();
        target = doc.createElement("div") as unknown as ShadowRoot;
    });

    const getHost = (): HostElement =>
        ({
            link_href: "https://example.com",
            link_title: "See example",
            edit_link_callback: vi.fn(),
            cancel_callback: vi.fn(),
        }) as unknown as HostElement;

    const submitForm = (host: HostElement, updated_link: LinkProperties): void => {
        renderEditLinkForm(host, gettext_provider)(host, target);

        const form = target.querySelector<HTMLFormElement>("[data-test=edit-link-form]");
        const href_input = target.querySelector<HTMLInputElement>("[data-test=input-href]");
        const title_input = target.querySelector<HTMLInputElement>("[data-test=input-title]");

        if (!form || !href_input || !title_input) {
            throw new Error("Missing elements in EditLinkFormTemplate");
        }

        href_input.value = updated_link.href;
        href_input.dispatchEvent(new Event("input"));
        title_input.value = updated_link.title;
        title_input.dispatchEvent(new Event("input"));

        form.dispatchEvent(new Event("submit"));
    };

    it("When the form is submitted, then it should call the edit_link_callback with the updated url and title", () => {
        const host = getHost();

        submitForm(host, {
            href: "https://www.example.com",
            title: "See example HERE",
        });

        expect(host.edit_link_callback).toHaveBeenCalledOnce();
        expect(host.edit_link_callback).toHaveBeenCalledWith({
            href: "https://www.example.com",
            title: "See example HERE",
        });
    });

    it("When the title is empty, then the new title will be the current url", () => {
        const host = getHost();

        submitForm(host, {
            href: "https://www.example.com",
            title: "",
        });

        expect(host.edit_link_callback).toHaveBeenCalledOnce();
        expect(host.edit_link_callback).toHaveBeenCalledWith({
            href: "https://www.example.com",
            title: "https://www.example.com",
        });
    });

    it("When the form is canceled, then it should call the cancel_callback", () => {
        const host = getHost();

        renderEditLinkForm(host, gettext_provider)(host, target);

        const cancel_button = target.querySelector<HTMLButtonElement>("[data-test=cancel-button]");
        if (!cancel_button) {
            throw new Error("Expected a cancel button");
        }

        cancel_button.click();

        expect(host.cancel_callback).toHaveBeenCalledOnce();
    });
});
