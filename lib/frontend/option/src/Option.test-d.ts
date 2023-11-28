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

import { describe, expectTypeOf, it } from "vitest";
import { Option } from "./Option";

type CustomType = {
    readonly property: string;
};

const CustomType = (property: string): CustomType => ({ property });

describe(`Option type`, () => {
    it(`apply() has correct type for its callback`, () => {
        const itCouldReturnNothing = (): Option<CustomType> => Option.nothing();

        expectTypeOf(
            itCouldReturnNothing().apply((received_value) => {
                expectTypeOf(received_value).toMatchTypeOf<CustomType>();
            }),
        ).toBeVoid();
    });

    describe(`andThen()`, () => {
        it(`has correct type for its callback`, () => {
            const itCouldReturnNothing = (): Option<number> => Option.nothing();

            const return_value = itCouldReturnNothing().andThen((received_value) => {
                expectTypeOf(received_value).toBeNumber();
                return Option.fromValue(789);
            });

            expectTypeOf(return_value).toMatchTypeOf<Option<number>>();
        });

        it(`can map to a different type than the Option's Initial type`, () => {
            const itCouldReturnNothing = (): Option<number> => Option.fromValue(49);

            const return_value = itCouldReturnNothing().andThen(() =>
                Option.fromValue(CustomType("scrawk")),
            );

            expectTypeOf(return_value).toMatchTypeOf<Option<CustomType>>();
        });

        it(`can map Some to None`, () => {
            const itCouldReturnNothing = (): Option<string> => Option.fromValue("forkable");

            const return_value = itCouldReturnNothing().andThen(() => Option.nothing<number>());

            expectTypeOf(return_value).toMatchTypeOf<Option<number>>();
        });
    });

    describe(`map()`, () => {
        it(`has correct type for its callback`, () => {
            const itCouldReturnNothing = (): Option<string> => Option.nothing();

            const return_value = itCouldReturnNothing().map((received_value) => {
                expectTypeOf(received_value).toBeString();
                return "mapped";
            });

            expectTypeOf(return_value).toMatchTypeOf<Option<string>>();
        });

        it(`can map to a different type than the Option's initial type`, () => {
            const itCouldReturnNothing = (): Option<number> => Option.fromValue(123);

            const return_value = itCouldReturnNothing().map(() => {
                return new Set(["one", "two", "three"]);
            });

            expectTypeOf(return_value).toMatchTypeOf<Option<Set<string>>>();
        });
    });

    describe(`mapOr()`, () => {
        it(`has correct type for its callback`, () => {
            const itCouldReturnNothing = (): Option<string> => Option.nothing();

            const return_value = itCouldReturnNothing().mapOr((received_value) => {
                expectTypeOf(received_value).toBeString();
                return "mapped";
            }, "default");

            expectTypeOf(return_value).toBeString();
        });

        it(`can map to a different type than the Option's initial type`, () => {
            const itCouldReturnNothing = (): Option<string> => Option.fromValue("33");

            const return_value = itCouldReturnNothing().mapOr((received_value) => {
                return Number.parseInt(received_value, 10) + 10;
            }, "default");
            expectTypeOf(return_value).toMatchTypeOf<number | string>();
        });

        it(`can return a different type of default value than the mapped type or the Option's initial type`, () => {
            const itCouldReturnNothing = (): Option<string> => Option.nothing();

            const return_value = itCouldReturnNothing().mapOr((received_value) => {
                return received_value === "argue" ? 994 : 271;
            }, CustomType("default"));
            expectTypeOf(return_value).toMatchTypeOf<number | CustomType>();
        });
    });

    describe(`match()`, () => {
        it(`has correct type for its callback`, () => {
            const itCouldReturnNothing = (): Option<string> => Option.nothing();

            const return_value = itCouldReturnNothing().match(
                (value) => {
                    expectTypeOf(value).toBeString();
                    return "value";
                },
                () => {
                    return "default";
                },
            );

            expectTypeOf(return_value).toBeString();
        });

        it(`can return to a different type than the Option's original type`, () => {
            const itCouldReturnNothing = (): Option<string> => Option.fromValue("33");

            const return_value = itCouldReturnNothing().match(
                (received_value) => {
                    return Number.parseInt(received_value, 10) + 10;
                },
                () => 42,
            );
            expectTypeOf(return_value).toBeNumber();
        });
    });

    describe(`unwrapOr()`, () => {
        it(`can return a different type of default value than the Option's initial type`, () => {
            const itCouldReturnNothing = (): Option<number> => Option.nothing();

            expectTypeOf(itCouldReturnNothing().unwrapOr(CustomType("default"))).toMatchTypeOf<
                number | CustomType
            >();
        });
    });

    describe(`fromNullable()`, () => {
        type ReturnType = "null" | "undefined" | "value";
        const getNullable = (return_type: ReturnType): string | null | undefined =>
            return_type === "null" ? null : return_type === "undefined" ? undefined : "unkey";

        it(`returns an Option with the generic type of its argument`, () => {
            const option_from_null = Option.fromNullable(getNullable("null"));
            expectTypeOf(option_from_null).toMatchTypeOf<Option<string>>();

            const option_from_undefined = Option.fromNullable(getNullable("undefined"));
            expectTypeOf(option_from_undefined).toMatchTypeOf<Option<string>>();

            const option_from_value = Option.fromNullable(getNullable("value"));
            expectTypeOf(option_from_value).toMatchTypeOf<Option<string>>();
        });
    });
});
