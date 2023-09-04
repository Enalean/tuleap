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
 *
 */

import ArrayUtils from "./array-utils";

describe("ArrayUtils:", () => {
    describe("mapAttribute", () => {
        it("returns specifics objects attribute", () => {
            expect(
                ArrayUtils.mapAttribute(
                    [
                        { id: 1, title: "Scra" },
                        { id: 2, title: "Jibidus" },
                    ],
                    "id",
                ),
            ).toStrictEqual([1, 2]);
        });

        it("returns empty array when no element match with given attribute", () => {
            expect(ArrayUtils.mapAttribute([{ id: 1, title: "Scra" }], "not_exist")).toStrictEqual(
                [],
            );
        });

        it("returns empty array when array is empty", () => {
            expect(ArrayUtils.mapAttribute([], "id")).toStrictEqual([]);
        });
    });

    describe("unique", () => {
        describe("with two objects with same references", () => {
            const object = { id: 1, title: "Scra" };

            it("identifies double", () => {
                expect(ArrayUtils.unique([object, object])).toStrictEqual([object]);
            });
        });

        describe("with two objects with same content, but different references", () => {
            const object1 = { id: 1, title: "title" };
            const object2 = { id: 1, title: "title" };

            it("does not identify double", () => {
                expect(ArrayUtils.unique([object1, object2])).toStrictEqual([object1, object2]);
            });
        });

        describe("when they are numbers", () => {
            it("returns unique values", () => {
                expect(ArrayUtils.unique([1, 1, 2])).toStrictEqual([1, 2]);
            });
        });

        describe("when they are booleans", () => {
            it("returns unique values", () => {
                expect(ArrayUtils.unique([true, true, false])).toStrictEqual([true, false]);
            });
        });

        describe("when they are string", () => {
            it("returns unique values", () => {
                expect(
                    ArrayUtils.unique(["unique string", "unique string", "other string"]),
                ).toStrictEqual(["unique string", "other string"]);
            });
        });

        describe("when array is empty", () => {
            it("returns empty array", () => {
                expect(ArrayUtils.unique([])).toStrictEqual([]);
            });
        });

        describe("when they are null values", () => {
            it("returns unique null values", () => {
                expect(ArrayUtils.unique([null, null])).toStrictEqual([null]);
            });
        });
    });

    describe("uniqueByAttribute", () => {
        describe("with two objects with same references", () => {
            const object = { id: 1, title: "Scra" };

            it("identifies double", () => {
                expect(ArrayUtils.uniqueByAttribute([object, object], "id")).toStrictEqual([
                    object,
                ]);
            });
        });

        describe("with two objects with same content, but different references", () => {
            const object1 = { id: 1, title: "title" };
            const object2 = { id: 1, title: "title" };

            it("identifies double", () => {
                expect(ArrayUtils.uniqueByAttribute([object1, object2], "id")).toStrictEqual([
                    object1,
                ]);
            });
        });

        describe("with two objects with different attributes", () => {
            const object1 = { id: 1, title: "title" };
            const object2 = { id: 2, title: "title" };

            it("returns identical array", () => {
                expect(ArrayUtils.uniqueByAttribute([object1, object2], "id")).toStrictEqual([
                    object1,
                    object2,
                ]);
            });
        });
    });
});
