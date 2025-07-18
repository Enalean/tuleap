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
  -
  -->

<template>
    <swimlane-header v-bind:swimlane="swimlane">
        <card-with-remaining-effort
            class="taskboard-cell-parent-card"
            v-bind:class="edit_mode_class"
            v-bind:card="swimlane.card"
        />
        <no-mapping-message
            v-if="should_no_mapping_message_be_displayed"
            v-bind:card="swimlane.card"
        />
    </swimlane-header>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { Swimlane } from "../../../../type";
import NoMappingMessage from "./Header/NoMappingMessage.vue";
import SwimlaneHeader from "./Header/SwimlaneHeader.vue";
import CardWithRemainingEffort from "./Card/CardWithRemainingEffort.vue";

const props = defineProps<{
    swimlane: Swimlane;
}>();

const should_no_mapping_message_be_displayed = computed((): boolean => {
    return !props.swimlane.card.has_children;
});

const edit_mode_class = computed((): string[] => {
    const classes = [];

    if (should_no_mapping_message_be_displayed.value) {
        classes.push("taskboard-cell-parent-card-no-mapping");
    }

    if (props.swimlane.card.is_in_edit_mode) {
        classes.push("taskboard-cell-parent-card-edit-mode");
    }

    return classes;
});

defineExpose({ edit_mode_class, should_no_mapping_message_be_displayed });
</script>
