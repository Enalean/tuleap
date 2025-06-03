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
import type { Item } from "../../src/type";

export class ItemBuilder {
    private readonly id: number;
    private parent_id: number | null = null;
    private type: string = "folder";
    private title: string = "";
    private level: number = 0;

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

    public withLevel(level: number): this {
        this.level = level;
        return this;
    }

    public build(): Item {
        return {
            can_user_manage: false,
            creation_date: "",
            description: "",
            id: this.id,
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
            post_processed_description: "",
            properties: [],
            status: "",
            title: this.title,
            type: this.type,
            user_can_delete: false,
            user_can_write: false,
            level: this.level,
        };
    }
}
