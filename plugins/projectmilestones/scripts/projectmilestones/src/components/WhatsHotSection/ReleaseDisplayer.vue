<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
        class="project-release"
        v-bind:class="{
            'project-release-toggle-closed': !is_open,
            'tlp-tooltip tlp-tooltip-top': is_loading,
        }"
        v-bind:data-tlp-tooltip="$gettext('Loading data...')"
    >
        <release-header
            v-on:toggle-release-details="toggleReleaseDetails()"
            v-bind:release_data="displayed_release"
            v-bind:is-loading="is_loading"
            v-bind:class="{ 'project-release-toggle-closed': !is_open, disabled: is_loading }"
            v-bind:is-past-release="isPastRelease"
        />
        <div v-if="is_open" data-test="toggle-open" class="release-toggle">
            <div v-if="has_error" class="tlp-alert-danger" data-test="show-error-message">
                {{ error_message }}
            </div>
            <div v-else data-test="display-release-data">
                <release-badges-displayer
                    v-bind:release_data="displayed_release"
                    v-bind:is-open="isOpen"
                    v-bind:is-past-release="isPastRelease"
                />
                <release-description v-bind:release_data="displayed_release" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import ReleaseBadgesDisplayer from "./ReleaseBadges/ReleaseBadgesDisplayer.vue";
import ReleaseDescription from "./ReleaseDescription/ReleaseDescription.vue";
import ReleaseHeader from "./ReleaseHeader/ReleaseHeader.vue";
import type { Ref } from "vue";
import { computed, ref, onMounted } from "vue";
import type { MilestoneData } from "../../type";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import { is_testplan_activated } from "../../helpers/test-management-helper";
import { useStore } from "../../stores/root";
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";

const root_store = useStore();

const gettext_provider = useGettext();

const props = defineProps<{
    release_data: MilestoneData;
    isPastRelease: boolean;
    isOpen: boolean;
}>();

let is_open = ref(false);
let is_loading = ref(true);
let error_message: Ref<string | null> = ref(null);
let release_data_enhanced: Ref<MilestoneData | null> = ref(null);

const has_error = computed((): boolean => {
    return error_message.value !== null;
});
const displayed_release = computed((): MilestoneData => {
    return release_data_enhanced.value ? release_data_enhanced.value : props.release_data;
});

onMounted(async () => {
    try {
        release_data_enhanced.value = await root_store.getEnhancedMilestones(props.release_data);
        is_open.value = props.isOpen;
        if (
            props.isPastRelease &&
            is_testplan_activated(props.release_data) &&
            release_data_enhanced.value !== null
        ) {
            release_data_enhanced.value.campaign = await root_store.getTestManagementCampaigns(
                release_data_enhanced.value,
            );
        }
    } catch (rest_error) {
        await handle_error(rest_error);
    } finally {
        is_loading.value = false;
    }
});

async function handle_error(rest_error: unknown): Promise<void> {
    if (!(rest_error instanceof FetchWrapperError) || rest_error.response === undefined) {
        error_message.value = gettext_provider.$gettext("Oops, an error occurred!");
        throw rest_error;
    }
    try {
        const { error } = await rest_error.response.json();
        error_message.value = error.code + " " + error.message;
    } catch (error) {
        error_message.value = gettext_provider.$gettext("Oops, an error occurred!");
    }
}

function toggleReleaseDetails(): void {
    if (!is_loading.value || is_open.value) {
        is_open.value = !is_open.value;
    }
}
</script>
