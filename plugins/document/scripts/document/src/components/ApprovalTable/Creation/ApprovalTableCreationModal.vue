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
    <button
        type="button"
        class="tlp-button-primary"
        data-target-modal-id="table-creation-modal"
        data-test="creation-button"
        v-on:click="modal?.show()"
    >
        {{ $gettext("Create a new one") }}
    </button>

    <div
        ref="modal_div"
        id="table-creation-modal"
        role="dialog"
        aria-labelledby="table-creation-modal-label"
        class="tlp-modal"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="modal-label">
                {{ $gettext("Create a new approval table") }}
            </h1>
            <button class="tlp-modal-close" type="button" data-dismiss="modal" aria-label="Close">
                <i class="fa-solid fa-xmark tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body">
            <h2 class="tlp-modal-subtitle">{{ $gettext("Add reviewers") }}</h2>
            <div
                v-if="error_message !== ''"
                class="tlp-alert-danger"
                data-test="creation-error-message"
            >
                <p class="tlp-alert-title">
                    {{ $gettext("Error while creating approval table") }}
                </p>
                {{ error_message }}
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="user-lazybox">
                    {{ $gettext("Choose which users need to approve the document") }}
                </label>
                <tuleap-lazybox id="user-lazybox" ref="user_lazybox" />
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="user-group-list-picker">
                    {{ $gettext("Append members of user groups to the table") }}
                </label>
                <select
                    ref="user_group_picker"
                    name="list-value"
                    id="user-group-list-picker"
                    multiple
                >
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
        <div class="tlp-modal-footer">
            <button
                type="button"
                data-dismiss="modal"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="button"
                class="tlp-button-primary tlp-modal-action"
                v-on:click="onCreate"
                v-bind:disabled="is_creating"
                data-test="create-table-button"
            >
                <i
                    v-if="is_creating"
                    class="tlp-button-icon fa-solid fa-spin fa-circle-notch"
                    aria-hidden="true"
                ></i>
                {{ $gettext("Create") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Item, UserGroup } from "../../../type";
import { onMounted, onUnmounted, ref } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import "@tuleap/lazybox";
import type { Lazybox } from "@tuleap/lazybox";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PROJECT, USER_LOCALE } from "../../../configuration-keys";
import { loadProjectUserGroups } from "../../../helpers/permissions/ugroups";
import { useStore } from "vuex-composition-helpers";
import { createListPicker } from "@tuleap/list-picker";
import { useGettext } from "vue3-gettext";
import { initUsersAutocompleter } from "@tuleap/lazybox-users-autocomplete";
import type { User } from "@tuleap/core-rest-api-types";
import { postApprovalTable } from "../../../api/approval-table-rest-querier";

const { $gettext } = useGettext();
const $store = useStore();

const props = defineProps<{ item: Item }>();

const emit = defineEmits<{
    (e: "table-created"): void;
}>();

const modal_div = ref<HTMLDivElement>();
const modal = ref<Modal | null>(null);
const user_group_picker = ref<HTMLSelectElement>();
const user_lazybox = ref<Lazybox>();
const currently_selected_users = ref<Array<User>>([]);
const user_groups = ref<ReadonlyArray<UserGroup>>([]);
const currently_selected_user_groups = ref<Array<UserGroup>>([]);
const is_creating = ref<boolean>(false);
const error_message = ref<string>("");

const user_locale = strictInject(USER_LOCALE);
const project = strictInject(PROJECT);

onMounted(() => {
    if (modal_div.value === undefined) {
        throw Error("Failed to mount approval table creation modal: modal element not found");
    }
    if (user_group_picker.value === undefined) {
        throw new Error("Cannot find user group picker element");
    }
    if (user_lazybox.value === undefined) {
        throw new Error("Cannot find user lazybox element");
    }

    modal.value = createModal(modal_div.value, {
        keyboard: true,
        dismiss_on_backdrop_click: true,
    });

    loadProjectUserGroups($store, project.id).match(
        (groups) => {
            user_groups.value = groups;
        },
        (fault) => {
            error_message.value = fault.toString();
        },
    );

    createListPicker(user_group_picker.value, {
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
        currently_selected_user_groups.value = selected_groups;
    });

    initUsersAutocompleter(
        user_lazybox.value,
        [],
        (selected_users: ReadonlyArray<User>): void => {
            currently_selected_users.value = [...selected_users];
        },
        user_locale,
    );
});

onUnmounted(() => {
    modal.value?.destroy();
    modal.value = null;
});

function onCreate(): void {
    is_creating.value = true;
    postApprovalTable(
        props.item.id,
        currently_selected_users.value.map((user) => user.id),
        currently_selected_user_groups.value.map((user_group) => {
            if (user_group.id.includes("_")) {
                // We assume that user group id is something like 102_3, we just need 3
                return Number.parseInt(user_group.id.split("_")[1], 10);
            }
            return Number.parseInt(user_group.id, 10);
        }),
    ).match(
        () => {
            emit("table-created");
            is_creating.value = false;
            modal.value?.hide();
        },
        (fault) => {
            error_message.value = fault.toString();
            is_creating.value = false;
        },
    );
}
</script>
