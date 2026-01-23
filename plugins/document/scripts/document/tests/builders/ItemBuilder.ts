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
import type { ApprovableDocument, ApprovalTable, Item, Property, User } from "../../src/type";
import { UserBuilder } from "./UserBuilder";

export class ItemBuilder {
    private readonly id: number;
    private parent_id: number | null = null;
    private type: string = "folder";
    private title: string = "";
    private description: string = "";
    private level: number = 0;
    private owner: User = new UserBuilder(101).build();
    private status: string = "";
    private properties: Array<Property> = [];
    private approval_table: ApprovalTable | null = null;
    private user_can_write: boolean = false;
    private is_approval_table_enabled: boolean = false;
    private item_icon: string = "";

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

    public withIcon(icon: string): this {
        this.item_icon = icon;
        return this;
    }

    public withDescription(description: string): this {
        this.description = description;
        return this;
    }

    public withLevel(level: number): this {
        this.level = level;
        return this;
    }

    public withOwner(owner: User): this {
        this.owner = owner;
        return this;
    }

    public withStatus(status: string): this {
        this.status = status;
        return this;
    }

    public withProperties(properties: Array<Property>): this {
        this.properties = properties;
        return this;
    }

    public withApprovalTable(approval_table: ApprovalTable): this {
        this.approval_table = approval_table;
        return this;
    }

    public withApprovalTableEnabled(enabled: boolean): this {
        this.is_approval_table_enabled = enabled;
        return this;
    }

    public withUserCanWrite(user_can_write: boolean): this {
        this.user_can_write = user_can_write;
        return this;
    }

    public build(): Item {
        return {
            can_user_manage: false,
            creation_date: "",
            description: this.description,
            id: this.id,
            last_update_date: "",
            lock_info: null,
            move_uri: "",
            obsolescence_date: null,
            owner: this.owner,
            parent_id: this.parent_id,
            post_processed_description: "",
            properties: this.properties,
            status: this.status,
            title: this.title,
            type: this.type,
            user_can_delete: false,
            user_can_write: this.user_can_write,
            level: this.level,
            item_icon: this.item_icon,
        };
    }

    public buildApprovableDocument(): Item & ApprovableDocument {
        return {
            can_user_manage: false,
            creation_date: "",
            description: this.description,
            id: this.id,
            last_update_date: "",
            lock_info: null,
            move_uri: "",
            obsolescence_date: null,
            owner: this.owner,
            parent_id: this.parent_id,
            post_processed_description: "",
            properties: this.properties,
            status: this.status,
            title: this.title,
            type: this.type,
            user_can_delete: false,
            user_can_write: this.user_can_write,
            level: this.level,
            has_approval_table: this.approval_table !== null,
            approval_table: this.approval_table,
            is_approval_table_enabled: this.is_approval_table_enabled,
            item_icon: this.item_icon,
        };
    }
}
