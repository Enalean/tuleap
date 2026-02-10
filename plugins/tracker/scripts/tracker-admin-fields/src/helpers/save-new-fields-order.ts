/*
 * Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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

import { type ResultAsync } from "neverthrow";
import { type Fault } from "@tuleap/fault";
import { uri, patchJSON } from "@tuleap/fetch-result";

export type MoveFieldsAPIRequestParams = {
    field_id: number;
    parent_id: number | null;
    next_sibling_id: number | null;
};

export const saveNewFieldsOrder = (move: MoveFieldsAPIRequestParams): ResultAsync<null, Fault> =>
    patchJSON(uri`/api/v1/tracker_fields/${move.field_id}`, {
        move: {
            parent_id: move.parent_id,
            next_sibling_id: move.next_sibling_id,
        },
    });
