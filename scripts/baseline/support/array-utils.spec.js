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

    describe("mapAttribute", () => {
        it("returns specifics objects attribute", () => {
            expect(
                ArrayUtils.mapAttribute(
                    [{ id: 1, title: "Scra" }, { id: 2, title: "Jibidus" }],
                    "id"
                )
            ).toEqual([1, 2]);
        });

        it("returns empty array when no element match with given attribute", () => {
            expect(ArrayUtils.mapAttribute([{ id: 1, title: "Scra" }], "not_exist")).toEqual([]);
        });

        it("returns empty array when array is empty", () => {
            expect(ArrayUtils.mapAttribute([], "id")).toEqual([]);
        });
    });

    describe("unique", () => {
        describe("when they are objects", () => {
            const obj = { id: 1, title: "Scra" };

            it("returns unique values", () => {
                expect(ArrayUtils.unique([obj, obj])).toEqual([obj]);
            });
        });

        describe("when they are numbers", () => {
            it("returns unique values", () => {
                expect(ArrayUtils.unique([1, 1, 2])).toEqual([1, 2]);
            });
        });

        describe("when they are booleans", () => {
            it("returns unique values", () => {
                expect(ArrayUtils.unique([true, true, false])).toEqual([true, false]);
            });
        });

        describe("when they are string", () => {
            it("returns unique values", () => {
                expect(
                    ArrayUtils.unique(["unique string", "unique string", "other string"])
                ).toEqual(["unique string", "other string"]);
            });
        });

        describe("when array is empty", () => {
            it("returns empty array", () => {
                expect(ArrayUtils.unique([])).toEqual([]);
            });
        });

        describe("when they are null values", () => {
            it("returns unique null values", () => {
                expect(ArrayUtils.unique([null, null])).toEqual([null]);
            });
        });
    });

    describe("clone", () => {
        const obj = { id: 1, title: "Scra" };

        it("returns clones", () => {
            expect(ArrayUtils.clone([obj])[0]).not.toBe(obj);
        });

        it("returns empty array when array is empty", () => {
            expect(ArrayUtils.clone([obj])[0]).not.toBe(obj);
        });
    });
});
