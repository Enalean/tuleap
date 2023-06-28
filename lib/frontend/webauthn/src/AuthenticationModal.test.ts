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
import { describe, expect, it, vi } from "vitest";
import { AuthenticationModal } from "./AuthenticationModal";
import type { Modal } from "@tuleap/tlp-modal";

const noop = (): void => {
    // Do nothing
};

describe("AuthenticationModal", () => {
    it("should be closed then open target modal when submit form", async () => {
        const modal = new AuthenticationModal();
        modal.target_modal = { show: noop } as Modal;
        const showTargetModal = vi.spyOn(modal.target_modal, "show");
        const hide = vi.spyOn(modal, "hide");

        const event = new Event("submit", { cancelable: true });
        await modal.submitForm(event);

        expect(event.defaultPrevented).toBe(true);
        expect(showTargetModal).toHaveBeenCalled();
        expect(hide).toHaveBeenCalled();
    });
});
