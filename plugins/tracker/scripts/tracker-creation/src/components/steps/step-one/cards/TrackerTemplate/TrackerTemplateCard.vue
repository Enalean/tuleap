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
            <div class="card-glyph">
                <svg-tracker-template />
            </div>
            <tracker-template-selector v-if="isOptionActive" />
            <tracker-template-card-description v-else />
        </template>
    </tracker-base-card>
</template>
<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { TRACKER_TEMPLATE } from "../../../../../store/type";
import SvgTrackerTemplate from "./SvgTrackerTemplate.vue";
import TrackerBaseCard from "../TrackerBaseCard.vue";
import TrackerTemplateSelector from "./TrackerTemplateSelector.vue";
import TrackerTemplateCardDescription from "./TrackerTemplateCardDescription.vue";

@Component({
    components: {
        SvgTrackerTemplate,
        TrackerBaseCard,
        TrackerTemplateSelector,
        TrackerTemplateCardDescription,
    },
})
export default class TrackerTemplateCard extends Vue {
    option_name = TRACKER_TEMPLATE;
}
</script>
