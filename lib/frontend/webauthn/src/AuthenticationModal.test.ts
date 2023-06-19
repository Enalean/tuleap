/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
import { beforeEach, describe, expect, it, vi } from "vitest";
import { AuthenticationModal, HIDDEN_CLASS } from "./AuthenticationModal";
import { errAsync, okAsync } from "neverthrow";
import * as auth from "./authenticate";
import type { Modal } from "@tuleap/tlp-modal";
import { Fault } from "@tuleap/fault";

const noop = (): void => {
    // Do nothing
};

describe("AuthenticationModal", () => {
    let doc: Document;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    it("should call authenticate when submit form", async () => {
        const modal = new AuthenticationModal();
        modal.target_modal = { show: noop } as Modal;
        const icon = doc.createElement("i");
        icon.classList.add(HIDDEN_CLASS);
        modal.modal_button_icon = icon;
        const showTargetModal = vi.spyOn(modal.target_modal, "show");
        vi.spyOn(auth, "authenticate").mockReturnValue(okAsync(null));
        const hide = vi.spyOn(modal, "hide");

        const event = new Event("submit", { cancelable: true });
        const promise = modal.submitForm(event);
        expect(modal.modal_button_icon.classList.contains(HIDDEN_CLASS)).toBe(false);
        await promise;
        expect(modal.modal_button_icon.classList.contains(HIDDEN_CLASS)).toBe(true);

        expect(event.defaultPrevented).toBe(true);
        expect(hide).toHaveBeenCalled();
        expect(showTargetModal).toHaveBeenCalled();
    });

    it("when there is an error during authentication, it will display the fault", async () => {
        const modal = new AuthenticationModal();
        modal.modal_error = doc.createElement("div");
        modal.modal_button_icon = doc.createElement("i");
        const fault_message = "message";
        vi.spyOn(auth, "authenticate").mockReturnValue(errAsync(Fault.fromMessage(fault_message)));

        const event = new Event("submit", { cancelable: true });
        await modal.submitForm(event);

        expect(event.defaultPrevented).toBe(true);
        expect(modal.modal_error.innerText).toContain(fault_message);
        expect(modal.modal_error.classList.contains(HIDDEN_CLASS)).toBe(false);
        expect(modal.modal_button_icon.classList.contains(HIDDEN_CLASS)).toBe(true);
    });
});
