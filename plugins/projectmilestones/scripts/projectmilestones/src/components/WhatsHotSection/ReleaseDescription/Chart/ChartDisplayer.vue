<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <div class="container-chart-burndown-burnup">
        <div v-if="is_loading" class="release-loader" data-test="loading-data"></div>
        <div v-else-if="!has_rest_error" class="release-charts-row">
            <div
                v-if="burndown_exists"
                class="release-chart-displayer release-chart-displayer-burndown"
            >
                <h2 class="tlp-pane-subtitle">{{ burndown_label }}</h2>
                <burndown-displayer
                    v-bind:release_data="release_data"
                    v-bind:burndown_data="burndown_data"
                />
            </div>
            <div
                v-if="burnup_exists"
                data-test="burnup-exists"
                class="release-chart-displayer release-chart-displayer-burnup"
            >
                <h2 class="tlp-pane-subtitle project-milestones-chart-label">{{ burnup_label }}</h2>
                <burnup-displayer
                    v-bind:release_data="release_data"
                    v-bind:burnup_data="burnup_data"
                />
            </div>
        </div>
        <div v-if="has_rest_error" class="tlp-alert-danger" data-test="error-rest">
            {{ message_error_rest }}
        </div>
    </div>
</template>

<script setup lang="ts">
import type { BurndownData, BurnupData, MilestoneData } from "../../../../type";
import type { Ref } from "vue";
import { computed, onMounted, ref } from "vue";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import { getChartData } from "../../../../api/rest-querier";
import { getBurndownDataFromType, getBurnupDataFromType } from "../../../../helpers/chart-helper";
import BurndownDisplayer from "./Burndown/BurndownDisplayer.vue";
import BurnupDisplayer from "./Burnup/BurnupDisplayer.vue";
import { useGettext } from "vue3-gettext";

const props = defineProps<{ release_data: MilestoneData }>();

const { $gettext } = useGettext();

let is_loading = ref(true);
let message_error_rest: Ref<string | null> = ref(null);
let has_rest_error = ref(false);
let burndown_data: Ref<BurndownData | null> = ref(null);
let burnup_data: Ref<BurnupData | null> = ref(null);

onMounted(async () => {
    burndown_data.value = props.release_data.burndown_data;
    burnup_data.value = props.release_data.burnup_data;
    if (is_loading.value && (!burndown_data.value || !burnup_data.value)) {
        try {
            const burndown_values = await getChartData(props.release_data.id);
            burndown_data.value = getBurndownDataFromType(burndown_values);
            burnup_data.value = getBurnupDataFromType(burndown_values);
        } catch (rest_error) {
            has_rest_error.value = true;
            await handle_error(rest_error);
        } finally {
            is_loading.value = false;
        }
    } else {
        is_loading.value = false;
    }
});

const emit = defineEmits<{
    (e: "burndown-exists"): void;
    (e: "burnup-exists"): void;
}>();

const burndown_exists = computed((): boolean => {
    if (burndown_data.value !== null) {
        emit("burndown-exists");
        return true;
    }
    return false;
});
const burnup_exists = computed((): boolean => {
    if (burnup_data.value !== null) {
        emit("burnup-exists");
        return true;
    }
    return false;
});

const burndown_label = computed((): string => {
    if (burndown_data.value && burndown_data.value.label) {
        return burndown_data.value.label;
    }

    return $gettext("Burndown");
});
const burnup_label = computed((): string => {
    if (burnup_data.value && burnup_data.value.label) {
        return burnup_data.value.label;
    }

    return $gettext("Burnup");
});

async function handle_error(rest_error: unknown): Promise<void> {
    try {
        if (!(rest_error instanceof FetchWrapperError)) {
            return;
        }
        const { error } = await rest_error.response.json();
        message_error_rest.value = error.code + " " + error.message;
    } catch (error) {
        message_error_rest.value = $gettext("Oops, an error occurred!");
    } finally {
        is_loading.value = false;
    }
}
</script>
