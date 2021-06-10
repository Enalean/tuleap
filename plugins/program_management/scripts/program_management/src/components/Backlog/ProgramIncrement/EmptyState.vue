<!---
  - Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
    <div class="program-increment-empty-state">
        <empty-svg />
        <p v-translate class="empty-page-text">There are no program increments yet</p>
        <form v-bind:action="create_new_program_increment" method="post">
            <button
                class="tlp-button-primary program-increment-title-button-icon"
                data-test="create-program-increment-button"
                v-if="can_create_program_increment"
            >
                <i class="fas fa-plus tlp-button-icon" aria-hidden="true"></i>
                <span v-translate>Create the first program increment</span>
            </button>
        </form>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import EmptySvg from "./EmptySvg.vue";
import { buildCreateNewProgramIncrement } from "../../../helpers/location-helper";
import { namespace } from "vuex-class";

const configuration = namespace("configuration");

@Component({
    components: { EmptySvg },
})
export default class EmptyState extends Vue {
    @configuration.State
    readonly can_create_program_increment!: boolean;

    @configuration.State
    readonly tracker_program_increment_id!: number;

    get create_new_program_increment(): string {
        return buildCreateNewProgramIncrement(this.tracker_program_increment_id);
    }
}
</script>
