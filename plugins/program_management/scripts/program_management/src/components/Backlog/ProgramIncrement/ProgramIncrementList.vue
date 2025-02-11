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
        <form v-bind:action="create_new_program_increment_uri" method="post">
            <div class="program-increment-title-with-button">
                <h2 data-test="program-increment-title" class="program-increment-title">
                    {{ tracker_program_increment_label }}
                </h2>
                <button
                    class="tlp-button-primary tlp-button-outline tlp-button-small program-increment-title-button"
                    v-if="user_can_create_program_increment"
                    data-test="create-program-increment-button"
                >
                    <i class="fas fa-plus tlp-button-icon" aria-hidden="true"></i>
                    {{ add_button_label }}
                </button>
            </div>
        </form>

        <empty-state
            v-if="program_increments.length === 0 && !is_loading && !has_error"
            data-test="empty-state"
        />

        <program-increment-card
            v-for="increment in program_increments"
            v-bind:key="increment.id"
            v-bind:increment="increment"
            data-test="program-increments"
        />

        <backlog-element-skeleton v-if="is_loading" data-test="program-increment-skeleton" />

        <div
            id="program-increment-error"
            class="tlp-alert-danger"
            v-if="has_error"
            data-test="program-increment-error"
        >
            {{ error_message }}
        </div>
    </div>
</template>
<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useNamespacedState } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";
import EmptyState from "./EmptyState.vue";
import ProgramIncrementCard from "./ProgramIncrementCard.vue";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";
import { getProgramIncrements } from "../../../helpers/ProgramIncrement/program-increment-retriever";
import BacklogElementSkeleton from "../BacklogElementSkeleton.vue";
import { buildCreateNewProgramIncrement } from "../../../helpers/location-helper";
import type { ConfigurationState } from "../../../store/configuration";

const { $gettext } = useGettext();

const error_message = ref("");
const has_error = ref(false);
const program_increments = ref<ProgramIncrement[]>([]);
const is_loading = ref(false);

const {
    can_create_program_increment,
    tracker_program_increment_label,
    tracker_program_increment_sub_label,
    tracker_program_increment_id,
    program_id,
} = useNamespacedState<ConfigurationState>("configuration", [
    "can_create_program_increment",
    "tracker_program_increment_label",
    "tracker_program_increment_sub_label",
    "tracker_program_increment_id",
    "program_id",
]);

onMounted(async () => {
    try {
        is_loading.value = true;
        program_increments.value = await getProgramIncrements(program_id.value);
    } catch (e) {
        has_error.value = true;
        error_message.value = $gettext("The retrieval of the program increments has failed");
        throw e;
    } finally {
        is_loading.value = false;
    }
});

const user_can_create_program_increment = computed(
    (): boolean => can_create_program_increment.value && program_increments.value.length > 0,
);

const create_new_program_increment_uri = buildCreateNewProgramIncrement(
    tracker_program_increment_id.value,
);

const add_button_label = $gettext("New %{ program_increment_sub_label }", {
    program_increment_sub_label: tracker_program_increment_sub_label.value,
});

defineExpose({ has_error, error_message });
</script>
