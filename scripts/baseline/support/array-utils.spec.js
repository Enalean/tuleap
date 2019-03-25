/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

import ArrayUtils from "./array-utils";

describe("ArrayUtils:", () => {
    describe("find", () => {
        const ALWAYS_TRUE = () => true;
        const ALWAYS_FALSE = () => false;

        it("returns first element which match given predicate", () => {
            expect(ArrayUtils.find([1, 2, 3], value => value > 1)).toEqual(2);
        });

        it("returns undefined when no element match with given predicate", () => {
            expect(ArrayUtils.find([1, 2, 3], ALWAYS_FALSE)).toBeUndefined();
        });

        it("returns undefined when array is empty", () => {
            expect(ArrayUtils.find([], ALWAYS_TRUE)).toBeUndefined();
        });
    });
});
