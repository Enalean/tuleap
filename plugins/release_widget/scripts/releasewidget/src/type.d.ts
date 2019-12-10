/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

export interface MilestoneData {
    label?: string;
    id: number;
    capacity?: number | null;
    start_date?: string | null;
    end_date?: string | null;
    planning?: {
        id: string;
    };
    number_days_until_end?: number | null;
    number_days_since_start?: number | null;
    remaining_effort?: number | null;
    initial_effort?: number | null;
    total_sprint?: number | null;
    burndown_data?: BurndownData | null;
    description?: string | null;
    resources?: {
        content: {
            accept: {
                trackers: TrackerProjectWithoutColor[];
            };
        };
        milestones: {
            accept: {
                trackers: [
                    {
                        label: string;
                    }
                ];
            };
        };
    };
    number_of_artifact_by_trackers: TrackerNumberArtifacts[];
}

export interface TrackerNumberArtifacts {
    id: number;
    label: string;
    total_artifact: number;
    color_name: string | null;
}

export interface TrackerProjectWithoutColor {
    id: number;
    label: string;
}

export interface TrackerAgileDashboard {
    id: number;
    color_name: string;
    label: string;
}

export interface MilestoneContent {
    initial_effort: number;
    artifact: {
        tracker: {
            id: number;
        };
    };
}

export interface StoreOptions {
    state: {
        project_id?: number;
        is_loading?: boolean;
        current_milestones?: Array<MilestoneData>;
        error_message?: string;
        is_browser_IE11?: boolean;
        label_tracker_planning?: string;
    };
    getters?: {
        has_rest_error?: boolean;
    };
}

export interface State {
    project_id: number | null;
    nb_backlog_items: number;
    nb_upcoming_releases: number;
    error_message: string | null;
    offset: number;
    limit: number;
    is_loading: boolean;
    current_milestones: MilestoneData[];
    trackers_agile_dashboard: TrackerAgileDashboard[];
    is_browser_IE11: boolean;
    label_tracker_planning: string;
}

export interface Context {
    state: State;
    commit: Function;
}

interface ParametersRequestWithId {
    project_id: number;
    limit: number;
    offset: number;
}

interface ParametersRequestWithoutId {
    limit: number;
    offset: number;
}

export interface BurndownData {
    start_date: string;
    duration: number | null;
    capacity: number | null;
    points: Array<number>;
    is_under_calculation: boolean;
    opening_days: Array<number>;
    points_with_date: Array<PointsWithDate>;
}

export interface PointsWithDate {
    date: string;
    remaining_effort: number | null;
}

export interface XYMinMaxCoordinates {
    x_coordinate_minimum: number;
    y_coordinate_minimum: number;
    x_coordinate_maximum: number;
    y_coordinate_maximum: number;
}
