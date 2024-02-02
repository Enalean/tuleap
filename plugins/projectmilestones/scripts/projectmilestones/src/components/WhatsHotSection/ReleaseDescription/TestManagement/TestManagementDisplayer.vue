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
    <div v-if="is_testmanagement_available" class="container-chart-ttm">
        <div v-if="is_loading" class="release-loader" data-test="loading-data"></div>
        <div v-else-if="has_rest_error || are_some_tests_to_display" class="release-ttm-section">
            <h2 class="tlp-pane-subtitle">{{ title_label }}</h2>
            <div v-if="has_rest_error" class="tlp-alert-danger" data-test="error-rest">
                {{ message_error_rest }}
            </div>
            <test-management
                v-else-if="are_some_tests_to_display"
                v-bind:release_data="release_data"
                v-bind:campaign="campaign"
            />
        </div>
    </div>
</template>
<script setup lang="ts">
import type { Ref } from "vue";
import { computed, onMounted, ref } from "vue";
import type { MilestoneData, TestManagementCampaign } from "../../../../type";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import { is_testplan_activated } from "../../../../helpers/test-management-helper";
import TestManagement from "./TestManagement.vue";
import { useStore } from "../../../../stores/root";
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";

const props = defineProps<{ release_data: MilestoneData }>();
const root_store = useStore();
const { $gettext } = useGettext();
let is_loading = ref(true);
let message_error_rest: Ref<string | null> = ref(null);
let campaign: Ref<TestManagementCampaign | null> = ref(null);
const title_label = $gettext("Tests Results");
const has_rest_error = computed((): boolean => {
    return message_error_rest.value !== null;
});
onMounted(async (): Promise<void> => {
    campaign.value = props.release_data.campaign;
    if (!campaign.value) {
        try {
            campaign.value = await root_store.getTestManagementCampaigns(props.release_data);
        } catch (rest_error) {
            await handle_error(rest_error);
        } finally {
            is_loading.value = false;
        }
    } else {
        is_loading.value = false;
    }
});

async function handle_error(rest_error: unknown): Promise<void> {
    try {
        if (!(rest_error instanceof FetchWrapperError)) {
            throw rest_error;
        }
        const { error } = await rest_error.response.json();
        message_error_rest.value = error.code + " " + error.message;
    } catch (error) {
        message_error_rest.value = $gettext("Oops, an error occurred!");
        throw error;
    }
}

const is_testmanagement_available = computed((): boolean => {
    return is_testplan_activated(props.release_data);
});
const emit = defineEmits<{
    (e: "ttm-exists"): void;
}>();
const are_some_tests_to_display = computed((): boolean => {
    if (!campaign.value) {
        return false;
    }
    if (
        campaign.value.nb_of_notrun > 0 ||
        campaign.value.nb_of_failed > 0 ||
        campaign.value.nb_of_passed > 0 ||
        campaign.value.nb_of_blocked > 0
    ) {
        emit("ttm-exists");
        return true;
    }
    return false;
});
</script>
