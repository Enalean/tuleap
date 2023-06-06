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
        <h2 v-translate class="program-to-be-planned-title">To Be Planned</h2>
        <div data-is-container="true" v-bind:data-can-plan="has_plan_permissions">
            <program-increment-not-plannable />
            <feature-not-plannable v-if="!has_plan_permissions" />

            <empty-state
                v-if="to_be_planned_elements.length === 0 && !is_loading && !has_error"
                data-test="empty-state"
            />

            <to-be-planned-card
                v-for="feature in to_be_planned_elements"
                v-bind:key="feature.id"
                v-bind:feature="feature"
                data-test="to-be-planned-elements"
            />
        </div>

        <error-displayer
            v-if="has_error"
            v-bind:message_error_rest="error_message"
            data-test="to-be-planned-error"
        />

        <backlog-element-skeleton v-if="is_loading" data-test="to-be-planned-skeleton" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import EmptyState from "./EmptyState.vue";
import ToBePlannedCard from "./ToBePlannedCard.vue";
import BacklogElementSkeleton from "../BacklogElementSkeleton.vue";
import { State, namespace } from "vuex-class";
import type { Feature } from "../../../type";
import ErrorDisplayer from "../ErrorDisplayer.vue";
import ProgramIncrementNotPlannable from "../ProgramIncrement/ProgramIncrementNotPlannable.vue";
import FeatureNotPlannable from "./FeatureNotPlannable.vue";

const configuration = namespace("configuration");

@Component({
    components: {
        FeatureNotPlannable,
        ProgramIncrementNotPlannable,
        ErrorDisplayer,
        BacklogElementSkeleton,
        ToBePlannedCard,
        EmptyState,
    },
})
export default class ToBePlanned extends Vue {
    error_message = "";
    has_error = false;
    is_loading = false;

    @State
    readonly to_be_planned_elements!: Array<Feature>;
    @configuration.State
    readonly program_id!: number;

    @configuration.State
    readonly has_plan_permissions!: boolean;

    async mounted(): Promise<void> {
        try {
            this.is_loading = true;
            await this.$store.dispatch("retrieveToBePlannedElement", this.program_id);
        } catch (e) {
            this.has_error = true;
            this.error_message = this.$gettext(
                "The retrieval of the elements to be planned in program has failed"
            );
            throw e;
        } finally {
            this.is_loading = false;
        }
    }
}
</script>
