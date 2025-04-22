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
    <button
        v-if="!is_editing_assignees"
        type="button"
        class="taskboard-card-assignees-button"
        v-bind:aria-label="edit_assignees_label"
        v-bind:title="edit_assignees_label"
        v-on:click="editAssignees"
        data-test="edit-assignees"
    >
        <i v-bind:class="user_edit_classes" aria-hidden="true" data-test="icon"></i>
        <user-avatar
            v-for="assignee in card.assignees"
            class="taskboard-card-assignees-avatars"
            v-bind:user="assignee"
            v-bind:key="assignee.id"
        />
    </button>
    <div class="taskboard-card-assignees-edit-mode" v-if="is_editing_assignees">
        <people-picker
            v-bind:is_multiple="is_multiple"
            v-bind:users="users"
            v-bind:value="assignee_ids"
            v-on:input="onAssigneesEdit"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useNamespacedActions, useNamespacedGetters } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";
import type { Card, Tracker } from "../../../../../type";
import type { UserForPeoplePicker } from "../../../../../store/swimlane/card/UserForPeoplePicker";
import UserAvatar from "./UserAvatar.vue";
import PeoplePicker from "./Editor/Assignees/PeoplePicker.vue";

const { $ngettext } = useGettext();

const { loadPossibleAssignees } = useNamespacedActions("swimlane", ["loadPossibleAssignees"]);

const { assignable_users } = useNamespacedGetters("swimlane", ["assignable_users"]);

const props = defineProps<{
    card: Card;
    tracker: Tracker;
}>();

const emit = defineEmits<{
    (e: "input", user_for_people_picker: UserForPeoplePicker[]): void;
}>();

const is_editing_assignees = ref(false);
const possible_users = ref<UserForPeoplePicker[]>([]);
const is_loading_users = ref(false);

watch(
    () => props.card.is_in_edit_mode,
    (is_in_edit_mode: boolean) => {
        if (!is_in_edit_mode) {
            is_editing_assignees.value = false;
        }
    },
);

const user_edit_classes = computed((): string[] => {
    if (is_loading_users.value) {
        return ["fa-solid", "fa-circle-notch", "fa-spin", "taskboard-card-assignees-loading-icon"];
    }

    if (props.card.assignees.length >= 1) {
        return ["fa", "fa-tlp-user-pencil", "taskboard-card-assignees-edit-icon"];
    }

    return ["fa-solid", "fa-user-plus", "taskboard-card-assignees-add-icon"];
});

const is_multiple = computed((): boolean => {
    return Boolean(props.tracker.assigned_to_field?.is_multiple);
});

const edit_assignees_label = computed((): string => {
    const number = is_multiple.value ? 2 : 1;

    return $ngettext("Edit assignee", "Edit assignees", number);
});

async function editAssignees(): Promise<void> {
    if (is_editing_assignees.value) {
        return;
    }

    await loadUsers();
    is_editing_assignees.value = true;
}

async function loadUsers(): Promise<void> {
    is_loading_users.value = true;

    await loadPossibleAssignees(props.tracker);
    possible_users.value = assignable_users.value(props.tracker);

    is_loading_users.value = false;
}

const users = computed((): UserForPeoplePicker[] => {
    return possible_users.value.map((user): UserForPeoplePicker => {
        const selected = props.card.assignees.some((selected_user) => selected_user.id === user.id);

        return { ...user, text: user.display_name, selected };
    });
});

const assignee_ids = computed((): number[] => props.card.assignees.map((user) => user.id));

function onAssigneesEdit(new_assignee_ids: number[]): void {
    emit(
        "input",
        users.value.filter((user) => new_assignee_ids.includes(user.id)),
    );
}
</script>
