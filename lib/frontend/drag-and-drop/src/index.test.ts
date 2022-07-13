/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import type { SpyInstance } from "vitest";
import { describe, expect, it, afterEach, vi } from "vitest";
import type { DrekkenovInitOptions } from "./index";
import { init } from "./index";
import { DrekkenovState } from "./DrekkenovState";
let state_create_start_handler_called = false;
const noop_function = (): void => {
    // Do nothing
};
vi.mock("./DrekkenovState", () => {
    const mocked_class = vi.fn();
    mocked_class.prototype.createDragStartHandler = (): (() => void) => {
        state_create_start_handler_called = true;
        return noop_function;
    };
    mocked_class.prototype.cleanup = vi.fn();

    return { DrekkenovState: mocked_class };
});

describe(`drekkenov`, () => {
    afterEach(() => {
        state_create_start_handler_called = false;
        const state_constructor = DrekkenovState as unknown as SpyInstance;
        state_constructor.mockClear();
    });

    describe(`init()`, () => {
        it(`will attach a dragstart listener on document,
            and it will return a new Drekkenov instance`, () => {
            const addEventListener = vi.spyOn(document, "addEventListener");
            const options = {} as DrekkenovInitOptions;

            init(options);

            expect(DrekkenovState).toHaveBeenCalled();
            expect(state_create_start_handler_called).toBe(true);
            expect(addEventListener).toHaveBeenCalledWith("dragstart", noop_function);
        });
    });

    describe(`Drekkenov instance`, () => {
        describe(`destroy()`, () => {
            it(`will cleanup the state,
                and it will remove the dragstart listener on document`, () => {
                vi.spyOn(document, "addEventListener");
                const removeEventListener = vi.spyOn(document, "removeEventListener");
                const options = {} as DrekkenovInitOptions;

                const drekkenov_instance = init(options);
                drekkenov_instance.destroy();

                expect(DrekkenovState.prototype.cleanup).toHaveBeenCalled();
                expect(removeEventListener).toHaveBeenCalledWith("dragstart", noop_function);
            });
        });
    });
});
