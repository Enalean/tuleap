<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
  -
  -->

<template>
    <div>
        <div class="tlp-form-element">
            <label class="tlp-label" v-bind:for="title_id">
                <translate>Title</translate>
                <i class="fas fa-asterisk" aria-hidden="true"></i>
            </label>
            <input
                type="text"
                class="tlp-input"
                v-bind:id="title_id"
                name="roadmap[title]"
                v-model="title"
                required
                v-bind:placeholder="placeholder"
            />
        </div>
        <div class="tlp-form-element">
            <label class="tlp-label" v-bind:for="progress_of_id">
                <translate>Show progress of</translate>
                <i class="fas fa-asterisk" aria-hidden="true"></i>
            </label>

            <select
                class="tlp-select tlp-select-adjusted"
                v-bind:id="progress_of_id"
                name="roadmap[tracker_id]"
                v-model="user_selected_tracker_id"
                required
                data-test="tracker"
            >
                <option value="" selected disabled v-if="is_in_creation">
                    <translate>Please choose a tracker</translate>
                </option>
                <option
                    v-for="tracker of suitable_trackers"
                    v-bind:key="tracker.id"
                    v-bind:value="tracker.id"
                >
                    {{ tracker.title }}
                </option>
            </select>
        </div>
        <hr class="roadmap-widget-configuration-separator" />
        <h2 class="tlp-modal-subtitle" v-bind:class="subtitle_class">Timeframe ribbons</h2>
        <translate tag="p">
            Artifacts of the selected tracker will appear in the upper part of the Roadmap, below
            the Quarters/Months/Weeks.
        </translate>
        <translate tag="p">
            Selected trackers are expected to have continuous time (i.e. artifacts timeframe do not
            overlap).
        </translate>
        <div class="tlp-form-element">
            <label class="tlp-label" v-bind:for="lvl1_id">
                <translate>Timeframe ribbon, level 1 (eg. Release)</translate>
            </label>

            <select
                class="tlp-select tlp-select-adjusted"
                v-bind:id="lvl1_id"
                name="roadmap[lvl1_iteration_tracker_id]"
                v-model="user_selected_lvl1_iteration_tracker_id"
                data-test="lvl1-iteration-tracker"
            >
                <option value="" selected>
                    <translate>Please choose a tracker</translate>
                </option>
                <option
                    v-for="tracker of suitable_lvl1_iteration_trackers"
                    v-bind:key="tracker.id"
                    v-bind:value="tracker.id"
                >
                    {{ tracker.title }}
                </option>
            </select>
        </div>
        <div
            class="tlp-form-element"
            v-bind:class="{ 'tlp-form-element-disabled': is_lvl2_disabled }"
        >
            <label class="tlp-label" v-bind:for="lvl1_id">
                <translate>Timeframe ribbon, level 2 (eg. Sprint)</translate>
            </label>

            <select
                class="tlp-select tlp-select-adjusted"
                v-bind:id="lvl1_id"
                name="roadmap[lvl2_iteration_tracker_id]"
                v-model="user_selected_lvl2_iteration_tracker_id"
                v-bind:disabled="is_lvl2_disabled"
                data-test="lvl2-iteration-tracker"
            >
                <option value="" selected>
                    <translate>Please choose a tracker</translate>
                </option>
                <option
                    v-for="tracker of suitable_lvl2_iteration_trackers"
                    v-bind:key="tracker.id"
                    v-bind:value="tracker.id"
                >
                    {{ tracker.title }}
                </option>
            </select>
            <p class="tlp-text-info">
                <i class="far fa-life-ring" aria-hidden="true"></i>
                <translate>Level 2 is expected to be a sub-division of level 1.</translate>
            </p>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop, Watch } from "vue-property-decorator";
import type { Tracker } from "../type";

@Component
export default class App extends Vue {
    @Prop({ required: true })
    private readonly widget_id!: number;

    @Prop({ required: true })
    private title!: string;

    @Prop({ required: true })
    private readonly trackers!: Tracker[];

    @Prop({ required: true })
    private readonly selected_tracker_id!: number | "";

    @Prop({ required: true })
    private readonly selected_lvl1_iteration_tracker_id!: number | "";

    @Prop({ required: true })
    private readonly selected_lvl2_iteration_tracker_id!: number | "";

    @Prop({ required: true })
    private readonly is_in_creation!: boolean;

    private user_selected_tracker_id: number | "" = "";
    private user_selected_lvl1_iteration_tracker_id: number | "" = "";
    private user_selected_lvl2_iteration_tracker_id: number | "" = "";

    mounted(): void {
        this.user_selected_tracker_id = this.selected_tracker_id;
        this.user_selected_lvl1_iteration_tracker_id = this.selected_lvl1_iteration_tracker_id;
        this.user_selected_lvl2_iteration_tracker_id = this.selected_lvl2_iteration_tracker_id;
    }

    @Watch("user_selected_lvl1_iteration_tracker_id")
    forceLevel2ToBeEmptyIfNoLevel1(): void {
        if (!this.user_selected_lvl1_iteration_tracker_id) {
            this.user_selected_lvl2_iteration_tracker_id = "";
        }
    }

    get title_id(): string {
        return "title-" + this.widget_id;
    }

    get progress_of_id(): string {
        return "roadmap-tracker-" + this.widget_id;
    }

    get lvl1_id(): string {
        return "lvl1-" + this.widget_id;
    }

    get lvl2_id(): string {
        return "lvl2-" + this.widget_id;
    }

    get placeholder(): string {
        return this.$gettext("Roadmap");
    }

    get subtitle_class(): string {
        return this.is_in_creation ? "roadmap-widget-configuration-subtitle" : "";
    }

    get suitable_trackers(): Tracker[] {
        return this.trackers.filter(
            (tracker) =>
                tracker.id !== this.user_selected_lvl1_iteration_tracker_id &&
                tracker.id !== this.user_selected_lvl2_iteration_tracker_id
        );
    }

    get suitable_lvl1_iteration_trackers(): Tracker[] {
        return this.trackers.filter(
            (tracker) =>
                tracker.id !== this.user_selected_tracker_id &&
                tracker.id !== this.user_selected_lvl2_iteration_tracker_id
        );
    }

    get suitable_lvl2_iteration_trackers(): Tracker[] {
        return this.trackers.filter(
            (tracker) =>
                tracker.id !== this.user_selected_tracker_id &&
                tracker.id !== this.user_selected_lvl1_iteration_tracker_id
        );
    }

    get is_lvl2_disabled(): boolean {
        return (
            !this.user_selected_lvl1_iteration_tracker_id &&
            !this.user_selected_lvl2_iteration_tracker_id
        );
    }
}
</script>
