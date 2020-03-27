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

import { DrekkenovInitOptions, init } from "./drekkenov";
import { DrekkenovState } from "./DrekkenovState";
const noop_function = (): void => {
    // Do nothing
};
jest.mock("./DrekkenovState", () => {
    return {
        DrekkenovState: jest.fn().mockImplementation(() => {
            return {
                createDragStartHandler: jest.fn().mockReturnValue(noop_function),
                cleanup: jest.fn(),
            };
        }),
    };
});

describe(`drekkenov`, () => {
    afterEach(() => {
        const state_constructor = (DrekkenovState as unknown) as jest.SpyInstance;
        state_constructor.mockClear();
    });

    describe(`init()`, () => {
        it(`will attach a dragstart listener on document,
            and it will return a new Drekkenov instance`, () => {
            const addEventListener = jest.spyOn(document, "addEventListener");
            const options = {} as DrekkenovInitOptions;

            init(options);

            const state_constructor = (DrekkenovState as unknown) as jest.SpyInstance;
            const state = state_constructor.mock.results[0].value;
            expect(state_constructor).toHaveBeenCalled();
            expect(state.createDragStartHandler).toHaveBeenCalled();
            expect(addEventListener).toHaveBeenCalledWith("dragstart", noop_function);
        });
    });

    describe(`Drekkenov instance`, () => {
        describe(`destroy()`, () => {
            it(`will cleanup the state,
                and it will remove the dragstart listener on document`, () => {
                jest.spyOn(document, "addEventListener");
                const removeEventListener = jest.spyOn(document, "removeEventListener");
                const options = {} as DrekkenovInitOptions;

                const drekkenov_instance = init(options);
                drekkenov_instance.destroy();

                const state_constructor = (DrekkenovState as unknown) as jest.SpyInstance;
                const state = state_constructor.mock.results[0].value;

                expect(state.cleanup).toHaveBeenCalled();
                expect(removeEventListener).toHaveBeenCalledWith("dragstart", noop_function);
            });
        });
    });
});
