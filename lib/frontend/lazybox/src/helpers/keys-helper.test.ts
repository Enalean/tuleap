/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import {
    isArrowDown,
    isArrowUp,
    isBackspaceKey,
    isEnterKey,
    isEscapeKey,
    isTabKey,
} from "./keys-helper";

const chrome_native_autofill_event = {} as KeyboardEvent;

describe("keys-helper", () => {
    it.each([
        ["isEscapeKey", isEscapeKey],
        ["isEnterKey", isEnterKey],
        ["isArrowDown", isArrowDown],
        ["isArrowUp", isArrowUp],
        ["isTabKey", isTabKey],
        ["isBackspaceKey", isBackspaceKey],
    ])(`%s does not recognize Chrome native autofill event`, (_method_name, method_under_test) => {
        expect(method_under_test(chrome_native_autofill_event)).toBe(false);
    });

    describe("isEscapeKey", () => {
        it("should return true", () => {
            [{ key: "Escape" }, { key: "Esc" }].forEach((event_init: KeyboardEventInit) => {
                expect(isEscapeKey(new KeyboardEvent("keyup", event_init))).toBe(true);
            });
        });
    });

    describe("isEnterKey", () => {
        it("should return true", () => {
            expect(isEnterKey(new KeyboardEvent("keyup", { key: "Enter" }))).toBe(true);
        });
    });

    describe("isArrowDown", () => {
        it("should return true", () => {
            expect(isArrowDown(new KeyboardEvent("keyup", { key: "ArrowDown" }))).toBe(true);
        });
    });

    describe("isArrowUp", () => {
        it("should return true", () => {
            expect(isArrowUp(new KeyboardEvent("keyup", { key: "ArrowUp" }))).toBe(true);
        });
    });

    describe("isTabKey", () => {
        it("should return true", () => {
            expect(isTabKey(new KeyboardEvent("keyup", { key: "Tab" }))).toBe(true);
        });
    });

    describe("isBackspaceKey", () => {
        it("should return true", () => {
            expect(isBackspaceKey(new KeyboardEvent("keyup", { key: "Backspace" }))).toBe(true);
        });
    });
});
