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
-->

<template>
    <tracker-base-card v-bind:option-name="option_name">
        <template v-slot:content>
            <div class="card-glyph"><svg-tracker-empty /></div>
            <tracker-empty-card-description />
        </template>
    </tracker-base-card>
</template>
<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { TRACKER_EMPTY } from "../../../../../store/type";
import SvgTrackerEmpty from "./SvgTrackerEmpty.vue";
import TrackerBaseCard from "../TrackerBaseCard.vue";
import TrackerEmptyCardDescription from "./TrackerEmptyCardDescription.vue";

@Component({
    components: {
        SvgTrackerEmpty,
        TrackerBaseCard,
        TrackerEmptyCardDescription,
    },
})
export default class TrackerEmptyCard extends Vue {
    option_name = TRACKER_EMPTY;
}
</script>
