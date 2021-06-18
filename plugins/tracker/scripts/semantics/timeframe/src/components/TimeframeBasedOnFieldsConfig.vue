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
  -->

<template>
    <div>
        <div class="control-group">
            <label class="control-label" for="start-date">
                <translate>Start date</translate>
                <span class="highlight">*</span>
            </label>

            <div class="controls">
                <select
                    id="start-date"
                    name="start-date-field-id"
                    data-test="start-date-field-select-box"
                    v-model="user_selected_start_date_field_id"
                    required
                >
                    <option value="" v-translate>Choose a field...</option>
                    <option
                        v-for="date_field in suitable_start_date_fields"
                        v-bind:value="date_field.id"
                        v-bind:name="date_field.label"
                        v-bind:key="'start-date-field' + date_field.id"
                    >
                        {{ date_field.label }}
                    </option>
                </select>
            </div>
        </div>

        <div class="control-group">
            <div class="controls">
                <div class="control-group">
                    <label class="radio" for="option-end-date">
                        <input
                            id="option-end-date"
                            type="radio"
                            value="end-date"
                            data-test="option-end-date"
                            v-bind:checked="!is_in_start_date_duration_mode"
                            v-on:click="toggleTimeframeMode(MODE_START_DATE_END_DATE)"
                        />
                        <translate>End date</translate>
                        <span
                            class="highlight"
                            data-test="end-date-field-highlight-field-required"
                            v-if="!is_in_start_date_duration_mode"
                        >
                            *
                        </span>
                    </label>
                    <select
                        class="tracker-administration-semantic-timeframe-option-selector"
                        name="end-date-field-id"
                        data-test="end-date-field-select-box"
                        v-model="user_selected_end_date_field_id"
                        v-bind:required="!is_in_start_date_duration_mode"
                        v-bind:disabled="is_in_start_date_duration_mode"
                    >
                        <option value="" v-translate>Choose a field...</option>
                        <option
                            v-for="date_field in suitable_end_date_fields"
                            v-bind:value="date_field.id"
                            v-bind:name="date_field.label"
                            v-bind:key="'end-date-field' + date_field.id"
                        >
                            {{ date_field.label }}
                        </option>
                    </select>
                </div>
                <div class="control-group">
                    <label class="radio" for="option-duration">
                        <input
                            id="option-duration"
                            type="radio"
                            value="duration"
                            data-test="option-duration"
                            v-bind:checked="is_in_start_date_duration_mode"
                            v-on:click="toggleTimeframeMode(MODE_START_DATE_DURATION)"
                        />
                        <translate>Duration</translate>
                        <span
                            class="highlight"
                            data-test="duration-field-highlight-field-required"
                            v-if="is_in_start_date_duration_mode"
                        >
                            *
                        </span>
                    </label>
                    <select
                        class="tracker-administration-semantic-timeframe-option-selector"
                        name="duration-field-id"
                        data-test="duration-field-select-box"
                        v-model="user_selected_duration_field_id"
                        v-bind:required="is_in_start_date_duration_mode"
                        v-bind:disabled="!is_in_start_date_duration_mode"
                    >
                        <option value="" v-translate>Choose a field...</option>
                        <option
                            v-for="numeric_field in usable_numeric_fields"
                            v-bind:value="numeric_field.id"
                            v-bind:key="numeric_field.id"
                        >
                            {{ numeric_field.label }}
                        </option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";

import type { TrackerField } from "../../type";

@Component
export default class TimeframeBasedOnFieldsConfig extends Vue {
    @Prop({ required: true })
    private readonly usable_date_fields!: TrackerField[];

    @Prop({ required: true })
    private readonly usable_numeric_fields!: TrackerField[];

    @Prop({ required: true })
    private readonly selected_start_date_field_id!: number | "";

    @Prop({ required: true })
    private readonly selected_end_date_field_id!: number | "";

    @Prop({ required: true })
    private readonly selected_duration_field_id!: number | "";

    private user_selected_start_date_field_id: number | "" = "";
    private user_selected_end_date_field_id: number | "" = "";
    private user_selected_duration_field_id: number | "" = "";
    private is_in_start_date_duration_mode = false;

    private readonly MODE_START_DATE_END_DATE = "MODE_START_DATE_END_DATE";
    private readonly MODE_START_DATE_DURATION = "MODE_START_DATE_DURATION";

    mounted(): void {
        this.user_selected_start_date_field_id = this.selected_start_date_field_id;
        this.user_selected_end_date_field_id = this.selected_end_date_field_id;
        this.user_selected_duration_field_id = this.selected_duration_field_id;
        this.is_in_start_date_duration_mode = Boolean(
            this.selected_start_date_field_id !== "" && this.selected_duration_field_id !== ""
        );
    }

    toggleTimeframeMode(mode: string): void {
        this.is_in_start_date_duration_mode = mode === this.MODE_START_DATE_DURATION;
    }

    get suitable_start_date_fields(): TrackerField[] {
        return this.usable_date_fields.filter(
            (date_field) => date_field.id !== this.user_selected_end_date_field_id
        );
    }

    get suitable_end_date_fields(): TrackerField[] {
        return this.usable_date_fields.filter(
            (date_field) => date_field.id !== this.user_selected_start_date_field_id
        );
    }
}
</script>
