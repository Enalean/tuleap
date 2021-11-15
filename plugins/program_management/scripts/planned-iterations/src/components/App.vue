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
        <breadcrumb />
        <h1 class="planned-iterations-title-header" data-test="app-header-title">
            {{ program_increment.title }}
            <small class="planned-iterations-title-header-dates" v-if="are_dates_displayed">
                {{ program_increment.start_date }} – {{ program_increment.end_date }}
            </small>
        </h1>
        <div class="iterations-backlog">
            <iterations-to-be-planned-section />
            <planned-iterations-section />
        </div>
    </div>
</template>

<script lang="ts">
import type { ProgramIncrement } from "../type";

import Vue from "vue";
import { State } from "vuex-class";
import { Component } from "vue-property-decorator";
import Breadcrumb from "./Breadcrumb.vue";
import IterationsToBePlannedSection from "./IterationsToBePlannedSection.vue";
import PlannedIterationsSection from "./PlannedIterationsSection.vue";

@Component({
    components: {
        Breadcrumb,
        IterationsToBePlannedSection,
        PlannedIterationsSection,
    },
})
export default class App extends Vue {
    @State
    readonly program_increment!: ProgramIncrement;

    get are_dates_displayed(): boolean {
        return (
            this.program_increment.start_date.length > 0 &&
            this.program_increment.end_date.length > 0
        );
    }
}
</script>
