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

export type BurnupMode = "count" | "effort";
export const COUNT: BurnupMode = "count";
export const EFFORT: BurnupMode = "effort";

export interface MilestoneData {
    label: string;
    id: number;
    semantic_status: "open" | "closed";
    capacity: number | null;
    start_date: string | null;
    end_date: string | null;
    planning: {
        id: string;
    };
    number_days_until_end: number | null;
    number_days_since_start: number | null;
    remaining_effort: number | null;
    initial_effort: number | null;
    total_sprint: number | null;
    total_closed_sprint: number | null;
    open_sprints: MilestoneData[] | null;
    burndown_data: BurndownData | null;
    burnup_data: BurnupData | null;
    post_processed_description: string | null;
    resources: MilestoneResourcesData;
    number_of_artifact_by_trackers: TrackerNumberArtifacts[];
    campaign: TestManagementCampaign | null;
    artifact: Artifact;
}

export interface Artifact {
    id: number;
    tracker: Tracker;
    uri: string;
}

export interface Tracker {
    id: number;
    label: string;
    project: Project;
    uri: string;
}

export interface Project {
    id: number;
    icon: string;
    label: string;
    uri: string;
}

export interface MilestoneResourcesData {
    content: {
        accept: {
            trackers: TrackerProjectWithoutColor[];
        };
    };
    milestones: {
        accept: {
            trackers: TrackerProjectLabel[];
        };
    };
    additional_panes: Pane[];
    burndown: null | { uri: string };
    cardwall: null | { uri: string };
}

export interface Pane {
    title: string;
    icon_name: string;
    uri: string;
    identifier: string;
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

export interface TrackerProjectLabel {
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
export interface State {
    project_id: number;
    project_name: string;
    nb_backlog_items: number;
    nb_upcoming_releases: number;
    error_message: string | null;
    offset: number;
    limit: number;
    is_loading: boolean;
    current_milestones: MilestoneData[];
    trackers_agile_dashboard: TrackerAgileDashboard[];
    label_tracker_planning: string;
    is_timeframe_duration: boolean;
    label_start_date: string;
    label_timeframe: string;
    user_can_view_sub_milestones_planning: boolean;
    burnup_mode: BurnupMode;
    nb_past_releases: number;
    last_release: MilestoneData | null;
}

export interface ParametersRequestWithId {
    project_id: number;
    limit: number;
    offset: number;
}

export interface ParametersRequestWithoutId {
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
    points_with_date: Array<PointsWithDateForBurndown>;
    label: string | null;
}

export interface BurnupData {
    start_date: string;
    duration: number | null;
    capacity: number | null;
    is_under_calculation: boolean;
    opening_days: Array<number>;
    points_with_date: Array<PointsWithDateForBurnup>;
    points_with_date_count_elements: Array<PointsCountElements>;
    label: string | null;
}

export interface PointsCountElements {
    date: string;
    closed_elements: number;
    total_elements: number;
}

export interface PointsWithDateForBurndown {
    date: string;
    remaining_effort: number | null;
}

export interface PointsWithDateForBurnup {
    date: string;
    team_effort: number;
    total_effort: number;
}

export interface PointsNotNullWithDate {
    date: string;
    remaining_effort: number;
}

export interface XYMinMaxCoordinates {
    x_coordinate_minimum: number;
    y_coordinate_minimum: number;
    x_coordinate_maximum: number;
    y_coordinate_maximum: number;
}

export interface XYSizeElement {
    width: number;
    height: number;
    x: number;
    y: number;
}

export interface ArtifactMilestone {
    values: [ArtifactMilestoneChartBurndown, ArtifactMilestoneChartBurnup];
}

export interface ArtifactMilestoneChartBurndown {
    value: BurndownData;
    field_id: number;
    label: string;
    type: "burndown";
}

export interface ArtifactMilestoneChartBurnup {
    value: BurnupData;
    field_id: number;
    label: string;
    type: "burnup";
}

export interface TestManagementCampaign {
    nb_of_notrun: number;
    nb_of_passed: number;
    nb_of_failed: number;
    nb_of_blocked: number;
}
