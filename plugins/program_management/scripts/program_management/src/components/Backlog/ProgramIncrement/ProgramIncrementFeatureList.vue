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
        v-bind:class="{ 'program-increment-no-content': features.length === 0 }"
    >
        <to-be-planned-skeleton v-if="is_loading" data-test="to-be-planned-skeleton" />

        <program-increment-no-content
            v-if="features.length === 0 && !is_loading && !has_error"
            data-test="empty-state"
        />

        <element-card
            v-else
            v-for="element in features"
            v-bind:key="element.artifact_id"
            v-bind:element="element"
            data-test="to-be-planned-elements"
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
import ElementCard from "../ElementCard.vue";
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { Feature } from "../../../helpers/ProgramIncrement/Feature/feature-retriever";
import { getFeatures } from "../../../helpers/ProgramIncrement/Feature/feature-retriever";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";

@Component({
    components: { ToBePlannedSkeleton, ElementCard, ProgramIncrementNoContent },
})
export default class ProgramIncrementFeatureList extends Vue {
    @Prop({ required: true })
    readonly increment!: ProgramIncrement;

    private features: Array<Feature> = [];
    private error_message = "";
    private has_error = false;
    private is_loading = false;
    private has_loaded_feature = false;

    async mounted(): Promise<void> {
        if (!this.has_loaded_feature) {
            try {
                this.is_loading = true;
                this.features = await getFeatures(this.increment.id);
            } catch (e) {
                this.has_error = true;
                this.error_message = this.$gettext(
                    "The retrieval of the elements to be planned in program has failed"
                );
                throw e;
            } finally {
                this.is_loading = false;
                this.has_loaded_feature = true;
            }
        }
    }
}
</script>
