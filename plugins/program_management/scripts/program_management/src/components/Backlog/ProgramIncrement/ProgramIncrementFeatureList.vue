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
        v-bind:data-planned-feature-ids="getFeaturesAlreadyLinked()"
        v-bind:data-artifact-link-field-id="program_increment_artifact_link_id"
    >
        <to-be-planned-skeleton v-if="is_loading" data-test="to-be-planned-skeleton" />

        <program-increment-not-plannable />

        <program-increment-no-content
            v-if="features.length === 0 && !is_loading && !has_error"
            data-test="empty-state"
        />

        <feature-card
            v-else
            v-for="element in features"
            v-bind:key="element.artifact_id"
            v-bind:element="element"
            v-bind:program_increment="increment"
            data-test="to-be-planned-elements"
            v-bind:data-program-increment-id="increment.id"
            v-bind:data-planned-feature-ids="getFeaturesAlreadyLinked()"
            v-bind:data-artifact-link-field-id="program_increment_artifact_link_id"
        />

        <div
            id="to-be-planned-backlog-error"
            class="tlp-alert-danger"
            v-if="has_error"
            data-test="to-be-planned-error"
        >
            {{ error_message }}
        </div>
    </div>
</template>

<script lang="ts">
import ToBePlannedSkeleton from "../ToBePlanned/ToBePlannedSkeleton.vue";
import ProgramIncrementNoContent from "./ProgramIncrementNoContent.vue";
import FeatureCard from "./FeatureCard.vue";
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { Feature } from "../../../helpers/ProgramIncrement/Feature/feature-retriever";
import { getFeatures } from "../../../helpers/ProgramIncrement/Feature/feature-retriever";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";
import ProgramIncrementNotPlannable from "./ProgramIncrementNotPlannable.vue";
import { Getter, Mutation, namespace } from "vuex-class";

const configuration = namespace("configuration");

@Component({
    components: {
        ProgramIncrementNotPlannable,
        ToBePlannedSkeleton,
        FeatureCard,
        ProgramIncrementNoContent,
    },
})
export default class ProgramIncrementFeatureList extends Vue {
    @Prop({ required: true })
    readonly increment!: ProgramIncrement;

    private features: Array<Feature> = [];
    private error_message = "";
    private has_error = false;
    private is_loading = false;

    @Mutation
    readonly addProgramIncrement!: (program_increment: ProgramIncrement) => void;
    @Getter
    readonly getFeaturesInProgramIncrement!: (increment_id: number) => Feature[];
    @Getter
    readonly isProgramIncrementAlreadyAdded!: (increment_id: number) => boolean;
    @configuration.State
    readonly program_increment_artifact_link_id!: number | null;

    async mounted(): Promise<void> {
        if (this.isProgramIncrementAlreadyAdded(this.increment.id)) {
            this.features = this.getFeaturesInProgramIncrement(this.increment.id);
            return;
        }

        try {
            this.is_loading = true;
            this.features = await getFeatures(this.increment.id);
            this.addProgramIncrement({ ...this.increment, features: this.features });
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

    public doesIncrementAcceptPlannableItems(): boolean {
        return this.increment.user_can_plan;
    }

    public getFeaturesAlreadyLinked(): string {
        return this.features
            .map((feature) => {
                return feature.artifact_id;
            })
            .toString();
    }
}
</script>
