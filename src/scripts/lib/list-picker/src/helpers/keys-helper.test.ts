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

import {
    isArrowDown,
    isArrowUp,
    isBackspaceKey,
    isEnterKey,
    isEscapeKey,
    isShiftKey,
    isTabKey,
} from "./keys-helper";

describe("keys-helper", () => {
    describe("isEscapeKey", () => {
        it("should return true", () => {
            [{ key: "Escape" }, { key: "Esc" }, { keyCode: 27 }].forEach(
                (event_init: KeyboardEventInit) => {
                    expect(isEscapeKey(new KeyboardEvent("keyup", event_init))).toBe(true);
                }
            );
        });

        it("should throw an error when the event is not a KeyboardEvent", () => {
            expect(() => isEscapeKey(new Event("mouseenter"))).toThrowError();
        });
    });

    describe("isBackspaceKey", () => {
        it("should return true", () => {
            [{ key: "Backspace" }, { keyCode: 8 }].forEach((event_init: KeyboardEventInit) => {
                expect(isBackspaceKey(new KeyboardEvent("keyup", event_init))).toBe(true);
            });
        });

        it("should throw an error when the event is not a KeyboardEvent", () => {
            expect(() => isBackspaceKey(new Event("mouseenter"))).toThrowError();
        });
    });

    describe("isEnterKey", () => {
        it("should return true", () => {
            [{ key: "Enter" }, { keyCode: 13 }].forEach((event_init: KeyboardEventInit) => {
                expect(isEnterKey(new KeyboardEvent("keyup", event_init))).toBe(true);
            });
        });

        it("should throw an error when the event is not a KeyboardEvent", () => {
            expect(() => isEnterKey(new Event("mouseenter"))).toThrowError();
        });
    });

    describe("isArrowDown", () => {
        it("should return true", () => {
            [{ key: "ArrowDown" }, { keyCode: 40 }].forEach((event_init: KeyboardEventInit) => {
                expect(isArrowDown(new KeyboardEvent("keyup", event_init))).toBe(true);
            });
        });

        it("should throw an error when the event is not a KeyboardEvent", () => {
            expect(() => isArrowDown(new Event("mouseenter"))).toThrowError();
        });
    });

    describe("isArrowUp", () => {
        it("should return true", () => {
            [{ key: "ArrowUp" }, { keyCode: 38 }].forEach((event_init: KeyboardEventInit) => {
                expect(isArrowUp(new KeyboardEvent("keyup", event_init))).toBe(true);
            });
        });

        it("should throw an error when the event is not a KeyboardEvent", () => {
            expect(() => isArrowUp(new Event("mouseenter"))).toThrowError();
        });
    });

    describe("isTabKey", () => {
        it("should return true", () => {
            [{ key: "Tab" }, { keyCode: 9 }].forEach((event_init: KeyboardEventInit) => {
                expect(isTabKey(new KeyboardEvent("keyup", event_init))).toBe(true);
            });
        });

        it("should throw an error when the event is not a KeyboardEvent", () => {
            expect(() => isTabKey(new Event("mouseenter"))).toThrowError();
        });
    });

    describe("isShiftKey", () => {
        it("should return true", () => {
            [{ key: "Shift" }, { keyCode: 16 }].forEach((event_init: KeyboardEventInit) => {
                expect(isShiftKey(new KeyboardEvent("keyup", event_init))).toBe(true);
            });
        });

        it("should throw an error when the event is not a KeyboardEvent", () => {
            expect(() => isShiftKey(new Event("mouseenter"))).toThrowError();
        });
    });
});
