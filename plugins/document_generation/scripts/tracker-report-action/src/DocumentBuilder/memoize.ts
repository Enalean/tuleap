/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

function serializeArguments(...args: unknown[]): string {
    return args.map((arg) => String(arg)).join("|");
}

type Fn<Args extends unknown[], Result> = (...args: Args) => Result;

type Either<Result> = { result: Result } | { err: unknown };

export function memoize<Args extends unknown[], Result>(fn: Fn<Args, Result>): Fn<Args, Result> {
    const cache: { [key: string]: Either<Result> } = {};
    return (...args: Args): Result => {
        const key = serializeArguments(...args);
        if (!(key in cache)) {
            try {
                cache[key] = { result: fn(...args) };
            } catch (err) {
                cache[key] = { err };
            }
        }

        const cache_value = cache[key];
        if ("err" in cache_value) {
            throw cache_value.err;
        }
        return cache_value.result;
    };
}
