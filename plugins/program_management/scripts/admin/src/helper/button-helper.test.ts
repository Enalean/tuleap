/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import {
    resetButtonToAddTeam,
    resetButtonToSaveConfiguration,
    setButtonToDisabledWithSpinner,
} from "./button-helper";

describe("ButtonHelper", () => {
    describe("setButtonToDisabledWithSpinner", () => {
        it("Error is thrown When icon element does not exist", () => {
            const button = document.createElement("button");
            expect(() => setButtonToDisabledWithSpinner(button)).toThrow(
                "Icon on button does not exist",
            );
        });
        it("When icon is set Then icon class is changed to have spinner", () => {
            const button = document.createElement("button");
            const icon = document.createElement("i");
            icon.classList.add("fas", "fa-plus");
            button.appendChild(icon);

            setButtonToDisabledWithSpinner(button);
            expect(icon.classList).toContain("fa");
            expect(icon.classList).toContain("fa-spin");
            expect(icon.classList).toContain("fa-circle-o-notch");
        });
    });
    describe("resetButtonToAddTeam", () => {
        it("Error is thrown When icon element does not exist", () => {
            const button = document.createElement("button");
            expect(() => resetButtonToAddTeam(button)).toThrow("Icon on button does not exist");
        });
        it("When icon is a spinner Then icon class is changed to have plus", () => {
            const button = document.createElement("button");
            const icon = document.createElement("i");
            icon.classList.add("fa", "fa-spin", "fa-circle-o-notch");
            button.appendChild(icon);

            resetButtonToAddTeam(button);
            expect(icon.classList).toContain("fas");
            expect(icon.classList).toContain("fa-plus");
        });
    });
    describe("resetButtonToSaveConfiguration", () => {
        it("Error is thrown When icon element does not exist", () => {
            const button = document.createElement("button");
            expect(() => resetButtonToSaveConfiguration(button)).toThrow(
                "Icon on button does not exist",
            );
        });
        it("When icon is a spinner Then icon class is changed to have plus", () => {
            const button = document.createElement("button");
            const icon = document.createElement("i");
            icon.classList.add("fa", "fa-spin", "fa-circle-o-notch");
            button.appendChild(icon);

            resetButtonToSaveConfiguration(button);
            expect(icon.classList).toContain("fa");
            expect(icon.classList).toContain("fa-save");
        });
    });
});
