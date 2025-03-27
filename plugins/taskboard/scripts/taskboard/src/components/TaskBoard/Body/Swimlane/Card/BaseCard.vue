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
    <div ref="root" class="taskboard-card" v-bind:class="additional_classnames">
        <div class="taskboard-card-content">
            <card-xref-label v-bind:card="card" v-bind:label="label" />
            <card-info v-bind:card="card" v-bind:tracker="tracker" v-model="assignees">
                <template #initial_effort>
                    <slot name="initial_effort" />
                </template>
            </card-info>
        </div>
        <button
            v-if="can_user_update_card"
            class="taskboard-card-edit-trigger"
            v-on:click="switchToEditMode"
            data-test="card-edit-button"
            type="button"
            v-bind:title="$gettext('Edit card')"
            data-shortcut="edit-card"
        >
            <i class="fas fa-pencil-alt" aria-hidden="true"></i>
        </button>
        <label-editor v-model="label" v-if="card.is_in_edit_mode" v-on:save="save" />
        <div class="taskboard-card-accessibility" v-if="show_accessibility_pattern"></div>
        <slot name="remaining_effort" />
    </div>
</template>
<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from "vue";
import {
    useGetters,
    useNamespacedActions,
    useNamespacedMutations,
    useNamespacedState,
} from "vuex-composition-helpers";
import CardXrefLabel from "./CardXrefLabel.vue";
import type { Card, Tracker, User } from "../../../../../type";
import { TaskboardEvent } from "../../../../../type";
import EventBus from "../../../../../helpers/event-bus";
import type { UpdateCardPayload } from "../../../../../store/swimlane/card/type";
import LabelEditor from "./Editor/Label/LabelEditor.vue";
import CardInfo from "./CardInfo.vue";
import { haveAssigneesChanged } from "../../../../../helpers/have-assignees-changed";
import { scrollToItemIfNeeded } from "../../../../../helpers/scroll-to-item";
import type { UserState } from "../../../../../store/user/type";
import type { FullscreenState } from "../../../../../store/fullscreen/type";

const { user_has_accessibility_mode } = useNamespacedState<UserState>("user", [
    "user_has_accessibility_mode",
]);

const props = defineProps<{ card: Card }>();

const emit = defineEmits<{
    (e: "editor-closed"): void;
}>();

const { tracker_of_card } = useGetters(["tracker_of_card"]);

const { addCardToEditMode, removeCardFromEditMode } = useNamespacedMutations("swimlane", [
    "addCardToEditMode",
    "removeCardFromEditMode",
]);

const { saveCard } = useNamespacedActions("swimlane", ["saveCard"]);

const { is_taskboard_in_fullscreen_mode } = useNamespacedState<FullscreenState>("fullscreen", [
    "is_taskboard_in_fullscreen_mode",
]);

const root = ref<HTMLElement>();
const label = ref("");
const assignees = ref<User[]>([]);

const tracker = computed((): Tracker => tracker_of_card.value(props.card));
const can_user_update_card = computed(
    (): boolean => tracker.value.title_field !== null && !props.card.is_being_saved,
);
const show_accessibility_pattern = computed(
    (): boolean => user_has_accessibility_mode.value && props.card.background_color.length > 0,
);
const is_label_changed = computed((): boolean => label.value !== props.card.label);

onMounted(() => {
    label.value = props.card.label;
    assignees.value = props.card.assignees;
    EventBus.$on(TaskboardEvent.CANCEL_CARD_EDITION, cancelButtonCallback);
    EventBus.$on(TaskboardEvent.SAVE_CARD_EDITION, saveButtonCallback);
});

onUnmounted(() => {
    EventBus.$off(TaskboardEvent.CANCEL_CARD_EDITION, cancelButtonCallback);
    EventBus.$off(TaskboardEvent.SAVE_CARD_EDITION, saveButtonCallback);
});

function cancelButtonCallback(card: Card): void {
    if (card.id === props.card.id) {
        cancel();
    }
}

function saveButtonCallback(card: Card): void {
    if (card.id === props.card.id) {
        save();
    }
}

function save(): void {
    if (!is_label_changed.value && !haveAssigneesChanged(props.card.assignees, assignees.value)) {
        cancel();
        return;
    }

    const payload: UpdateCardPayload = {
        card: props.card,
        label: label.value,
        assignees: assignees.value,
        tracker: tracker.value,
    };
    saveCard(payload);

    emit("editor-closed");
}

function cancel(): void {
    removeCardFromEditMode(props.card);
    label.value = props.card.label;
    emit("editor-closed");
}

function switchToEditMode(): void {
    if (props.card.is_in_edit_mode) {
        return;
    }

    if (props.card.is_being_saved) {
        return;
    }

    addCardToEditMode(props.card);

    setTimeout((): void => {
        let fullscreen_element = null;

        if (is_taskboard_in_fullscreen_mode.value) {
            fullscreen_element = document.querySelector(".taskboard");
        }

        if (root.value) {
            scrollToItemIfNeeded(root.value, fullscreen_element);
        }
    }, 100);
}

const additional_classnames = computed((): string => {
    const classnames = [`taskboard-card-${props.card.color}`];

    if (props.card.background_color) {
        classnames.push(`taskboard-card-background-${props.card.background_color}`);
    }

    if (show_accessibility_pattern.value) {
        classnames.push("taskboard-card-with-accessibility");
    }

    if (props.card.is_in_edit_mode) {
        classnames.push("taskboard-card-edit-mode");
    } else if (props.card.is_being_saved) {
        classnames.push("taskboard-card-is-being-saved");
    } else if (props.card.is_just_saved) {
        classnames.push("taskboard-card-is-just-saved");
    }

    if (can_user_update_card.value) {
        classnames.push("taskboard-card-editable");
    }

    return classnames.join(" ");
});

defineExpose({ label });
</script>
