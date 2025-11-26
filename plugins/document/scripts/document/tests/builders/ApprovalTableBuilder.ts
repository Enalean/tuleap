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
import { UserBuilder } from "./UserBuilder";
import type { ApprovalTable } from "../../src/type";

export class ApprovalTableBuilder {
    private readonly id: number;
    private version_number: number | null = null;
    private version_label: string = "";
    private notification_type: string = "";
    private description: string = "";
    private approval_state: string = "";

    constructor(id: number) {
        this.id = id;
    }

    public withVersionNumber(version_number: number): this {
        this.version_number = version_number;
        return this;
    }

    public withVersionLabel(version_label: string): this {
        this.version_label = version_label;
        return this;
    }

    public withNotificationType(notification_type: string): this {
        this.notification_type = notification_type;
        return this;
    }

    public withDescription(description: string): this {
        this.description = description;
        return this;
    }

    public withApprovalState(approval_state: string): this {
        this.approval_state = approval_state;
        return this;
    }

    public build(): ApprovalTable {
        return {
            id: this.id,
            table_owner: new UserBuilder(102).build(),
            approval_state: this.approval_state,
            approval_request_date: "",
            has_been_approved: false,
            version_number: this.version_number,
            version_label: this.version_label,
            notification_type: this.notification_type,
            is_closed: false,
            description: this.description,
            reviewers: [],
        };
    }
}
