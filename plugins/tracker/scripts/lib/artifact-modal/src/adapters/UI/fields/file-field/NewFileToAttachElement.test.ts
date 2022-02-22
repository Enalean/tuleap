/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { HostElement } from "./NewFileToAttachElement";
import { NewFileToAttachElement, onClick, onDescriptionInput } from "./NewFileToAttachElement";
import { setCatalog } from "../../../../gettext-catalog";

describe(`NewFileToAttachElement`, () => {
    let dispatchEvent: jest.SpyInstance;
    beforeEach(() => {
        dispatchEvent = jest.fn();
        setCatalog({
            getString: (msgid): string => msgid,
        });
    });

    const dispatchInput = (value: string): void => {
        const host = { dispatchEvent } as unknown as HostElement;
        const doc = document.implementation.createHTMLDocument();
        const inner_input = doc.createElement("input");
        inner_input.addEventListener("input", (event) => onDescriptionInput(host, event));
        inner_input.value = value;
        inner_input.dispatchEvent(new InputEvent("input"));
    };

    it(`on input, it will dispatch a "description-changed" event with the input's value`, () => {
        dispatchInput("pernavigate");

        const event = dispatchEvent.mock.calls[0][0];
        expect(event.type).toBe("description-changed");
        expect(event.detail.description).toBe("pernavigate");
    });

    const triggerReset = (): HostElement => {
        const doc = document.implementation.createHTMLDocument();
        const file_input = doc.createElement("input");
        file_input.value = "filename.txt";
        const host = { file_input, dispatchEvent } as unknown as HostElement;

        const button = doc.createElement("button");
        button.addEventListener("click", () => onClick(host));
        button.click();
        return host;
    };

    it(`when I trigger the "Reset" button, it will dispatch a "reset" event
        and will reset the file input's value to an empty string`, () => {
        const host = triggerReset();

        expect(host.file_input?.value).toBe("");
        const event = dispatchEvent.mock.calls[0][0];
        expect(event.type).toBe("reset");
    });

    const renderNewFileToAttach = (host: HostElement): ShadowRoot => {
        const doc = document.implementation.createHTMLDocument();
        const target = doc.createElement("div") as unknown as ShadowRoot;
        const render = NewFileToAttachElement.content(host);
        render(host, target);

        return target;
    };

    it(`renders a New file to attach template`, () => {
        const DESCRIPTION = "nightless beadrow";
        const host = {
            disabled: true,
            required: true,
            description: DESCRIPTION,
        } as HostElement;
        const target = renderNewFileToAttach(host);

        const description_input = target.querySelector("[data-test=file-field-description-input]");
        const file_input = target.querySelector("[data-test=file-field-file-input]");
        const reset_button = target.querySelector("[data-test=file-field-reset]");
        if (
            !(description_input instanceof HTMLInputElement) ||
            !(file_input instanceof HTMLInputElement) ||
            !(reset_button instanceof HTMLButtonElement)
        ) {
            throw new Error("Unable to find an expected element in DOM");
        }

        expect(description_input.value).toBe(DESCRIPTION);
        expect(description_input.disabled).toBe(true);
        expect(file_input.required).toBe(true);
        expect(file_input.disabled).toBe(true);
        expect(reset_button.disabled).toBe(true);
    });
});
