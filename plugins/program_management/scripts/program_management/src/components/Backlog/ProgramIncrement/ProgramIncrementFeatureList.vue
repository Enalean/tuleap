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
    <div
        class="program-increment-content-items"
        data-is-container="true"
        v-bind:data-can-plan="doesIncrementAcceptPlannableItems()"
        data-test="program-increment-feature-list"
        v-bind:data-program-increment-id="increment.id"
    >
        <backlog-element-skeleton v-if="is_loading" data-test="to-be-planned-skeleton" />

        <program-increment-not-plannable />

        <program-increment-no-content
            v-if="features.length === 0 && !is_loading && !has_error"
            data-test="empty-state"
        />

        <feature-card
            v-else
            v-for="feature in features"
            v-bind:key="feature.id"
            v-bind:feature="feature"
            v-bind:program_increment="increment"
            data-test="to-be-planned-elements"
            v-bind:data-program-increment-id="increment.id"
        />

        <error-displayer
            v-if="has_error"
            v-bind:message_error_rest="error_message"
            data-test="to-be-planned-error"
        />
    </div>
</template>

<script lang="ts">
import BacklogElementSkeleton from "../BacklogElementSkeleton.vue";
import ProgramIncrementNoContent from "./ProgramIncrementNoContent.vue";
import FeatureCard from "./FeatureCard.vue";
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";
import ProgramIncrementNotPlannable from "./ProgramIncrementNotPlannable.vue";
import { Getter } from "vuex-class";
import type { Feature } from "../../../type";
import ErrorDisplayer from "../ErrorDisplayer.vue";

@Component({
    components: {
        ErrorDisplayer,
        ProgramIncrementNotPlannable,
        BacklogElementSkeleton,
        FeatureCard,
        ProgramIncrementNoContent,
    },
})
export default class ProgramIncrementFeatureList extends Vue {
    @Prop({ required: true })
    readonly increment!: ProgramIncrement;

    features: Array<Feature> = [];
    error_message = "";
    has_error = false;
    is_loading = false;

    @Getter
    readonly getFeaturesInProgramIncrement!: (increment_id: number) => Feature[];
    @Getter
    readonly isProgramIncrementAlreadyAdded!: (increment_id: number) => boolean;

    async mounted(): Promise<void> {
        if (this.isProgramIncrementAlreadyAdded(this.increment.id)) {
            this.features = this.getFeaturesInProgramIncrement(this.increment.id);
            return;
        }

        try {
            this.is_loading = true;
            this.features = await this.$store.dispatch(
                "getFeatureAndStoreInProgramIncrement",
                this.increment,
            );
        } catch (e) {
            this.has_error = true;
            this.error_message = this.$gettext(
                "The retrieval of the elements to be planned in program has failed",
            );
            throw e;
        } finally {
            this.is_loading = false;
        }
    }

    public doesIncrementAcceptPlannableItems(): boolean {
        return this.increment.user_can_plan;
    }
}
</script>
