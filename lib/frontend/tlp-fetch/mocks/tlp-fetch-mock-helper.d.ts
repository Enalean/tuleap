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
 */

import type { SpyInstance } from "vitest";
import type { get, recursiveGet, options, head, post, put, del, patch } from "../src/fetch-wrapper";

interface MockFetchSuccessOptions {
    headers?: Partial<Headers>;
    return_json?: unknown;
}

interface MockFetchErrorOptions {
    status?: number;
    statusText?: string;
    error_json?: Record<string, unknown>;
}

type MockedTlpFetchFunction<TypeOfArrayItem> =
    | jest.SpyInstance<Promise<Response | Array<TypeOfArrayItem>>>
    | SpyInstance<Parameters<typeof get>, Promise<Response>>
    | SpyInstance<Parameters<typeof options>, Promise<Response>>
    | SpyInstance<Parameters<typeof head>, Promise<Response>>
    | SpyInstance<Parameters<typeof post>, Promise<Response>>
    | SpyInstance<Parameters<typeof put>, Promise<Response>>
    | SpyInstance<Parameters<typeof del>, Promise<Response>>
    | SpyInstance<Parameters<typeof patch>, Promise<Response>>
    | SpyInstance<Parameters<typeof recursiveGet>, Promise<Array<TypeOfArrayItem>>>;

export function mockFetchSuccess<TypeOfArrayItem>(
    mocked_function: MockedTlpFetchFunction<TypeOfArrayItem>,
    options?: MockFetchSuccessOptions,
): void;
export function mockFetchError<TypeOfArrayItem>(
    mocked_function: MockedTlpFetchFunction<TypeOfArrayItem>,
    options?: MockFetchErrorOptions,
): void;
