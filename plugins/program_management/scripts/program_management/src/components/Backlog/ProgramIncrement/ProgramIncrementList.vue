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
    <div>
        <form v-bind:action="create_new_program_increment" method="post">
            <div class="program-increment-title-with-button">
                <h2 data-test="program-increment-title" class="program-increment-title">
                    {{ tracker_program_increment_label }}
                </h2>
                <button
                    class="tlp-button-primary tlp-button-outline tlp-button-small program-increment-title-button"
                    v-if="user_can_create_program_increment"
                    data-test="create-program-increment-button"
                >
                    <i class="fas fa-plus tlp-button-icon" aria-hidden="true"></i>
                    <span
                        data-test="button-add-program-increment-label"
                        v-translate="{
                            program_increment_sub_label: tracker_program_increment_sub_label,
                        }"
                    >
                        New %{ program_increment_sub_label }
                    </span>
                </button>
            </div>
        </form>

        <empty-state
            v-if="program_increments.length === 0 && !is_loading && !has_error"
            data-test="empty-state"
        />

        <program-increment-card
            v-for="increment in program_increments"
            v-bind:key="increment.id"
            v-bind:increment="increment"
            data-test="program-increments"
        />

        <backlog-element-skeleton v-if="is_loading" dat-test="program-increment-skeleton" />

        <div
            id="program-increment-error"
            class="tlp-alert-danger"
            v-if="has_error"
            data-test="program-increment-error"
        >
            {{ error_message }}
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import EmptyState from "./EmptyState.vue";
import ProgramIncrementCard from "./ProgramIncrementCard.vue";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";
import { getProgramIncrements } from "../../../helpers/ProgramIncrement/program-increment-retriever";
import BacklogElementSkeleton from "../BacklogElementSkeleton.vue";
import { buildCreateNewProgramIncrement } from "../../../helpers/location-helper";
import { namespace } from "vuex-class";

const configuration = namespace("configuration");

@Component({
    components: { BacklogElementSkeleton, ProgramIncrementCard, EmptyState },
})
export default class ProgramIncrementList extends Vue {
    error_message = "";
    has_error = false;
    program_increments: Array<ProgramIncrement> = [];
    is_loading = false;

    @configuration.State
    readonly can_create_program_increment!: boolean;

    @configuration.State
    readonly tracker_program_increment_label!: string;

    @configuration.State
    readonly tracker_program_increment_sub_label!: string;

    @configuration.State
    readonly tracker_program_increment_id!: number;

    @configuration.State
    readonly program_id!: number;

    async mounted(): Promise<void> {
        try {
            this.is_loading = true;
            this.program_increments = await getProgramIncrements(this.program_id);
        } catch (e) {
            this.has_error = true;
            this.error_message = this.$gettext(
                "The retrieval of the program increments has failed",
            );
            throw e;
        } finally {
            this.is_loading = false;
        }
    }

    get user_can_create_program_increment(): boolean {
        return this.can_create_program_increment && this.program_increments.length > 0;
    }

    get create_new_program_increment(): string {
        return buildCreateNewProgramIncrement(this.tracker_program_increment_id);
    }
}
</script>
