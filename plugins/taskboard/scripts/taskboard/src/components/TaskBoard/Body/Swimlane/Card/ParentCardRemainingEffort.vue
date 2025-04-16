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
        v-if="has_remaining_effort"
        class="taskboard-card-remaining-effort taskboard-no-text-selection"
        v-bind:class="additional_classes"
        v-on:click="updateRemainingEffort"
        v-on:keyup.enter="updateRemainingEffort"
        v-bind:tabindex="tabindex"
        v-bind:role="role"
        v-bind:title="$gettext('Remaining effort')"
        data-not-drag-handle="true"
        draggable="true"
        data-shortcut="edit-remaining-effort"
    >
        <edit-remaining-effort
            v-if="is_in_edit_mode"
            v-bind:card="card"
            v-on:editor-closed="$emit('editor-closed')"
            data-test="edit-remaining-effort"
        />
        <template v-else>{{ remaining_effort }}</template>
        <i class="fas fa-long-arrow-alt-right" aria-hidden="true"></i>
        <i class="fa" aria-hidden="true" v-bind:class="icon"></i>
    </span>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { Card } from "../../../../../type";
import EditRemainingEffort from "./RemainingEffort/EditRemainingEffort.vue";

const props = defineProps<{
    card: Card;
}>();

const has_remaining_effort = computed((): boolean => {
    return (
        props.card &&
        props.card.remaining_effort !== null &&
        props.card.remaining_effort.value !== null
    );
});

const remaining_effort = computed((): string => {
    if (!props.card.remaining_effort?.value) {
        return "";
    }

    return String(props.card.remaining_effort.value);
});

const can_update_remaining_effort = computed((): boolean => {
    if (!props.card.remaining_effort) {
        return false;
    }

    return props.card.remaining_effort.can_update;
});
const is_in_edit_mode = computed((): boolean => {
    if (!props.card.remaining_effort) {
        return false;
    }

    return props.card.remaining_effort.is_in_edit_mode;
});

const additional_classes = computed((): string => {
    const classes = [`tlp-badge-${props.card.color}`, `tlp-swatch-${props.card.color}`];

    if (can_update_remaining_effort.value) {
        classes.push("taskboard-card-remaining-effort-editable");
    }

    if (is_in_edit_mode.value) {
        classes.push("taskboard-card-remaining-effort-edit-mode");
    }

    return classes.join(" ");
});

const icon = computed((): string => {
    if (props.card.remaining_effort && props.card.remaining_effort.is_being_saved) {
        return "fa-circle-o-notch fa-spin";
    }

    return "fa-flag-checkered";
});

const role = computed((): string => {
    return can_update_remaining_effort.value ? "button" : "";
});

const tabindex = computed((): number => {
    return can_update_remaining_effort.value ? 0 : -1;
});

function updateRemainingEffort(): void {
    if (can_update_remaining_effort.value && props.card.remaining_effort) {
        // eslint-disable-next-line vue/no-mutating-props
        props.card.remaining_effort.is_in_edit_mode = true;
    }
}
</script>
