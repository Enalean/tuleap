<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <tracker-base-card v-bind:option-name="tracker.id">
        <template v-slot:content>
            <div class="card-glyph">
                <component v-bind:is="svg_glyph" />
            </div>
            <div class="card-content">
                <h4 class="card-title">{{ tracker.name }}</h4>
                <div class="card-description">
                    <span class="card-description-content">
                        {{ tracker.description }}
                    </span>
                </div>
            </div>
        </template>
    </tracker-base-card>
</template>
<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Tracker } from "../../../../../store/type";
import TrackerBaseCard from "../TrackerBaseCard.vue";
import SvgTrackerTemplate from "../TrackerTemplate/SvgTrackerTemplate.vue";
import SvgActivity from "./SvgActivity.vue";
import SvgBug from "./SvgBug.vue";

@Component({
    components: {
        SvgTrackerTemplate,
        SvgActivity,
        SvgBug,
        TrackerBaseCard,
    },
})
export default class DefaultTemplateCard extends Vue {
    @Prop({ required: true })
    readonly tracker!: Tracker;

    get svg_glyph(): string {
        switch (this.tracker.id) {
            case "default-bug":
                return "svg-bug";
            case "default-activity":
                return "svg-activity";
            default:
                return "svg-tracker-template";
        }
    }
}
</script>
