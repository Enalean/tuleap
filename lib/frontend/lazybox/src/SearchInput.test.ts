/*
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
import type { HostElement } from "./SearchInput";
import { buildClear, connect, onInput, onKeyDown, onKeyUp } from "./SearchInput";

const noopSearchCallback = (query: string): void => {
    //Fake usage of query to prevent eslint from removing it
    if (query !== "") {
        //Do nothing
    }
};

describe(`SearchInput`, () => {
    let inner_input: HTMLInputElement, doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        inner_input = doc.createElement("input");
    });

    describe("onInput()", () => {
        beforeEach(() => {
            vi.useFakeTimers();
        });

        const getHost = (): HostElement =>
            Object.assign(doc.createElement("span"), {
                query: "",
                timeout_id: undefined,
                search_callback: noopSearchCallback,
            }) as HostElement;

        const buildInputEvent = (input_value: string): Event => {
            const inner_event = new Event("input");
            inner_input.value = input_value;
            inner_input.dispatchEvent(inner_event);
            return inner_event;
        };

        it(`dispatches a "search-input" event to open the dropdown`, () => {
            const host = getHost();
            const dispatchEvent = vi.spyOn(host, "dispatchEvent");

            const inner_event = buildInputEvent("a");
            onInput(host, inner_event);

            const event = dispatchEvent.mock.calls[0][0];
            expect(event.type).toBe("search-input");
        });

        it("should execute the callback after 250ms after the users has stopped typing in the search_field_element", () => {
            const host = getHost();
            const search_callback = vi.spyOn(host, "search_callback");

            const event = buildInputEvent("a query");
            onInput(host, event);

            vi.advanceTimersByTime(249); // 249 ms elapsed

            expect(search_callback).not.toHaveBeenCalled();

            vi.advanceTimersByTime(1); // 250 ms elapsed

            expect(search_callback).toHaveBeenCalledWith("a query");
        });

        it("should not execute the callback when the user it still typing", () => {
            const host = getHost();
            const search_callback = vi.spyOn(host, "search_callback");

            ["nana ", "nana ", "nana ", "BATMAN"].forEach((query) => {
                inner_input.value += query;
                const event = new Event("input");
                inner_input.dispatchEvent(event);
                onInput(host, event);
            });

            vi.advanceTimersByTime(250);

            expect(search_callback).toHaveBeenCalledTimes(1);
            expect(search_callback).toHaveBeenCalledWith("nana nana nana BATMAN");
        });

        it("When the query has been cleared, then it should trigger the callback immediately", () => {
            const host = getHost();
            const search_callback = vi.spyOn(host, "search_callback");

            const event = buildInputEvent("");
            onInput(host, event);

            vi.advanceTimersByTime(0); // 0 ms elapsed

            expect(search_callback).toHaveBeenCalledWith("");
        });
    });

    describe(`events`, () => {
        const getHost = (): HostElement =>
            Object.assign(doc.createElement("span"), {
                query: "",
            }) as HostElement;

        const buildKeyboardEvent = (key: string, input_value: string): KeyboardEvent => {
            const inner_event = new KeyboardEvent("keyup", { key });
            inner_input.value = input_value;
            inner_input.dispatchEvent(inner_event);
            return inner_event;
        };

        it(`dispatches a "backspace-pressed" event`, () => {
            const host = getHost();
            const dispatchEvent = vi.spyOn(host, "dispatchEvent");
            const inner_event = buildKeyboardEvent("Backspace", "");
            onKeyUp(host, inner_event);

            const event = dispatchEvent.mock.calls[0][0];
            expect(event.type).toBe("backspace-pressed");
        });

        it(`prevents the "enter" key from submitting forms
            and stops propagation to avoid triggering handler in SelectionElement`, () => {
            const event = new KeyboardEvent("keydown", { key: "Enter", cancelable: true });
            const stopPropagation = vi.spyOn(event, "stopPropagation");
            onKeyDown({}, event);

            expect(event.defaultPrevented).toBe(true);
            expect(stopPropagation).toHaveBeenCalled();
        });

        it(`assigns host query on keyUp`, () => {
            const host = getHost();
            const inner_event = buildKeyboardEvent("a", "a");
            onKeyUp(host, inner_event);

            expect(host.query).toBe("a");
        });

        it(`when focused, it transmits focus to the inner input element`, () => {
            const focus = vi.spyOn(inner_input, "focus");
            const target = doc.createElement("div");
            target.append(inner_input);
            const host = Object.assign(doc.createElement("span"), {
                content: (): HTMLElement => target,
            }) as HostElement;

            connect(host);

            host.dispatchEvent(new Event("focus"));
            expect(focus).toHaveBeenCalled();
        });
    });

    describe("clear()", () => {
        it(`clears the query and calls the callback`, () => {
            const host = {
                query: "a query",
                search_callback: noopSearchCallback,
            } as HostElement;
            const search_callback = vi.spyOn(host, "search_callback");

            buildClear(host)();

            expect(host.query).toBe("");
            expect(search_callback).toHaveBeenCalledWith("");
        });
    });
});
