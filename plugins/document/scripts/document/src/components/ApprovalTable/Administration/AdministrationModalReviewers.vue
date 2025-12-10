<!--
  - Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
    <h2 class="tlp-modal-subtitle">{{ $gettext("Reviewers") }}</h2>
    <span class="tlp-text-info">
        {{
            $gettext(
                "Table is not saved when you change it, please click on 'Update' button to save it.",
            )
        }}
    </span>
    <table class="tlp-table reviewers-table">
        <thead>
            <tr>
                <th>{{ $gettext("Name") }}</th>
                <th>{{ $gettext("Review") }}</th>
                <th>{{ $gettext("Rank") }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <tr v-if="table_reviewers_value.length === 0">
                <td colspan="4" class="tlp-table-cell-empty">
                    {{ $gettext("There isn't any reviewers") }}
                </td>
            </tr>
            <tr
                v-else
                v-for="(reviewer, index) in table_reviewers_value"
                v-bind:key="reviewer.user.id"
                data-test="reviewer-row"
            >
                <td>
                    <user-badge v-bind:user="reviewer.user" />
                </td>
                <td>{{ translateReviewStatus(reviewer.state, $gettext) }}</td>
                <td>
                    <button
                        class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline"
                        v-on:click="updateRank(reviewer, index - 1)"
                        v-bind:disabled="index === 0 || is_doing_something"
                        data-test="rank-up"
                    >
                        <i class="fa-solid fa-angle-up tlp-button-icon"></i>
                    </button>
                    <button
                        class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline"
                        v-on:click="updateRank(reviewer, 0)"
                        v-bind:disabled="index === 0 || is_doing_something"
                        data-test="rank-top"
                    >
                        <i class="fa-solid fa-angles-up tlp-button-icon"></i>
                    </button>
                    <button
                        class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline"
                        v-on:click="updateRank(reviewer, index + 1)"
                        v-bind:disabled="
                            index === table_reviewers_value.length - 1 || is_doing_something
                        "
                        data-test="rank-down"
                    >
                        <i class="fa-solid fa-angle-down tlp-button-icon"></i>
                    </button>
                    <button
                        class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline"
                        v-on:click="updateRank(reviewer, table_reviewers_value.length - 1)"
                        v-bind:disabled="
                            index === table_reviewers_value.length - 1 || is_doing_something
                        "
                        data-test="rank-end"
                    >
                        <i class="fa-solid fa-angles-down tlp-button-icon"></i>
                    </button>
                </td>
                <td class="tlp-table-cell-actions">
                    <button
                        class="tlp-table-cell-actions-button tlp-button-small tlp-button-info tlp-button-outline"
                        v-on:click="sendReminder(reviewer)"
                        v-bind:disabled="is_doing_something"
                        data-test="reviewer-send-reminder"
                    >
                        <i
                            v-if="is_sending_to === reviewer.user.id"
                            class="tlp-button-icon fa-solid fa-spin fa-circle-notch"
                            aria-hidden="true"
                        ></i>
                        <i
                            v-else
                            class="fa-solid fa-paper-plane tlp-button-icon"
                            aria-hidden="true"
                        ></i>
                        {{ $gettext("Send reminder") }}
                    </button>
                    <button
                        class="tlp-table-cell-actions-button tlp-button-small tlp-button-danger tlp-button-outline"
                        v-on:click="removeReviewer(reviewer)"
                        v-bind:disabled="is_doing_something"
                        data-test="remove-reviewer"
                    >
                        <i class="fa-solid fa-trash tlp-button-icon" aria-hidden="true"></i>
                        {{ $gettext("Remove") }}
                    </button>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="add-reviewer-section">
        <div class="tlp-form-element">
            <label class="tlp-label" for="user-lazybox">
                {{ $gettext("Append users to the table") }}
            </label>
            <tuleap-lazybox id="user-lazybox" ref="user_lazybox" />
        </div>
        <div class="tlp-form-element">
            <label class="tlp-label" for="user-group-list-picker">
                {{ $gettext("Append members of user groups to the table") }}
            </label>
            <select ref="user_group_picker" name="list-value" id="user-group-list-picker" multiple>
                <option value=""></option>
                <option
                    v-for="user_group in user_groups"
                    v-bind:key="user_group.id"
                    v-bind:value="user_group.short_name"
                >
                    {{ user_group.label }}
                </option>
            </select>
        </div>
    </div>
</template>
<script setup lang="ts">
import type { ApprovalTableReviewer, Item, UserGroup } from "../../../type";
import UserBadge from "../../User/UserBadge.vue";
import {
    rearrangeReviewersTable,
    translateReviewStatus,
} from "../../../helpers/approval-table-helper";
import { onMounted, onUnmounted, ref } from "vue";
import { postApprovalTableReviewerReminder } from "../../../api/approval-table-rest-querier";
import { useGettext } from "vue3-gettext";
import { sprintf } from "sprintf-js";
import "@tuleap/lazybox";
import type { Lazybox } from "@tuleap/lazybox";
import type { ListPicker } from "@tuleap/list-picker";
import { createListPicker } from "@tuleap/list-picker";
import type { User } from "@tuleap/core-rest-api-types";
import { loadProjectUserGroups } from "../../../helpers/permissions/ugroups";
import { useStore } from "vuex-composition-helpers";
import { PROJECT, USER_LOCALE } from "../../../configuration-keys";
import { strictInject } from "@tuleap/vue-strict-inject";
import { initUsersAutocompleter } from "@tuleap/lazybox-users-autocomplete";

const { $gettext } = useGettext();
const $store = useStore();

const props = defineProps<{
    item: Item;
    is_doing_something: boolean;
}>();

const emit = defineEmits<{
    (e: "error-message", message: string): void;
    (e: "success-message", message: string): void;
}>();

const table_reviewers_value = defineModel<Array<ApprovalTableReviewer>>("table_reviewers_value", {
    required: true,
});
const table_reviewers_to_add_value = defineModel<Array<User>>("table_reviewers_to_add_value", {
    required: true,
});
const table_reviewers_group_to_add_value = defineModel<Array<UserGroup>>(
    "table_reviewers_group_to_add_value",
    { required: true },
);
const is_sending_reminder = defineModel<boolean>("is_sending_reminder");

const is_sending_to = ref<number | null>(null);
const user_group_picker = ref<HTMLSelectElement>();
const user_lazybox = ref<Lazybox>();
const list_picker = ref<ListPicker>();
const user_groups = ref<ReadonlyArray<UserGroup>>([]);

const user_locale = strictInject(USER_LOCALE);
const project = strictInject(PROJECT);

onMounted(() => {
    if (user_group_picker.value === undefined) {
        throw new Error("Cannot find user group picker element");
    }
    if (user_lazybox.value === undefined) {
        throw new Error("Cannot find user lazybox element");
    }

    loadProjectUserGroups($store, project.id).match(
        (groups) => {
            user_groups.value = groups;
        },
        (fault) => {
            emit("error-message", fault.toString());
        },
    );

    list_picker.value = createListPicker(user_group_picker.value, {
        locale: user_locale,
        placeholder: $gettext("Choose zero, one or multiple user group"),
    });
    user_group_picker.value.addEventListener("change", () => {
        if (user_group_picker.value === undefined) {
            return;
        }
        const selected_options = user_group_picker.value.selectedOptions;
        const selected_groups = [];
        for (const selected of selected_options) {
            const user_group = user_groups.value.find(
                (group) => group.short_name === selected.value,
            );
            if (user_group !== undefined) {
                selected_groups.push(user_group);
            }
        }
        table_reviewers_group_to_add_value.value = selected_groups;
    });

    initUsersAutocompleter(
        user_lazybox.value,
        [],
        (selected_users: ReadonlyArray<User>): void => {
            table_reviewers_to_add_value.value = [...selected_users];
        },
        user_locale,
    );
});

onUnmounted(() => {
    list_picker.value?.destroy();
});

function updateRank(updated_reviewer: ApprovalTableReviewer, new_rank: number): void {
    table_reviewers_value.value = rearrangeReviewersTable(
        table_reviewers_value.value,
        updated_reviewer,
        new_rank,
    );
}

function removeReviewer(removed_reviewer: ApprovalTableReviewer): void {
    let result: Array<ApprovalTableReviewer> = [];

    let index = 0;
    table_reviewers_value.value.forEach((reviewer) => {
        if (reviewer.user.id === removed_reviewer.user.id) {
            return;
        }
        result.push({
            ...reviewer,
            rank: index,
        });
        index++;
    });

    table_reviewers_value.value = result;
}

function sendReminder(reviewer: ApprovalTableReviewer): void {
    is_sending_reminder.value = true;
    is_sending_to.value = reviewer.user.id;
    postApprovalTableReviewerReminder(props.item.id, reviewer.user.id).match(
        () => {
            is_sending_reminder.value = false;
            is_sending_to.value = null;
            emit(
                "success-message",
                sprintf($gettext("Reminder sent to %s"), reviewer.user.display_name),
            );
        },
        (fault) => {
            is_sending_reminder.value = false;
            is_sending_to.value = null;
            emit("error-message", fault.toString());
        },
    );
}
</script>

<style scoped lang="scss">
.reviewers-table {
    margin-bottom: var(--tlp-small-spacing);
}

.add-reviewer-section {
    display: flex;
    gap: var(--tlp-medium-spacing);

    > .tlp-form-element {
        flex: 1;
    }
}
</style>
