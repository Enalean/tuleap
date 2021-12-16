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
    <div class="empty-state-page">
        <svg-planned-iterations-empty-state />
        <p
            class="empty-state-text planned-iterations-empty-state-text"
            data-test="planned-iterations-empty-state-text"
        >
            {{ planned_iterations_empty_state_text }}
        </p>
        <form v-bind:action="create_iteration_url" method="post" data-test="new-iteration-form">
            <button
                type="submit"
                class="tlp-button-primary"
                data-test="create-first-iteration-button"
            >
                <i aria-hidden="true" class="fas fa-plus tlp-button-icon"></i>
                <span data-test="button-add-iteration-label">
                    {{ create_the_first_iteration_text }}
                </span>
            </button>
        </form>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { namespace } from "vuex-class";
import { Component } from "vue-property-decorator";
import { sprintf } from "sprintf-js";
import { buildIterationCreationUrl } from "../../../helpers/create-new-iteration-link-builder";

import SvgPlannedIterationsEmptyState from "./SVGPlannedIterationsEmptyState.vue";

import type { IterationLabels, ProgramIncrement } from "../../../store/configuration";

const configuration = namespace("configuration");

@Component({
    components: {
        SvgPlannedIterationsEmptyState,
    },
})
export default class PlannedIterationsSectionEmptyState extends Vue {
    @configuration.State
    readonly iterations_labels!: IterationLabels;

    @configuration.State
    readonly iteration_tracker_id!: number;

    @configuration.State
    readonly program_increment!: ProgramIncrement;

    get planned_iterations_empty_state_text(): string {
        if (this.iterations_labels.sub_label.length === 0) {
            return this.$gettext("There is no iteration yet.");
        }

        return sprintf(this.$gettext("There is no %s yet."), this.iterations_labels.sub_label);
    }

    get create_the_first_iteration_text(): string {
        if (this.iterations_labels.sub_label.length === 0) {
            return this.$gettext("Create the first iteration");
        }

        return sprintf(this.$gettext("Create the first %s"), this.iterations_labels.sub_label);
    }

    get create_iteration_url(): string {
        return buildIterationCreationUrl(this.program_increment.id, this.iteration_tracker_id);
    }
}
</script>
