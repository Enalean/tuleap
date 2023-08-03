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

import * as mutations from "./mutations";
import type { ArtifactField, DryRunState, Project, RootState, Tracker } from "./types";
import { default_state } from "./state";

describe("mutations", () => {
    it("resetProjectLoading() should set is_loading_initial to false", () => {
        const state = { is_loading_initial: true } as RootState;

        mutations.resetProjectLoading(state);

        expect(state.is_loading_initial).toBe(false);
    });

    it("saveSelectedProjectId() should save the current project id", () => {
        const state = { selected_project_id: null } as RootState;

        mutations.saveSelectedProjectId(state, 105);

        expect(state.selected_project_id).toBe(105);
    });

    it("loadingTrackersAfterProjectSelected() should make the tracker load", () => {
        const state = {
            selected_project_id: null,
            are_trackers_loading: false,
            trackers: [{} as Tracker],
            selected_tracker_id: 10,
            has_processed_dry_run: true,
        } as RootState;

        mutations.loadingTrackersAfterProjectSelected(state, 105);

        expect(state.selected_project_id).toBe(105);
        expect(state.are_trackers_loading).toBe(true);
        expect(state.trackers).toHaveLength(0);
        expect(state.selected_tracker_id).toBeNull();
        expect(state.has_processed_dry_run).toBe(false);
    });

    it("resetTrackersLoading() should set are_trackers_loading to false", () => {
        const state = { are_trackers_loading: true } as RootState;

        mutations.resetTrackersLoading(state);

        expect(state.are_trackers_loading).toBe(false);
    });

    it("saveProjects() should save the available projects", () => {
        const state = { projects: [{} as Project] } as RootState;
        const project: Project = {
            id: 104,
            label: "Guinea pig",
        };

        mutations.saveProjects(state, [project]);
        expect(state.projects).toStrictEqual([project]);
    });

    it("saveTrackers() should save the available trackers", () => {
        const state = { trackers: [{} as Tracker] } as RootState;
        const tracker: Tracker = {
            id: 10,
            label: "Tasks",
            disabled: false,
        };

        mutations.saveTrackers(state, [tracker]);
        expect(state.trackers).toStrictEqual([tracker]);
    });

    it("saveSelectedTrackerId() should save the selected tracker id and reset the dry run state and error", () => {
        const tracker_id = 10;
        const state = {
            selected_tracker_id: null,
            has_processed_dry_run: true,
            error_message: "Oh snap!",
        } as RootState;

        mutations.saveSelectedTrackerId(state, tracker_id);

        expect(state.selected_tracker_id).toBe(tracker_id);
        expect(state.has_processed_dry_run).toBe(false);
        expect(state.error_message).toBe("");
    });

    it("hasProcessedDryRun() should store the dry run state", () => {
        const state = {
            dry_run_fields: {
                fields_not_migrated: [],
                fields_partially_migrated: [],
                fields_migrated: [],
            } as DryRunState,
            has_processed_dry_run: false,
        } as RootState;

        const dry_run_state = {
            fields_not_migrated: [{} as ArtifactField],
            fields_partially_migrated: [{} as ArtifactField],
            fields_migrated: [{} as ArtifactField],
        };

        mutations.hasProcessedDryRun(state, dry_run_state);

        expect(state.dry_run_fields).toBe(dry_run_state);
        expect(state.has_processed_dry_run).toBe(true);
    });

    it("resetError() should reset the error", () => {
        const state = { error_message: "Oh snap!" } as RootState;

        mutations.resetError(state);

        expect(state.error_message).toBe("");
    });

    it("setErrorMessage() should store the given error message", () => {
        const state = { error_message: "" } as RootState;

        mutations.setErrorMessage(state, "Oh snap!");

        expect(state.error_message).toBe("Oh snap!");
    });

    it("switchToProcessingMove() should set is_processing_move to true", () => {
        const state = { is_processing_move: false } as RootState;

        mutations.switchToProcessingMove(state);

        expect(state.is_processing_move).toBe(true);
    });

    it("resetProcessingMove() should set is_processing_move to false", () => {
        const state = { is_processing_move: true } as RootState;

        mutations.resetProcessingMove(state);

        expect(state.is_processing_move).toBe(false);
    });

    it("resetState() should replace the current state with the default one", () => {
        const state = {} as RootState;

        mutations.resetState(state);

        expect(state).toStrictEqual(default_state);
    });

    it("blockArtifactMove() should set is_move_possible to false", () => {
        const state = { is_move_possible: true } as RootState;

        mutations.blockArtifactMove(state);

        expect(state.is_move_possible).toBe(false);
    });
});
