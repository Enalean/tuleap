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
    <section class="empty-state-page">
        <empty-svg />
        <p class="empty-state-text">
            {{ $gettext("There are no program increments yet") }}
        </p>
        <form v-bind:action="create_new_program_increment" method="post">
            <button
                class="empty-state-action tlp-button-primary"
                data-test="create-program-increment-button"
                v-if="can_create_program_increment"
            >
                <i class="fas fa-plus tlp-button-icon" aria-hidden="true"></i>
                {{ create_first_label }}
            </button>
        </form>
    </section>
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

    @configuration.State
    readonly tracker_program_increment_sub_label!: string;

    get create_new_program_increment(): string {
        return buildCreateNewProgramIncrement(this.tracker_program_increment_id);
    }

    get create_first_label(): string {
        return this.$gettextInterpolate(
            this.$gettext("Create the first %{ program_increment_sub_label }"),
            { program_increment_sub_label: this.tracker_program_increment_sub_label },
        );
    }
}
</script>
