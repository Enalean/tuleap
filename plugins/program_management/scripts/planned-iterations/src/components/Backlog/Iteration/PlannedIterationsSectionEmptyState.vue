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

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { useNamespacedState } from "vuex-composition-helpers";
import { buildIterationCreationUrl } from "../../../helpers/create-new-iteration-link-builder";
import type { IterationLabels, ProgramIncrement } from "../../../store/configuration";
import SvgPlannedIterationsEmptyState from "./SVGPlannedIterationsEmptyState.vue";

const { $gettext } = useGettext();

const { iterations_labels, iteration_tracker_id, program_increment } = useNamespacedState<{
    iterations_labels: IterationLabels;
    iteration_tracker_id: number;
    program_increment: ProgramIncrement;
}>("configuration", ["iterations_labels", "iteration_tracker_id", "program_increment"]);

const planned_iterations_empty_state_text =
    iterations_labels.value.sub_label === ""
        ? $gettext("There is no iteration yet.")
        : $gettext("There is no %{ iteration_label } yet.", {
              iteration_label: iterations_labels.value.sub_label,
          });

const create_the_first_iteration_text =
    iterations_labels.value.sub_label === ""
        ? $gettext("Create the first iteration")
        : $gettext("Create the first %{ iteration_label }", {
              iteration_label: iterations_labels.value.sub_label,
          });

const create_iteration_url = buildIterationCreationUrl(
    program_increment.value.id,
    iteration_tracker_id.value,
);
</script>
