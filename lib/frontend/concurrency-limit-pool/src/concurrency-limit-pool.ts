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

export async function limitConcurrencyPool<I, O>(
    max_concurrency: number,
    args: ReadonlyArray<I>,
    resolver: (arg: I) => PromiseLike<O>,
): Promise<O[]> {
    if (max_concurrency <= 0) {
        throw new Error("Concurrency limit needs to be a positive number");
    }

    const all_resolved_promise: Promise<O>[] = [];

    const executing_promises: Promise<void>[] = [];

    for (const arg of args) {
        // We voluntarily do not resolve the promise yet to control the resolution pool
        // eslint-disable-next-line require-await
        const resolved_promise = (async (): Promise<O> => resolver(arg))();
        all_resolved_promise.push(resolved_promise);

        if (max_concurrency <= args.length) {
            const promise_to_execute: Promise<void> = resolved_promise.then(() => {
                executing_promises.splice(executing_promises.indexOf(promise_to_execute), 1);
            });
            executing_promises.push(promise_to_execute);
        }
        if (executing_promises.length >= max_concurrency) {
            await Promise.race(executing_promises);
        }
    }

    return Promise.all(all_resolved_promise);
}
