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

import { defineStore } from "pinia";
import { getProjectList, getTrackerList } from "../api/rest-querier";
import { useDryRunStore } from "./dry-run";
import { useModalStore } from "./modal";
import type { Project, Tracker } from "../api/types";

export type ProjectsAndTrackersStore = {
    are_projects_loading: boolean;
    projects: Project[];
    selected_project_id: number | null;
    are_trackers_loading: boolean;
    trackers: Tracker[];
    selected_tracker_id: number | null;
};

export const useSelectorsStore = defineStore("selectors", {
    state: (): ProjectsAndTrackersStore => ({
        are_projects_loading: true,
        projects: [],
        selected_project_id: null,
        are_trackers_loading: false,
        trackers: [],
        selected_tracker_id: null,
    }),
    actions: {
        startLoadingTrackers(): void {
            this.are_trackers_loading = true;
        },
        stopLoadingTrackers(): void {
            this.are_trackers_loading = false;
        },
        startLoadingProjects(): void {
            this.are_projects_loading = true;
        },
        stopLoadingProjects(): void {
            this.are_projects_loading = false;
        },
        saveSelectedProjectId(project_id: number): void {
            this.selected_project_id = project_id;
        },
        saveSelectedTrackerId(tracker_id: number | null): void {
            this.selected_tracker_id = tracker_id;

            useDryRunStore().$reset();
        },
        loadTrackerList(project_id: number): Promise<void> {
            this.startLoadingTrackers();

            const modal_store = useModalStore();
            modal_store.resetError();

            useDryRunStore().$reset();

            this.selected_project_id = project_id;
            this.trackers = [];
            this.selected_tracker_id = null;

            return getTrackerList(project_id)
                .match((tracker_list) => {
                    this.trackers = [...tracker_list];
                }, modal_store.setErrorMessage)
                .finally(() => this.stopLoadingTrackers());
        },
        loadProjectList(): Promise<void> {
            this.startLoadingProjects();

            return getProjectList()
                .match((project_list) => {
                    this.projects = [...project_list].sort((a: Project, b: Project) =>
                        a.label.localeCompare(b.label)
                    );
                }, useModalStore().setErrorMessage)
                .finally(() => this.stopLoadingProjects());
        },
    },
});
