<!--
  - Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
    <div role="dialog" aria-labelledby="add-modal-label" class="tlp-modal" ref="add_modal_element">
        <input
            v-for="user_id in current_selected_user_ids"
            v-bind:key="user_id"
            type="hidden"
            name="listeners_users_to_add[]"
            v-bind:value="user_id"
        />

        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="add-modal-label">{{ $gettext("Add notification") }}</h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-on:click="$emit('cancel')"
                v-bind:aria-label="$gettext('Close')"
            >
                <i class="fa-solid fa-xmark tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body">
            <div class="tlp-form-element">
                <label class="tlp-label" for="ugroups-add">
                    {{ $gettext("User groups that will receive the notification") }}
                    <select
                        id="listeners_ugroups_to_add"
                        data-test="listeners_ugroups_to_add[]"
                        name="listeners_ugroups_to_add[]"
                        class="tlp-select"
                        multiple
                    >
                        <option
                            v-for="ugroup in project_ugroups"
                            v-bind:value="
                                ugroup.id.includes('_') ? ugroup.id.split('_')[1] : ugroup.id
                            "
                            v-bind:key="`notified-${item.id}-${ugroup.id}`"
                            v-bind:title="ugroup.label"
                        >
                            {{ ugroup.label }}
                        </option>
                    </select>
                </label>
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="users-add">
                    {{ $gettext("Users who will receive the notification") }}
                    <tuleap-lazybox id="update-users-select" ref="users_input" />
                </label>
            </div>
            <div class="tlp-form-element">
                <label
                    class="tlp-label tlp-checkbox"
                    for="plugin_docman_monitor_add_user_cascade"
                    v-if="item.type === TYPE_FOLDER"
                >
                    <input
                        type="checkbox"
                        name="monitor_cascade"
                        value="1"
                        id="plugin_docman_monitor_add_user_cascade"
                        data-test="plugin_docman_monitor_add_user_cascade"
                    />
                    {{ $gettext("Enable monitoring for the whole sub-hierarchy") }}
                </label>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                data-dismiss="modal"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                v-on:click="$emit('cancel')"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                data-test="validate-notification-button"
            >
                {{ $gettext("Validate") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";
import "@tuleap/lazybox";
import { initUsersAutocompleter } from "@tuleap/lazybox-users-autocomplete";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import type { Lazybox } from "@tuleap/lazybox";
import type { User } from "@tuleap/core-rest-api-types";
import type { Item, UserGroup } from "../../type";
import { TYPE_FOLDER } from "../../constants";

defineProps<{
    item: Item;
    project_ugroups: ReadonlyArray<UserGroup> | null;
}>();

defineEmits<{
    (e: "cancel"): void;
}>();

const add_modal_element = ref<HTMLElement | null>(null);
const add_modal = ref<Modal | null>(null);

const users_input = ref<Lazybox | undefined>();
const current_selected_user_ids = ref<number[]>([]);

onMounted(() => {
    if (add_modal_element.value) {
        add_modal.value = createModal(add_modal_element.value, {
            destroy_on_hide: true,
            dismiss_on_backdrop_click: false,
        });
        add_modal.value.show();
    }

    if (!users_input.value) {
        return;
    }
    initUsersAutocompleter(users_input.value, [], (selected_users: ReadonlyArray<User>): void => {
        current_selected_user_ids.value = selected_users.map((user) => user.id);
    });
});

onUnmounted(() => {
    if (add_modal.value) {
        add_modal.value.destroy();
    }
});
</script>

<style lang="scss">
@use "pkg:@tuleap/lazybox";
</style>
