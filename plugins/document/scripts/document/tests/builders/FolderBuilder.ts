/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
import type { Folder } from "../../src/type";

export class FolderBuilder {
    private readonly id: number;
    private parent_id: number | null = null;

    constructor(id: number) {
        this.id = id;
    }

    public withParentId(parent_id: number): this {
        this.parent_id = parent_id;
        return this;
    }

    public build(): Folder {
        return {
            can_user_manage: false,
            creation_date: "",
            description: "",
            folder_properties: {
                total_size: 0,
                nb_files: 0,
            },
            id: this.id,
            is_expanded: false,
            is_uploading: false,
            is_uploading_in_collapsed_folder: false,
            is_uploading_new_version: false,
            last_update_date: "",
            lock_info: null,
            move_uri: "",
            obsolescence_date: null,
            owner: {
                id: 0,
                display_name: "",
                has_avatar: false,
                avatar_url: "",
                user_url: "",
            },
            parent_id: this.parent_id,
            permissions_for_groups: {
                apply_permissions_on_children: false,
                can_read: [],
                can_write: [],
                can_manage: [],
            },
            post_processed_description: "",
            progress: null,
            properties: [],
            status: {
                value: "",
                recursion: "",
            },
            title: "",
            type: "folder",
            updated: false,
            upload_error: null,
            user_can_delete: false,
            user_can_write: false,
        };
    }
}
