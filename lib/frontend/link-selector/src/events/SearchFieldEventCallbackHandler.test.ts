/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { SearchFieldEventCallbackHandlerType } from "./SearchFieldEventCallbackHandler";
import { SearchFieldEventCallbackHandler } from "./SearchFieldEventCallbackHandler";
import { DropdownContentRefresherStub } from "../../tests/stubs/DropdownContentRefresherStub";

describe("SearchFieldEventCallbackHandler", () => {
    const getHandler = (): SearchFieldEventCallbackHandlerType => {
        const handler = SearchFieldEventCallbackHandler(DropdownContentRefresherStub.build());
        return handler;
    };

    it("should execute the callback after 250ms after the users has stopped typing in the search_field_element", () => {
        const doc = document.implementation.createHTMLDocument();
        const search_field_element = doc.createElement("input");
        const callback = jest.fn();

        jest.useFakeTimers();

        getHandler().init(search_field_element, callback);

        search_field_element.value = "a query";
        search_field_element.dispatchEvent(new Event("input"));

        jest.advanceTimersByTime(249); // 249 ms elapsed

        expect(callback).not.toHaveBeenCalled();

        jest.advanceTimersByTime(1); // 250 ms elapsed

        expect(callback).toHaveBeenCalledWith("a query", expect.any(Function));
    });

    it("should not execute the callback when the user it still typing", () => {
        const doc = document.implementation.createHTMLDocument();
        const search_field_element = doc.createElement("input");
        const callback = jest.fn();

        jest.useFakeTimers();

        getHandler().init(search_field_element, callback);

        ["nana ", "nana ", "nana ", "BATMAN"].forEach((query) => {
            search_field_element.value += query;
            search_field_element.dispatchEvent(new Event("input"));
        });

        jest.advanceTimersByTime(250);

        expect(callback).toHaveBeenCalledTimes(1);
        expect(callback).toHaveBeenCalledWith("nana nana nana BATMAN", expect.any(Function));
    });
});
