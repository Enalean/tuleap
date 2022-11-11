/**
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

import { describe, it, expect } from "vitest";
import { usePromise } from "./use-promise";
import { nextTick } from "vue";

describe("use-promise", () => {
    it("is in processing state when retrieving data", () => {
        const { promise } = buildPromise();
        const { is_processing, data, error } = usePromise(null, promise);

        expect(is_processing.value).toBe(true);
        expect(data.value).toBeNull();
        expect(error.value).toBeNull();
    });

    it("moves out the processing state once data has been retrieved", async () => {
        const { promise, resolve } = buildPromise();
        const { is_processing, data, error } = usePromise("", promise);

        resolve("success");

        await movesEnoughTicksToCompletelyResolve();

        expect(is_processing.value).toBe(false);
        expect(data.value).toBe("success");
        expect(error.value).toBeNull();
    });

    it("moves out the processing state once an error has been encountered", async () => {
        const { promise, reject } = buildPromise();
        const { is_processing, data, error } = usePromise("", promise);

        const expected_error = new Error("Something bad");

        reject(expected_error);

        await movesEnoughTicksToCompletelyResolve();

        expect(is_processing.value).toBe(false);
        expect(data.value).toBe("");
        expect(error.value).toBe(expected_error);
    });
});

function buildPromise(): {
    promise: Promise<unknown>;
    resolve: (arg: unknown) => void;
    reject: (arg: unknown) => void;
} {
    const default_do_nothing = (): void => {
        // Expect to do nothing by default
    };
    let returned_resolve: (arg: unknown) => void = default_do_nothing;
    let returned_reject: (arg: unknown) => void = default_do_nothing;
    const promise = new Promise((resolve, reject) => {
        returned_resolve = resolve;
        returned_reject = reject;
    });
    return {
        promise,
        resolve: returned_resolve,
        reject: returned_reject,
    };
}

async function movesEnoughTicksToCompletelyResolve(): Promise<void> {
    await nextTick();
    await nextTick();
}
