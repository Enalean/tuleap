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
    <div id="timeframe-admin-section-implied-from-another-tracker" class="control-group">
        <template v-if="can_semantic_be_implied">
            <label class="control-label" for="timeframe-tracker-selector">
                <translate>Tracker</translate>
                <span class="highlight">*</span>
            </label>
            <div class="controls">
                <select
                    id="timeframe-tracker-selector"
                    name="implied-from-tracker-id"
                    data-test="implied-from-tracker-select-box"
                    v-model="user_select_implied_from_tracker_id"
                    required
                >
                    <option value="" disabled v-translate>Choose a tracker...</option>
                    <option
                        v-for="tracker in suitable_trackers"
                        v-bind:value="tracker.id"
                        v-bind:key="tracker.id"
                    >
                        {{ tracker.name }}
                    </option>
                </select>
                <p class="text-info">
                    <i class="fas fa-life-ring"></i>
                    <translate>
                        You can't find the tracker you are looking for? Make sure it has an artifact
                        link field, and that its semantic is not inherited from another tracker.
                    </translate>
                </p>
            </div>
        </template>
        <div
            v-else-if="has_other_trackers_implying_their_timeframes"
            class="alert alert-error"
            data-test="error-message-other-trackers-implying-their-timeframe"
            v-translate
        >
            You cannot make this semantic inherit from another tracker because some other trackers
            are inheriting their own semantics timeframe from this one.
        </div>
        <div
            v-else
            class="alert alert-error"
            data-test="error-message-no-art-link-field"
            v-dompurify-html="missing_artifact_link_field_error_message"
        ></div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { Tracker } from "../../type";

@Component
export default class TimeframeImpliedFromAnotherTrackerConfig extends Vue {
    @Prop({ required: true })
    private readonly suitable_trackers!: Tracker[];

    @Prop({ required: true })
    private readonly has_artifact_link_field!: boolean;

    @Prop({ required: true })
    private readonly implied_from_tracker_id!: number | "";

    @Prop({ required: true })
    private readonly current_tracker_id!: number;

    @Prop({ required: true })
    private readonly has_other_trackers_implying_their_timeframes!: boolean;

    private user_select_implied_from_tracker_id: number | "" = "";

    mounted(): void {
        this.user_select_implied_from_tracker_id = this.implied_from_tracker_id;
    }

    get can_semantic_be_implied(): boolean {
        return this.has_artifact_link_field && !this.has_other_trackers_implying_their_timeframes;
    }

    get missing_artifact_link_field_error_message(): string {
        const tracker_fields_admin_url = `/plugins/tracker/?tracker=${this.current_tracker_id}&func=admin-formElements`;

        let translated = this.$gettext(
            `Please <a href="%{ tracker_fields_admin_url }">add an artifact link field</a> to your tracker first.`
        );

        return this.$gettextInterpolate(translated, { tracker_fields_admin_url });
    }
}
</script>
