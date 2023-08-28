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
            <div class="card-glyph"><svg-from-jira /></div>
            <tracker-from-jira v-if="isOptionActive" />
            <tracker-from-jira-description v-else />
        </template>
    </tracker-base-card>
</template>
<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { FROM_JIRA } from "../../../../../store/type";
import SvgFromJira from "./SvgFromJira.vue";
import TrackerBaseCard from "../TrackerBaseCard.vue";
import TrackerFromJiraDescription from "./TrackerFromJiraDescription.vue";
import TrackerFromJira from "./TrackerFromJira.vue";

@Component({
    components: {
        TrackerFromJira,
        SvgFromJira,
        TrackerBaseCard,
        TrackerFromJiraDescription,
    },
})
export default class TrackerFromJiraCard extends Vue {
    option_name = FROM_JIRA;
}
</script>
