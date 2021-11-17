<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
    <div class="planned-iterations">
        <h2 class="planned-iterations-section-title" data-test="planned-iterations-section-title">
            {{ iterations_section_title }}
        </h2>
        <backlog-element-skeleton v-if="is_loading" />
        <template v-if="!is_loading && !has_error">
            <div
                class="empty-state-page"
                data-test="planned-iterations-empty-state"
                v-if="has_iterations === false"
            >
                <svg-planned-iterations-empty-state />
                <p
                    class="empty-state-text planned-iterations-empty-state-text"
                    data-test="planned-iterations-empty-state-text"
                >
                    {{ planned_iterations_empty_state_text }}
                </p>
            </div>
            <iteration-card
                v-else
                v-for="iteration in iterations"
                v-bind:key="iteration.id"
                v-bind:iteration="iteration"
            />
        </template>
        <div
            class="tlp-alert-danger iteration-fetch-error"
            v-if="has_error"
            data-test="iteration-fetch-error"
        >
            {{ error_message }}
        </div>
    </div>
</template>

<script lang="ts">
import type { Iteration, IterationLabels, ProgramIncrement } from "../type";

import Vue from "vue";
import { State } from "vuex-class";
import { Component } from "vue-property-decorator";
import { sprintf } from "sprintf-js";
import { getIncrementIterations } from "../helpers/increment-iterations-retriever";
import SvgPlannedIterationsEmptyState from "./SVGPlannedIterationsEmptyState.vue";
import IterationCard from "./IterationCard.vue";
import BacklogElementSkeleton from "./BacklogElementSkeleton.vue";

@Component({
    components: {
        SvgPlannedIterationsEmptyState,
        IterationCard,
        BacklogElementSkeleton,
    },
})
export default class PlannedIterationsSection extends Vue {
    @State
    readonly iterations_labels!: IterationLabels;

    @State
    readonly program_increment!: ProgramIncrement;

    private iterations: Array<Iteration> = [];
    private error_message = "";
    private has_error = false;
    private is_loading = false;

    async mounted(): Promise<void> {
        try {
            this.is_loading = true;
            this.iterations = await getIncrementIterations(this.program_increment.id);
        } catch (e) {
            this.has_error = true;
            this.error_message = this.buildErrorMessage();
        } finally {
            this.is_loading = false;
        }
    }

    buildErrorMessage(): string {
        if (this.iterations_labels.label.length === 0) {
            return this.$gettext("The retrieval of iterations has failed");
        }

        return sprintf(
            this.$gettext("The retrieval of %s has failed"),
            this.iterations_labels.label
        );
    }

    get iterations_section_title(): string {
        return this.iterations_labels.label.length === 0
            ? this.$gettext("Iterations")
            : this.iterations_labels.label;
    }

    get planned_iterations_empty_state_text(): string {
        if (this.iterations_labels.sub_label.length === 0) {
            return this.$gettext("There is no iteration yet.");
        }

        return sprintf(this.$gettext("There is no %s yet."), this.iterations_labels.sub_label);
    }

    get has_iterations(): boolean {
        return this.iterations.length > 0;
    }
}
</script>
