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
        <h2 class="program-to-be-planned-title">
            {{ $gettext("To Be Planned") }}
        </h2>
        <div data-is-container="true" v-bind:data-can-plan="has_plan_permissions">
            <program-increment-not-plannable />
            <feature-not-plannable v-if="!has_plan_permissions" />

            <empty-state
                v-if="to_be_planned_elements.length === 0 && !is_loading && !has_error"
                data-test="empty-state"
            />

            <to-be-planned-card
                v-for="feature in to_be_planned_elements"
                v-bind:key="feature.id"
                v-bind:feature="feature"
                data-test="to-be-planned-elements"
            />
        </div>

        <error-displayer
            v-if="has_error"
            v-bind:message_error_rest="error_message"
            data-test="to-be-planned-error"
        />

        <backlog-element-skeleton v-if="is_loading" data-test="to-be-planned-skeleton" />
    </div>
</template>
<script setup lang="ts">
import { computed, ref, onMounted } from "vue";
import { useNamespacedState, useStore, useActions } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";
import EmptyState from "./EmptyState.vue";
import ToBePlannedCard from "./ToBePlannedCard.vue";
import BacklogElementSkeleton from "../BacklogElementSkeleton.vue";
import type { Feature } from "../../../type";
import ErrorDisplayer from "../ErrorDisplayer.vue";
import ProgramIncrementNotPlannable from "../ProgramIncrement/ProgramIncrementNotPlannable.vue";
import FeatureNotPlannable from "./FeatureNotPlannable.vue";
import type { ConfigurationState } from "../../../store/configuration";

const { $gettext } = useGettext();

const error_message = ref("");
const has_error = ref(false);
const is_loading = ref(false);

const store = useStore();
const to_be_planned_elements = computed((): Feature[] => store.state.to_be_planned_elements);

const { program_id, has_plan_permissions } = useNamespacedState<ConfigurationState>(
    "configuration",
    ["program_id", "has_plan_permissions"],
);

const { retrieveToBePlannedElement } = useActions(["retrieveToBePlannedElement"]);

onMounted(async () => {
    try {
        is_loading.value = true;
        await retrieveToBePlannedElement(program_id.value);
    } catch (e) {
        has_error.value = true;
        error_message.value = $gettext(
            "The retrieval of the elements to be planned in program has failed",
        );
        throw e;
    } finally {
        is_loading.value = false;
    }
});

defineExpose({ has_error, error_message });
</script>
