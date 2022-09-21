<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
    <nav class="tlp-wizard">
        <span
            v-for="(current_step, index) in steps"
            v-bind:key="current_step.id"
            v-bind:class="{
                'tlp-wizard-step-previous': index < active_step_index,
                'tlp-wizard-step-current': current_step.id === active_step_id,
                'tlp-wizard-step-next': index > active_step_index,
            }"
        >
            {{ current_step.title }}
        </span>
    </nav>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useGettext } from "vue3-gettext";
import { STEP_GITLAB_SERVER, STEP_GITLAB_GROUP, STEP_GITLAB_CONFIGURATION } from "../types";
import type { GitlabGroupLinkStepName } from "../types";

const props = defineProps<{
    active_step_id: GitlabGroupLinkStepName;
}>();

const { $gettext } = useGettext();

const steps = ref([
    {
        id: STEP_GITLAB_SERVER,
        title: $gettext("Server"),
    },
    {
        id: STEP_GITLAB_GROUP,
        title: $gettext("Group"),
    },
    {
        id: STEP_GITLAB_CONFIGURATION,
        title: $gettext("Configuration"),
    },
]);

const active_step_index = steps.value.findIndex((step) => step.id === props.active_step_id);
</script>
