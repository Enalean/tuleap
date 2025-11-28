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
import type { ApprovalTableReviewer } from "../../src/type";
import { UserBuilder } from "./UserBuilder";

export class ApprovalTableReviewerBuilder {
    constructor() {}

    public build(): ApprovalTableReviewer {
        return {
            user: new UserBuilder(102).build(),
            rank: 0,
            review_date: null,
            state: "not_yet",
            comment: "",
            version_id: null,
            version_name: null,
        };
    }
}
