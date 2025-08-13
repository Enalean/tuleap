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
import type { FakeItem } from "../../src/type";

export class FakeItemBuilder {
    private readonly id: number;
    private parent_id: number | null = null;
    private type: string = "file";
    private title: string = "";
    private file_type: string = "";

    constructor(id: number) {
        this.id = id;
    }

    public withParentId(parent_id: number): this {
        this.parent_id = parent_id;
        return this;
    }

    public withType(type: string): this {
        this.type = type;
        return this;
    }

    public withTitle(title: string): this {
        this.title = title;
        return this;
    }

    public withFileType(file_type: string): this {
        this.file_type = file_type;
        return this;
    }

    public build(): FakeItem {
        return {
            approval_table: null,
            file_type: this.file_type,
            has_approval_table: false,
            id: this.id,
            is_approval_table_enabled: false,
            is_uploading: true,
            is_uploading_in_collapsed_folder: false,
            is_uploading_new_version: false,
            parent_id: this.parent_id,
            progress: null,
            title: this.title,
            type: this.type,
            upload_error: null,
        };
    }
}
