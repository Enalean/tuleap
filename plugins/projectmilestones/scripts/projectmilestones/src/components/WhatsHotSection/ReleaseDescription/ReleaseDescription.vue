<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
        <div class="release-content-description">
            <div>
                <release-description-badges-tracker v-bind:release_data="release_data" />
                <test-management-displayer
                    v-if="is_testmanagement_available"
                    v-bind:release_data="release_data"
                />
            </div>
            <chart-displayer class="release-charts-row" v-bind:release_data="release_data" />
        </div>
        <div class="release-description-row">
            <div
                v-if="release_data.description"
                class="tlp-tooltip tlp-tooltip-top"
                v-bind:data-tlp-tooltip="release_data.description"
                data-test="tooltip-description"
            >
                <div class="release-description" v-dompurify-html="release_data.description"></div>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { MilestoneData } from "../../../type";
import ReleaseDescriptionBadgesTracker from "./ReleaseDescriptionBadgesTracker.vue";
import ChartDisplayer from "./Chart/ChartDisplayer.vue";
import TestManagementDisplayer from "./TestManagement/TestManagementDisplayer.vue";
import { is_testmanagement_activated } from "../../../helpers/test-management-helper";

@Component({
    components: {
        TestManagementDisplayer,
        ChartDisplayer,
        ReleaseDescriptionBadgesTracker,
    },
})
export default class ReleaseDescription extends Vue {
    @Prop()
    readonly release_data!: MilestoneData;

    get is_testmanagement_available(): boolean {
        return is_testmanagement_activated(this.release_data);
    }
}
</script>
