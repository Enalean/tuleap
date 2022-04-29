/*
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

export type GetMethod = "GET";
export type HeadMethod = "HEAD";
export type OptionsMethod = "OPTIONS";
export type PutMethod = "PUT";
export type PatchMethod = "PATCH";
export type PostMethod = "POST";
export type DeleteMethod = "DELETE";

export type SupportedHTTPMethod =
    | GetMethod
    | HeadMethod
    | OptionsMethod
    | PutMethod
    | PatchMethod
    | PostMethod
    | DeleteMethod;

export const GET_METHOD: GetMethod = "GET";
export const HEAD_METHOD: HeadMethod = "HEAD";
export const OPTIONS_METHOD: OptionsMethod = "OPTIONS";
export const POST_METHOD: PostMethod = "POST";
export const PUT_METHOD: PutMethod = "PUT";
export const PATCH_METHOD: PatchMethod = "PATCH";
export const DELETE_METHOD: DeleteMethod = "DELETE";
