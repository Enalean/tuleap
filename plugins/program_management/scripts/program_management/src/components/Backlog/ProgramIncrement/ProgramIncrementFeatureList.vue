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
        data-is-container="true"
        v-bind:data-can-plan="doesIncrementAcceptPlannableItems()"
        data-test="program-increment-feature-list"
        v-bind:data-program-increment-id="increment.id"
    >
        <backlog-element-skeleton v-if="is_loading" data-test="to-be-planned-skeleton" />

        <program-increment-not-plannable />

        <program-increment-no-content
            v-if="features.length === 0 && !is_loading && !has_error"
            data-test="empty-state"
        />

        <feature-card
            v-for="feature in features"
            v-bind:key="feature.id"
            v-bind:feature="feature"
            v-bind:program_increment="increment"
            data-test="to-be-planned-elements"
            v-bind:data-program-increment-id="increment.id"
        />

        <error-displayer
            v-if="has_error"
            v-bind:message_error_rest="error_message"
            data-test="to-be-planned-error"
        />
    </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useGetters, useActions } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";
import BacklogElementSkeleton from "../BacklogElementSkeleton.vue";
import ProgramIncrementNoContent from "./ProgramIncrementNoContent.vue";
import FeatureCard from "./FeatureCard.vue";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";
import ProgramIncrementNotPlannable from "./ProgramIncrementNotPlannable.vue";
import type { Feature } from "../../../type";
import ErrorDisplayer from "../ErrorDisplayer.vue";

const { $gettext } = useGettext();

const props = defineProps<{ increment: ProgramIncrement }>();

const features = ref<Feature[]>([]);
const error_message = ref("");
const has_error = ref(false);
const is_loading = ref(false);

const { getFeaturesInProgramIncrement, isProgramIncrementAlreadyAdded } = useGetters<{
    getFeaturesInProgramIncrement: () => (program_increment_id: number) => Feature[];
    isProgramIncrementAlreadyAdded: () => (program_increment_id: number) => boolean;
}>(["getFeaturesInProgramIncrement", "isProgramIncrementAlreadyAdded"]);

const { getFeatureAndStoreInProgramIncrement } = useActions([
    "getFeatureAndStoreInProgramIncrement",
]);

onMounted(async () => {
    if (isProgramIncrementAlreadyAdded.value(props.increment.id)) {
        features.value = getFeaturesInProgramIncrement.value(props.increment.id);
        return;
    }

    try {
        is_loading.value = true;
        features.value = await getFeatureAndStoreInProgramIncrement(props.increment);
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

function doesIncrementAcceptPlannableItems(): boolean {
    return props.increment.user_can_plan;
}

defineExpose({ has_error, error_message });
</script>
