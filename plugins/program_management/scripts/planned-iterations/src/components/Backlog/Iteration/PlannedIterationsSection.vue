<!--
  - Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
  -
  -->

<template>
    <div class="planned-iterations">
        <form v-bind:action="create_iteration_url" method="post">
            <div class="planned-iteration-header-with-button">
                <h2
                    class="planned-iterations-section-title"
                    data-test="planned-iterations-section-title"
                >
                    {{ iterations_labels.label }}
                </h2>
                <button
                    type="submit"
                    class="tlp-button-primary tlp-button-outline tlp-button-small new-iteration-button"
                    data-test="planned-iterations-add-iteration-button"
                >
                    <i aria-hidden="true" class="fas fa-plus tlp-button-icon"></i>
                    <span
                        data-test="button-add-iteration-label"
                        v-translate="{ iteration_sub_label: iterations_labels.sub_label }"
                    >
                        New %{ iteration_sub_label }
                    </span>
                </button>
            </div>
        </form>
        <backlog-element-skeleton v-if="is_loading" />
        <template v-if="!is_loading && !has_error">
            <planned-iterations-section-empty-state v-if="has_iterations === false" />
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
import Vue from "vue";

import { namespace } from "vuex-class";
import { Component } from "vue-property-decorator";
import { getIncrementIterations } from "../../../helpers/increment-iterations-retriever";
import { buildIterationCreationUrl } from "../../../helpers/create-new-iteration-link-builder";

import BacklogElementSkeleton from "../../BacklogElementSkeleton.vue";
import PlannedIterationsSectionEmptyState from "./PlannedIterationsSectionEmptyState.vue";
import IterationCard from "./IterationCard.vue";

import type { Iteration } from "../../../type";
import type { IterationLabels, ProgramIncrement } from "../../../store/configuration";

const configuration = namespace("configuration");

@Component({
    components: {
        PlannedIterationsSectionEmptyState,
        IterationCard,
        BacklogElementSkeleton,
    },
})
export default class PlannedIterationsSection extends Vue {
    @configuration.State
    readonly iterations_labels!: IterationLabels;

    @configuration.State
    readonly program_increment!: ProgramIncrement;

    @configuration.State
    readonly iteration_tracker_id!: number;

    iterations: Array<Iteration> = [];
    error_message = "";
    has_error = false;
    is_loading = false;

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

        return this.$gettextInterpolate(
            this.$gettext("The retrieval of %{ iteration_label } has failed"),
            { iteration_label: this.iterations_labels.label },
        );
    }

    get has_iterations(): boolean {
        return this.iterations.length > 0;
    }

    get create_iteration_url(): string {
        return buildIterationCreationUrl(this.program_increment.id, this.iteration_tracker_id);
    }
}
</script>
