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
    <span
        v-if="has_initial_effort"
        class="taskboard-card-inital-effort tlp-badge-outline tlp-badge-rounded taskboard-no-text-selection"
        v-bind:class="color_classname"
        v-bind:title="initial_effort_label"
    >
        <i class="fa fa-flag-checkered fa-flip-horizontal tlp-badge-icon" aria-hidden="true"></i>
        {{ card.initial_effort }}
    </span>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import type { Card } from "../../../../../type";

const { $gettext } = useGettext();

const props = defineProps<{
    card: Card;
}>();

const initial_effort_label = computed((): string => {
    return $gettext("Initial effort");
});

const has_initial_effort = computed((): boolean => {
    return props.card.initial_effort !== null;
});

const color_classname = computed((): string => {
    return `tlp-badge-${props.card.color}`;
});
</script>
