/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
import { getJSON, uri } from "@tuleap/fetch-result";

export type FolderStatistics = {
    size: string;
    count: number;
    types: Array<NumberOfItemsByType>;
};

export type NumberOfItemsByType = {
    type_name: string;
    count: number;
};

export function getStatistics(item_id: number): ResultAsync<FolderStatistics, Fault> {
    return getJSON<FolderStatistics>(uri`/plugins/document/${item_id}/statistics`);
}
