<!--
  - Copyright (c) Enalean, 2018 - present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="cross-tracker-selector">
        <div
            class="tlp-form-element"
            v-bind:class="{ 'tlp-form-element-disabled': is_project_select_disabled }"
            data-test="cross-tracker-selector-global"
        >
            <label class="tlp-label" for="project">
                <translate>Project</translate>
                <i class="fa fa-asterisk"></i>
            </label>
            <select
                class="cross-tracker-selector-project-input tlp-select"
                id="project"
                name="project"
                v-bind:disabled="is_project_select_disabled"
                v-model="selected_project"
                data-test="cross-tracker-selector-project"
            >
                <option v-for="project of projects" v-bind:value="project" v-bind:key="project.id">
                    {{ project.label }}
                </option>
            </select>
        </div>
        <div
            class="tlp-form-element"
            v-bind:class="{ 'tlp-form-element-disabled': is_tracker_select_disabled }"
        >
            <label class="tlp-label" for="tracker">
                <translate>Tracker</translate>
                <i class="fa fa-asterisk"></i>
            </label>
            <div class="tlp-form-element tlp-form-element-append">
                <select
                    class="cross-tracker-selector-tracker-input tlp-select"
                    id="tracker"
                    name="tracker"
                    v-bind:disabled="is_tracker_select_disabled"
                    v-model="selected_tracker"
                    data-test="cross-tracker-selector-tracker"
                >
                    <option
                        v-bind:value="null"
                        class="cross-tracker-please-choose-option"
                        v-translate
                    >
                        Please choose...
                    </option>
                    <option
                        v-for="tracker of tracker_options"
                        v-bind:value="{ id: tracker.id, label: tracker.label }"
                        v-bind:disabled="tracker.disabled"
                        v-bind:key="tracker.id"
                    >
                        {{ tracker.label }}
                    </option>
                </select>
                <button
                    type="button"
                    class="tlp-append tlp-button-primary tlp-button-outline"
                    v-bind:disabled="is_add_button_disabled"
                    v-on:click="addTrackerToSelection"
                    data-test="cross-tracker-selector-tracker-button"
                >
                    <i
                        v-if="is_loader_shown"
                        class="tlp-button-icon fas fa-circle-notch fa-spin"
                        data-test="tracker-loader"
                    ></i>
                    <i v-else class="tlp-button-icon fa fa-plus"></i>
                    <translate>Add</translate>
                </button>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Component, Prop, Watch } from "vue-property-decorator";
import { getSortedProjectsIAmMemberOf } from "./projects-cache";
import { getTrackersOfProject } from "../api/rest-querier";
import type { ProjectInfo, SelectedTracker, TrackerInfo } from "../type";

type TrackerSelectOption = TrackerInfo & {
    disabled: boolean;
};

@Component({})
export default class TrackerSelection extends Vue {
    @Prop({ required: true })
    private readonly selectedTrackers!: SelectedTracker[];

    private trackers: TrackerInfo[] = [];

    selected_project: ProjectInfo | null = null;
    selected_tracker: TrackerInfo | null = null;
    projects: ProjectInfo[] = [];
    is_loader_shown = false;

    get is_project_select_disabled(): boolean {
        return this.projects.length === 0;
    }
    get is_tracker_select_disabled(): boolean {
        return this.trackers.length === 0;
    }
    get is_add_button_disabled(): boolean {
        return this.selected_tracker === null;
    }

    get tracker_options(): TrackerSelectOption[] {
        return this.trackers.map(({ id, label }) => {
            const is_already_selected = this.selectedTrackers.find(
                ({ tracker_id }) => tracker_id === id,
            );
            return {
                id,
                label,
                disabled: is_already_selected !== undefined,
            };
        });
    }

    @Watch("selected_project")
    selected_project_value(new_value: TrackerInfo | null) {
        this.selected_tracker = null;
        this.trackers = [];
        if (new_value) {
            this.loadTrackers(new_value.id);
        }
    }

    mounted(): void {
        this.loadProjects();
    }

    async loadProjects(): Promise<void> {
        this.is_loader_shown = true;
        try {
            this.projects = await getSortedProjectsIAmMemberOf();

            this.selected_project = this.projects[0];
        } catch (error) {
            this.$store.commit(
                "setErrorMessage",
                this.$gettext("Error while fetching the list of projects you are member of"),
            );
        } finally {
            this.is_loader_shown = false;
        }
    }

    async loadTrackers(project_id: number): Promise<void> {
        this.is_loader_shown = true;
        try {
            this.trackers = await getTrackersOfProject(project_id);
        } catch (error) {
            this.$store.commit(
                "setErrorMessage",
                this.$gettext("Error while fetching the list of trackers of this project"),
            );
        } finally {
            this.is_loader_shown = false;
        }
    }

    addTrackerToSelection(): void {
        this.$emit("tracker-added", {
            selected_project: this.selected_project,
            selected_tracker: this.selected_tracker,
        });
        this.selected_tracker = null;
    }
}
</script>
