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
                    {{
                        $gettext("New %{ iteration_sub_label }", {
                            iteration_sub_label: iterations_labels.sub_label,
                        })
                    }}
                </button>
            </div>
        </form>
        <backlog-element-skeleton v-if="is_loading" />
        <planned-iterations-section-empty-state v-if="show_empty_state" />
        <iteration-card
            v-for="iteration in iterations"
            v-bind:key="iteration.id"
            v-bind:iteration="iteration"
        />
        <div
            class="tlp-alert-danger iteration-fetch-error"
            v-if="has_error"
            data-test="iteration-fetch-error"
        >
            {{ error_message }}
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import { useNamespacedState } from "vuex-composition-helpers";
import { getIncrementIterations } from "../../../helpers/increment-iterations-retriever";
import { buildIterationCreationUrl } from "../../../helpers/create-new-iteration-link-builder";
import BacklogElementSkeleton from "../../BacklogElementSkeleton.vue";
import PlannedIterationsSectionEmptyState from "./PlannedIterationsSectionEmptyState.vue";
import IterationCard from "./IterationCard.vue";
import type { Iteration } from "../../../type";
import type { IterationLabels, ProgramIncrement } from "../../../store/configuration";

const { $gettext } = useGettext();

const { iterations_labels, iteration_tracker_id, program_increment } = useNamespacedState<{
    iterations_labels: IterationLabels;
    iteration_tracker_id: number;
    program_increment: ProgramIncrement;
}>("configuration", ["iterations_labels", "iteration_tracker_id", "program_increment"]);

const iterations = ref<Iteration[]>([]);
const error_message = ref("");
const has_error = ref(false);
const is_loading = ref(false);

onMounted(async () => {
    try {
        is_loading.value = true;
        iterations.value = await getIncrementIterations(program_increment.value.id);
    } catch (_e) {
        has_error.value = true;
        error_message.value = buildErrorMessage();
    } finally {
        is_loading.value = false;
    }
});

function buildErrorMessage(): string {
    return iterations_labels.value.label === ""
        ? $gettext("The retrieval of iterations has failed")
        : $gettext("The retrieval of %{ iteration_label } has failed", {
              iteration_label: iterations_labels.value.label,
          });
}

const show_empty_state = computed((): boolean => {
    return !is_loading.value && !has_error.value && iterations.value.length === 0;
});

const create_iteration_url = buildIterationCreationUrl(
    program_increment.value.id,
    iteration_tracker_id.value,
);
</script>
