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
    <div
        class="taskboard-card-assignees"
        v-bind:class="classes"
        v-bind:role="role"
        v-bind:tabindex="tabindex"
        v-bind:aria-label="edit_assignees_label"
        v-bind:title="edit_assignees_label"
        v-on:click="editAssignees"
        v-on:keyup.enter="editAssignees"
    >
        <people-picker
            v-bind:is_multiple="is_multiple"
            v-bind:users="users"
            v-bind:value="new_assignees_ids"
            v-on:input="new_assignees_ids = $event"
            v-if="is_in_edit_mode_ref"
        />
        <template v-else>
            <i
                class="fa"
                v-bind:class="user_edit_classes"
                v-if="is_user_edit_displayed"
                aria-hidden="true"
                data-test="icon"
            ></i>
            <user-avatar
                v-for="assignee in card.assignees"
                class="taskboard-card-assignees-avatars"
                v-bind:user="assignee"
                v-bind:key="assignee.id"
            />
        </template>
    </div>
</template>

<script setup lang="ts">
import type { WritableComputedRef } from "vue";
import { ref, watch, computed } from "vue";
import { useNamespacedActions, useNamespacedGetters } from "vuex-composition-helpers";
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";
import type { Card, Tracker, User } from "../../../../../type";
import type { UserForPeoplePicker } from "../../../../../store/swimlane/card/UserForPeoplePicker";
import UserAvatar from "./UserAvatar.vue";
import PeoplePicker from "./Editor/Assignees/PeoplePicker.vue";

const { $ngettext } = useGettext();

const { loadPossibleAssignees } = useNamespacedActions("swimlane", ["loadPossibleAssignees"]);

const { assignable_users } = useNamespacedGetters("swimlane", ["assignable_users"]);

const props = defineProps<{
    value: User[];
    card: Card;
    tracker: Tracker;
}>();

const emit = defineEmits<{
    (e: "input", user_for_people_picker: UserForPeoplePicker[]): void;
}>();

const is_in_edit_mode_ref = ref(false);
const possible_users = ref<UserForPeoplePicker[]>([]);
const is_loading_users = ref(false);

watch(
    () => props.card.is_in_edit_mode,
    (is_in_edit_mode: boolean) => {
        if (!is_in_edit_mode) {
            is_in_edit_mode_ref.value = false;
        }
    },
);

const is_updatable = computed((): boolean => {
    return props.tracker.assigned_to_field !== null;
});

const classes = computed((): string[] => {
    if (!props.card.is_in_edit_mode) {
        return [];
    }

    const classes = ["taskboard-card-edit-mode-assignees"];

    if (is_in_edit_mode_ref.value) {
        classes.push("taskboard-card-assignees-edit-mode");
    } else if (is_updatable.value) {
        classes.push("taskboard-card-assignees-editable");
    }

    return classes;
});

const user_edit_classes = computed((): string[] => {
    if (is_loading_users.value) {
        return ["fa-circle-o-notch", "fa-spin", "taskboard-card-assignees-loading-icon"];
    }

    if (props.card.assignees.length >= 1) {
        return ["fa-tlp-user-pencil", "taskboard-card-assignees-edit-icon"];
    }

    return ["fa-user-plus", "taskboard-card-assignees-add-icon"];
});

const is_user_edit_displayed = computed((): boolean => {
    return props.card.is_in_edit_mode && is_updatable.value;
});

const is_multiple = computed((): boolean => {
    return Boolean(props.tracker.assigned_to_field?.is_multiple);
});

const edit_assignees_label = computed((): string => {
    if (!is_user_edit_displayed.value) {
        return "";
    }

    const number = is_multiple.value ? 2 : 1;

    return $ngettext("Edit assignee", "Edit assignees", number);
});

const role = computed((): string => {
    return is_user_edit_displayed.value ? "button" : "";
});

const tabindex = computed((): number => {
    return is_user_edit_displayed.value ? 0 : -1;
});

async function editAssignees(): Promise<void> {
    if (!props.card.is_in_edit_mode || is_in_edit_mode_ref.value) {
        return;
    }

    await loadUsers();
    is_in_edit_mode_ref.value = true;
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

const new_assignees_ids: WritableComputedRef<number[]> = computed({
    get: (): number[] => props.value.map((user) => user.id),
    set: (value: number[]) =>
        emit(
            "input",
            users.value.filter((user) => value.some((id) => id === user.id)),
        ),
});
</script>
