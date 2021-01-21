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
        <h2 v-translate class="program-increment-title">Program Increment</h2>

        <empty-state
            v-if="program_increments.length === 0 && !is_loading && !has_error"
            data-test="empty-state"
        />

        <program-increment-card
            v-for="increment in program_increments"
            v-bind:key="increment.artifact_id"
            v-bind:increment="increment"
            data-test="program-increments"
        />

        <program-increment-skeleton v-if="is_loading" dat-test="program-increment-skeleton" />

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
import {
    getProgramIncrements,
    ProgramIncrement,
} from "../../../helpers/ProgramIncrement/program-increment-retriever";
import { programId } from "../../../configuration";
import ProgramIncrementSkeleton from "./ProgramIncrementSkeleton.vue";

@Component({
    components: { ProgramIncrementSkeleton, ProgramIncrementCard, EmptyState },
})
export default class ProgramIncrementList extends Vue {
    private error_message = "";
    private has_error = false;
    private program_increments: Array<ProgramIncrement> = [];
    private is_loading = false;

    async mounted(): Promise<void> {
        try {
            this.is_loading = true;
            this.program_increments = await getProgramIncrements(programId());
        } catch (e) {
            this.has_error = true;
            this.error_message = this.$gettext(
                "The retrieval of the program increments has failed"
            );
            throw e;
        } finally {
            this.is_loading = false;
        }
    }
}
</script>
