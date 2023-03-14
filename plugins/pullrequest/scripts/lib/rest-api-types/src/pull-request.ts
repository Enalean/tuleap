/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import type { BuildStatus } from "@tuleap/plugin-pullrequest-constants";

export interface PullRequest {
    readonly id: number;
    readonly title: string;
    readonly creation_date: string;
    readonly short_stat: {
        readonly lines_added: number;
        readonly lines_removed: number;
    };
    readonly reference_src: string;
    readonly branch_src: string;
    readonly branch_dest: string;
    readonly last_build_status: BuildStatus;
    readonly last_build_date: string;
    readonly user_id: number;
    readonly repository_dest: {
        readonly clone_http_url: string;
        readonly clone_ssh_url: string;
    };
    readonly head_reference: string;
    readonly description: string;
    readonly user_can_merge: boolean;
}
