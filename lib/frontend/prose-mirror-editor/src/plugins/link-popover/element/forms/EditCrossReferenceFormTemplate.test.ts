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

import { describe, beforeEach, it, expect, vi } from "vitest";
import { createLocalDocument, gettext_provider } from "../../../../helpers";
import type { HostElement } from "./EditCrossReferenceFormElement";
import { renderEditCrossReferenceFormElement } from "./EditCrossReferenceFormTemplate";

describe("EditCrossReferenceFormTemplate", () => {
    let target: ShadowRoot;

    beforeEach(() => {
        const doc = createLocalDocument();
        target = doc.createElement("div") as unknown as ShadowRoot;
    });

    const getHost = (): HostElement =>
        ({
            reference_text: "art #123",
            edit_cross_reference_callback: vi.fn(),
            cancel_callback: vi.fn(),
        }) as unknown as HostElement;

    const submitForm = (host: HostElement, updated_reference_text: string): void => {
        renderEditCrossReferenceFormElement(host, gettext_provider)(host, target);

        const form = target.querySelector<HTMLFormElement>("[data-test=edit-cross-ref-form]");
        const title_input = target.querySelector<HTMLInputElement>("[data-test=reference-text]");

        if (!form || !title_input) {
            throw new Error("Missing elements in EditLinkFormTemplate");
        }

        title_input.value = updated_reference_text;
        title_input.dispatchEvent(new Event("input"));

        form.dispatchEvent(new Event("submit"));
    };

    it("When the form is submitted, then it should call the edit_cross_reference_callback with the updated reference", () => {
        const host = getHost();
        const updated_ref = "art #456";

        submitForm(host, updated_ref);

        expect(host.edit_cross_reference_callback).toHaveBeenCalledOnce();
        expect(host.edit_cross_reference_callback).toHaveBeenCalledWith(updated_ref);
    });

    it("When the form is canceled, then it should call the cancel_callback", () => {
        const host = getHost();

        renderEditCrossReferenceFormElement(host, gettext_provider)(host, target);

        const cancel_button = target.querySelector<HTMLButtonElement>("[data-test=cancel-button]");
        if (!cancel_button) {
            throw new Error("Expected a cancel button");
        }

        cancel_button.click();

        expect(host.cancel_callback).toHaveBeenCalledOnce();
    });
});
