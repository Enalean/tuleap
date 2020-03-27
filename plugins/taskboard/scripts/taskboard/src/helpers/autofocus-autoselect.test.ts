/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { autoFocusAutoSelect } from "./autofocus-autoselect";

jest.useFakeTimers();

describe("autoFocusAutoSelect", () => {
    it("Focus the element", () => {
        const element = ({
            focus: jest.fn(),
            select: jest.fn(),
        } as unknown) as HTMLInputElement;

        autoFocusAutoSelect(element);

        expect(element.focus).toHaveBeenCalled();
    });
    it("Defers the selection of the content", () => {
        const element = ({
            focus: jest.fn(),
            select: jest.fn(),
        } as unknown) as HTMLInputElement;

        autoFocusAutoSelect(element);

        jest.runAllTimers();
        expect(setTimeout).toHaveBeenCalledWith(expect.any(Function), 10);
        expect(element.select).toHaveBeenCalled();
    });
});
