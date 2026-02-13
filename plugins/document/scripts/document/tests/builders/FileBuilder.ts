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
import type { FileProperties, ItemFile, LockInfo, User, ApprovalTable } from "../../src/type";
import { UserBuilder } from "./UserBuilder";
import { TYPE_FILE } from "../../src/constants";

export class FileBuilder {
    private readonly id: number;
    private owner: User = new UserBuilder(101).build();
    private approval_table: ApprovalTable | null = null;
    private file_properties: FileProperties = {
        file_type: "text/html",
        file_name: "a file",
        file_size: 1234,
        download_href: "plugins/document/2/1",
        open_href: null,
        version_number: 1,
    };
    private lock_info: LockInfo | null = null;

    constructor(id: number) {
        this.id = id;
    }

    withLockInfo(lock_info: LockInfo): this {
        this.lock_info = lock_info;
        return this;
    }

    withFileProperties(file_properties: FileProperties): this {
        this.file_properties = file_properties;
        return this;
    }

    public withApprovalTable(approval_table: ApprovalTable): this {
        this.approval_table = approval_table;
        return this;
    }

    public build(): ItemFile {
        return {
            can_user_manage: false,
            creation_date: "",
            description: "",
            id: this.id,
            last_update_date: "",
            lock_info: this.lock_info,
            move_uri: "",
            obsolescence_date: null,
            owner: this.owner,
            parent_id: 989876565,
            post_processed_description: "",
            properties: [],
            status: "",
            title: "",
            type: TYPE_FILE,
            user_can_delete: false,
            user_can_write: true,
            level: 0,
            has_approval_table: false,
            approval_table: this.approval_table,
            is_approval_table_enabled: false,
            item_icon: "",
            file_properties: this.file_properties,
            progress: 0,
            upload_error: "",
            is_uploading: false,
            is_uploading_new_version: false,
            is_uploading_in_collapsed_folder: false,
        };
    }
}
