/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import { default_state } from "./state";
import type { RootState, DryRunState, Tracker, Project } from "./types";

const initial_state = { ...default_state };

export type RootMutations = {
    resetProjectLoading(state: RootState): void;
    saveSelectedProjectId(state: RootState, project_id: number): void;
    loadingTrackersAfterProjectSelected(state: RootState, project_id: number): void;
    resetTrackersLoading(state: RootState): void;
    saveProjects(state: RootState, projects: Project[]): void;
    saveTrackers(state: RootState, trackers: Tracker[]): void;
    saveSelectedTrackerId(state: RootState, tracker_id: number | null): void;
    hasProcessedDryRun(state: RootState, dry_run_state: DryRunState): void;
    resetError(state: RootState): void;
    setErrorMessage(state: RootState, error_message: string): void;
    switchToProcessingMove(state: RootState): void;
    resetProcessingMove(state: RootState): void;
    resetState(state: RootState): void;
    blockArtifactMove(state: RootState): void;
};

export const resetProjectLoading = (state: RootState): void => {
    state.is_loading_initial = false;
};

export const saveSelectedProjectId = (state: RootState, project_id: number): void => {
    state.selected_project_id = project_id;
};

export const loadingTrackersAfterProjectSelected = (state: RootState, project_id: number): void => {
    state.are_trackers_loading = true;
    state.selected_project_id = project_id;
    state.trackers = [];
    state.selected_tracker_id = null;
    state.has_processed_dry_run = false;
};

export const resetTrackersLoading = (state: RootState): void => {
    state.are_trackers_loading = false;
};

export const saveProjects = (state: RootState, projects: Project[]): void => {
    state.projects = projects;
};

export const saveTrackers = (state: RootState, trackers: Tracker[]): void => {
    state.trackers = trackers;
};

export const saveSelectedTrackerId = (state: RootState, tracker_id: number | null): void => {
    state.selected_tracker_id = tracker_id;
    state.has_processed_dry_run = false;
    state.error_message = "";
    state.is_move_possible = true;
};

export const hasProcessedDryRun = (state: RootState, dry_run_state: DryRunState): void => {
    state.dry_run_fields = dry_run_state;
    state.has_processed_dry_run = true;
};

export const resetError = (state: RootState): void => {
    state.error_message = "";
};

export const setErrorMessage = (state: RootState, error_message: string): void => {
    state.error_message = error_message;
};

export const switchToProcessingMove = (state: RootState): void => {
    state.is_processing_move = true;
};

export const resetProcessingMove = (state: RootState): void => {
    state.is_processing_move = false;
};

export const resetState = (state: RootState): void => {
    Object.assign(state, initial_state);
};

export const blockArtifactMove = (state: RootState): void => {
    state.is_move_possible = false;
};
