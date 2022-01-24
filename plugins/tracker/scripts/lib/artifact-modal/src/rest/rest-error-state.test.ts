/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
import { getErrorMessage, hasError, resetError, setError } from "./rest-error-state";

describe(`rest error state`, () => {
    beforeEach(() => {
        resetError();
    });

    it(`will hold null by default`, () => {
        expect(getErrorMessage()).toBeNull();
        expect(hasError()).toBe(false);
    });

    it(`will get, set and reset an error message`, () => {
        const message = "Ooops";
        setError(message);
        expect(hasError()).toBe(true);
        expect(getErrorMessage()).toBe(message);

        resetError();
        expect(hasError()).toBe(false);
        expect(getErrorMessage()).toBeNull();
    });
});
