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
        <empty-state
            v-if="to_be_planned_elements.length === 0 && !is_loading && !has_error"
            data-test="empty-state"
        />
        <element-card
            v-for="element in to_be_planned_elements"
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

        <to-be-planned-skeleton v-if="is_loading" data-test="to-be-planned-skeleton" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { programId } from "../../../configuration";
import {
    getToBePlannedElements,
    ToBePlannedElement,
} from "../../../helpers/ToBePlanned/element-to-plan-retriever";
import EmptyState from "./EmptyState.vue";
import ElementCard from "../ElementCard.vue";
import ToBePlannedSkeleton from "./ToBePlannedSkeleton.vue";

@Component({
    components: { ToBePlannedSkeleton, ElementCard, EmptyState },
})
export default class ToBePlanned extends Vue {
    private error_message = "";
    private has_error = false;
    private to_be_planned_elements: Array<ToBePlannedElement> = [];
    private is_loading = false;

    async mounted(): Promise<void> {
        try {
            this.is_loading = true;
            this.to_be_planned_elements = await getToBePlannedElements(programId());
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
