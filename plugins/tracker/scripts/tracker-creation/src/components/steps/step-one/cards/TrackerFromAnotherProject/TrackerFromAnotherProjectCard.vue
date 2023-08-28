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
        <template v-slot:content="{ isOptionActive }">
            <div class="card-glyph" v-bind:class="{ 'card-glyph-stay-at-top': isOptionActive }">
                <svg-tracker-from-another-project />
            </div>
            <tracker-from-another-project-selector v-if="isOptionActive" />
            <tracker-from-another-project-description v-else />
        </template>
    </tracker-base-card>
</template>
<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { TRACKER_ANOTHER_PROJECT } from "../../../../../store/type";
import TrackerBaseCard from "../TrackerBaseCard.vue";
import SvgTrackerFromAnotherProject from "./SvgTrackerFromAnotherProject.vue";
import TrackerFromAnotherProjectDescription from "./TrackerFromAnotherProjectDescription.vue";
import TrackerFromAnotherProjectSelector from "./TrackerFromAnotherProjectSelector.vue";

@Component({
    components: {
        SvgTrackerFromAnotherProject,
        TrackerBaseCard,
        TrackerFromAnotherProjectDescription,
        TrackerFromAnotherProjectSelector,
    },
})
export default class TrackerFromAnotherProjectCard extends Vue {
    option_name = TRACKER_ANOTHER_PROJECT;
}
</script>
