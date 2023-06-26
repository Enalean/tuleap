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
import { buildClear, onInput, onKeyUp } from "./SearchInput";

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
            } as HostElement);

        const buildInputEvent = (input_value: string): Event => {
            const inner_event = new Event("input");
            inner_input.value = input_value;
            inner_input.dispatchEvent(inner_event);
            return inner_event;
        };

        it(`dispatches a "search-entered" event to open the dropdown`, () => {
            const host = getHost();
            const dispatchEvent = vi.spyOn(host, "dispatchEvent");

            const inner_event = buildInputEvent("a");
            onInput(host, inner_event);

            const event = dispatchEvent.mock.calls[0][0];
            expect(event.type).toBe("search-entered");
        });

        it(`after waiting 250ms after the users has stopped typing in the input,
            it will dispatch a "search-input" event`, () => {
            const host = getHost();
            const dispatchEvent = vi.spyOn(host, "dispatchEvent");

            const inner_event = buildInputEvent("a query");
            onInput(host, inner_event);

            vi.advanceTimersByTime(249); // 249 ms elapsed

            expect(dispatchEvent).toHaveBeenCalledOnce();

            vi.advanceTimersByTime(1); // 250 ms elapsed

            expect(dispatchEvent).toHaveBeenCalledTimes(2);
            const event = dispatchEvent.mock.calls[1][0];
            expect(event.type).toBe("search-input");
        });

        it(`will not dispatch a "search-input" event while the user it still typing`, () => {
            const host = getHost();
            const dispatchEvent = vi.spyOn(host, "dispatchEvent");

            ["nana ", "nana ", "nana ", "BATMAN"].forEach((query) => {
                inner_input.value += query;
                const event = new Event("input");
                inner_input.dispatchEvent(event);
                onInput(host, event);
            });

            vi.advanceTimersByTime(250);

            const all_search_input_events = dispatchEvent.mock.calls
                .map((args) => args[0].type)
                .filter((type) => type === "search-input");
            expect(all_search_input_events).toHaveLength(1);
        });

        it(`When the query has been cleared, then it should dispatch a "search-input" event immediately`, () => {
            const host = getHost();
            const dispatchEvent = vi.spyOn(host, "dispatchEvent");

            const inner_event = buildInputEvent("");
            onInput(host, inner_event);

            vi.advanceTimersByTime(0); // 0 ms elapsed

            const event = dispatchEvent.mock.calls[1][0];
            expect(event.type).toBe("search-input");
        });
    });

    describe(`events`, () => {
        const getHost = (): HostElement =>
            Object.assign(doc.createElement("span"), {
                query: "",
            } as HostElement);

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

        it(`assigns host query on keyUp`, () => {
            const host = getHost();
            const inner_event = buildKeyboardEvent("a", "a");
            onKeyUp(host, inner_event);

            expect(host.query).toBe("a");
        });
    });

    describe("clear()", () => {
        it(`clears the query and dispatches a "search-input" event`, () => {
            const host = Object.assign(doc.createElement("span"), {
                query: "a query",
            } as HostElement);
            const dispatchEvent = vi.spyOn(host, "dispatchEvent");

            buildClear(host)();

            expect(host.query).toBe("");
            const event = dispatchEvent.mock.calls[0][0];
            expect(event.type).toBe("search-input");
        });
    });
});
