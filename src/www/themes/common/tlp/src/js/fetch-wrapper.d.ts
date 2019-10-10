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

interface FetchWrapperError extends Error {
    response: Response;
}
interface FetchWrapperParameter {
    [key: string]: string | number;
}
export function get(
    url: string,
    init?: RequestInit & { method?: "GET"; params?: FetchWrapperParameter }
): Promise<Response>;
interface RecursiveGetLimitParameters {
    limit?: number;
    offset?: number;
}
interface RecursiveGetInit<Y, T> {
    params?: FetchWrapperParameter & RecursiveGetLimitParameters;
    getCollectionCallback?: (json: Y) => Array<T>;
}
export function recursiveGet<Y, T>(url: string, init?: RecursiveGetInit<Y, T>): Promise<Array<T>>;
export function put(url: string, init?: RequestInit & { method?: "PUT" }): Promise<Response>;
export function patch(url: string, init?: RequestInit & { method?: "PATCH" }): Promise<Response>;
export function post(url: string, init?: RequestInit & { method?: "POST" }): Promise<Response>;
export function del(url: string, init?: RequestInit & { method?: "DELETE" }): Promise<Response>;
export function options(
    url: string,
    init?: RequestInit & { method?: "OPTIONS" }
): Promise<Response>;
