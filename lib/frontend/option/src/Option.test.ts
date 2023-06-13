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

import { describe, expect, it, vi } from "vitest";
import { Option } from "./Option";

type CustomType = {
    readonly property: string;
};

describe(`Option`, () => {
    it.each([
        ["Some", Option.fromValue(1), true, false],
        ["None", Option.nothing(), false, true],
    ])(
        `reports correctly the type for %s`,
        (_variant, option, expected_is_value, expected_is_nothing) => {
            expect(option.isValue()).toBe(expected_is_value);
            expect(option.isNothing()).toBe(expected_is_nothing);
        }
    );

    describe(`Some`, () => {
        it(`apply() calls the given function with the inner value`, () => {
            const value = {};
            let applied_value = null;

            Option.fromValue(value).apply((received_value) => {
                applied_value = received_value;
            });

            expect(applied_value).toBe(value);
        });

        it(`andThen() calls a function that returns another Option with the inner value
            and returns the new Option`, () => {
            const value = "initial";
            let then_value = null;

            const then_option = Option.fromValue(value).andThen((received_value) => {
                then_value = received_value;
                return Option.fromValue(208);
            });

            expect(then_option.isValue()).toBe(true);
            expect(then_option.unwrapOr(null)).toBe(208);
            expect(then_value).toBe(value);
        });

        it(`andThen() can map Some to None`, () => {
            const then_option = Option.fromValue("initial").andThen(() => Option.nothing());

            expect(then_option.isNothing()).toBe(true);
        });

        it(`map() calls the given function with the inner value and returns a new Some with its result`, () => {
            const value = "initial";
            let map_value = null;

            const mapped_option = Option.fromValue(value).map((received_value) => {
                map_value = received_value;
                return "callback";
            });

            expect(mapped_option.isValue()).toBe(true);
            expect(mapped_option.unwrapOr(null)).toBe("callback");
            expect(map_value).toBe(value);
        });

        it(`mapOr() calls the given function with the inner value and returns its result`, () => {
            const value = "initial";
            let map_value = null;

            const mapped = Option.fromValue(value).mapOr((received_value) => {
                map_value = received_value;
                return "callback";
            }, "default");

            expect(mapped).toBe("callback");
            expect(map_value).toBe(value);
        });

        it(`unwrapOr() returns the inner value`, () => {
            const value = 486;
            const unwrapped_value = Option.fromValue(value).unwrapOr(null);
            expect(unwrapped_value).toBe(value);
        });
    });

    describe(`None`, () => {
        it(`apply() does nothing`, () => {
            const callback = vi.fn();
            Option.nothing<CustomType>().apply(callback);
            expect(callback).not.toHaveBeenCalled();
        });

        it(`andThen() returns a new None`, () => {
            const callback = vi.fn();
            const initial_option = Option.nothing<string>();
            const then_option = initial_option.andThen(callback);

            expect(callback).not.toHaveBeenCalled();
            expect(then_option).not.toBe(initial_option);
        });

        it(`map() returns a new None`, () => {
            const callback = vi.fn();
            const initial_option = Option.nothing<string>();
            const mapped_option = initial_option.map(callback);

            expect(callback).not.toHaveBeenCalled();
            expect(mapped_option).not.toBe(initial_option);
        });

        it(`mapOr() returns the given default value`, () => {
            const callback = vi.fn();
            const mapped = Option.nothing<string>().mapOr(callback, "default");

            expect(mapped).toBe("default");
            expect(callback).not.toHaveBeenCalled();
        });

        it(`unwrapOr() returns the given default value`, () => {
            const unwrapped_value = Option.nothing<number>().unwrapOr(null);
            expect(unwrapped_value).toBeNull();
        });
    });
});
