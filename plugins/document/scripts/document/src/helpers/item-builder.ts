/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import type { FakeItem } from "../type";
import { TYPE_FILE } from "../constants";

export function buildFakeItem(): FakeItem {
    return {
        id: 0,
        title: "",
        parent_id: 0,
        type: TYPE_FILE,
        file_type: "",
        is_uploading: true,
        progress: 0,
        upload_error: null,
        is_uploading_in_collapsed_folder: false,
        is_uploading_new_version: false,
        is_approval_table_enabled: false,
        has_approval_table: false,
        approval_table: null,
    };
}
