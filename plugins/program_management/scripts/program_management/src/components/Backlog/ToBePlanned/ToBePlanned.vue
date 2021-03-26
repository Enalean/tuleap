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
        <div data-is-container="true" data-can-plan="true">
            <empty-state
                v-if="to_be_planned_elements.length === 0 && !is_loading && !has_error"
                data-test="empty-state"
            />

            <to-be-planned-card
                v-for="element in to_be_planned_elements"
                v-bind:key="element.id"
                v-bind:element="element"
                data-test="to-be-planned-elements"
            />
        </div>

        <div
            id="to-be-planned-backlog-error"
            class="tlp-alert-danger"
            v-if="has_error"
            data-test="to-be-planned-error"
        >
            {{ error_message }}
        </div>

        <backlog-element-skeleton v-if="is_loading" data-test="to-be-planned-skeleton" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { getToBePlannedElements } from "../../../helpers/ToBePlanned/element-to-plan-retriever";
import EmptyState from "./EmptyState.vue";
import ToBePlannedCard from "./ToBePlannedCard.vue";
import BacklogElementSkeleton from "../BacklogElementSkeleton.vue";
import { Mutation, State } from "vuex-class";
import type { Feature } from "../../../type";
import { namespace } from "vuex-class";

const configuration = namespace("configuration");

@Component({
    components: { BacklogElementSkeleton, ToBePlannedCard, EmptyState },
})
export default class ToBePlanned extends Vue {
    private error_message = "";
    private has_error = false;
    private is_loading = false;

    @Mutation
    readonly setToBePlannedElements!: (to_be_planned_elements: Feature[]) => void;
    @State
    readonly to_be_planned_elements!: Array<Feature>;
    @configuration.State
    readonly program_id!: number;

    async mounted(): Promise<void> {
        try {
            this.is_loading = true;
            const to_be_planned_elements = await getToBePlannedElements(this.program_id);
            this.setToBePlannedElements(to_be_planned_elements);
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
