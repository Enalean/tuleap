/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 *
 */

export interface User {
    readonly has_avatar: boolean;
    readonly avatar_url: string;
    readonly is_anonymous: boolean;
    readonly display_name: string;
    readonly user_url: string;
}

export interface UserPreferences {
    readonly user_locale: string;
    readonly user_timezone: string;
    readonly format: string;
}

export interface Artifact {
    readonly id: number;
    readonly title: string;
    readonly tracker_name: string;
    readonly description: string;
    readonly status: string;
}
export interface BaselineArtifact {
    readonly id: number;
    readonly title: string | null;
    readonly tracker_name: string;
    readonly description: string | null;
    readonly status: string | null;
    readonly tracker_id: number;
    readonly linked_artifact_ids: number[];
}

export interface Tracker {
    readonly item_name: string;
    readonly color_name: string;
}

export interface Baseline {
    readonly id: number;
    readonly name: string;
    readonly author_id: number;
    readonly snapshot_date: string;
    readonly artifact_id: number;
}

export interface Milestone {
    readonly id: number;
    readonly description: string;
    readonly label: string;
}

export interface Comparison {
    readonly id: number;
    readonly name: string | null;
    readonly comment: string | null;
    readonly base_baseline_id: number;
    readonly compared_to_baseline_id: number;
    readonly author_id: number;
    readonly creation_date: string;
}
