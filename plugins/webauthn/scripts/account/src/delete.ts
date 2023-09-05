/**
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
import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { post, uri } from "@tuleap/fetch-result";

export function deleteKey(key_id: string, csrf_token: string): ResultAsync<null, Fault> {
    return post(
        uri`/webauthn/key/delete`,
        {},
        {
            key_id: key_id,
            csrf_token: csrf_token,
        },
    ).map(() => null);
}
