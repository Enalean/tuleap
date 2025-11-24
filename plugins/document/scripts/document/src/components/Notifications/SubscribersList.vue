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
    <section class="tlp-pane-section">
        <form method="POST" v-bind:action="action_url" data-test="docman_notifications_form">
            <input type="hidden" v-bind:name="csrf_token.name" v-bind:value="csrf_token.value" />
            <input type="hidden" name="action" value="update_monitoring" />

            <remove-modal
                v-if="ugroup_to_remove"
                v-bind:subscriber_name="ugroup_to_remove.label"
                v-bind:subscriber_id="ugroup_to_remove.id"
                v-bind:input_name="'listeners_ugroups_to_delete[]'"
                v-on:cancel="ugroup_to_remove = null"
            />
            <remove-modal
                v-if="user_to_remove"
                v-bind:subscriber_name="user_to_remove.display_name"
                v-bind:subscriber_id="String(user_to_remove.id)"
                v-bind:input_name="'listeners_users_to_delete[]'"
                v-on:cancel="user_to_remove = null"
            />
            <add-modal
                v-if="add_modal_should_be_displayed"
                v-bind:item="item"
                v-bind:project_ugroups="project_ugroups"
                v-on:cancel="add_modal_should_be_displayed = false"
            />

            <div class="tlp-table-actions">
                <button
                    type="button"
                    class="tlp-button-primary"
                    v-on:click="add_modal_should_be_displayed = true"
                    data-test="add-notification-button"
                >
                    <i class="fa-solid fa-plus tlp-button-icon" aria-hidden="true"></i>
                    {{ $gettext("Add notification") }}
                </button>
            </div>
            <table class="tlp-table">
                <thead>
                    <tr>
                        <th>{{ $gettext("Notified people or groups") }}</th>
                        <th>{{ $gettext("Monitored document") }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody data-test="notified-users">
                    <tr v-if="has_no_subscribers">
                        <td colspan="3" class="tlp-table-cell-empty">
                            {{ $gettext("No notifications set") }}
                        </td>
                    </tr>
                    <tr v-for="ugroup of subscribers.ugroups" v-bind:key="ugroup.subscriber.id">
                        <td>
                            <i class="fa-solid fa-users" aria-hidden="true"></i>
                            {{ ugroup.subscriber.label }}
                        </td>
                        <td>{{ item.title }}</td>
                        <td class="tlp-table-cell-actions">
                            <button
                                type="button"
                                class="tlp-table-cell-actions-button tlp-button-small tlp-button-danger tlp-button-outline"
                                v-bind:disabled="ugroup.subscription_type === FROM_PARENT"
                                v-bind:title="
                                    ugroup.subscription_type === FROM_PARENT
                                        ? text_for_disabled_delete_button
                                        : ''
                                "
                                v-on:click="ugroup_to_remove = ugroup.subscriber"
                            >
                                <i
                                    class="tlp-button-icon fa-regular fa-trash-can"
                                    aria-hidden="true"
                                ></i>
                                {{ $gettext("Delete") }}
                            </button>
                        </td>
                    </tr>
                    <tr v-for="user of subscribers.users" v-bind:key="user.subscriber.id">
                        <td>
                            <div class="tlp-avatar">
                                <img
                                    alt="User avatar"
                                    loading="lazy"
                                    v-bind:src="user.subscriber.avatar_url"
                                />
                            </div>
                            {{ user.subscriber.display_name }}
                        </td>
                        <td>{{ item.title }}</td>
                        <td class="tlp-table-cell-actions">
                            <button
                                type="button"
                                class="tlp-table-cell-actions-button tlp-button-small tlp-button-danger tlp-button-outline"
                                v-bind:disabled="user.subscription_type === FROM_PARENT"
                                v-bind:title="
                                    user.subscription_type === FROM_PARENT
                                        ? text_for_disabled_delete_button
                                        : ''
                                "
                                v-on:click="user_to_remove = user.subscriber"
                                data-test="remove-notification-button"
                            >
                                <i
                                    class="tlp-button-icon fa-regular fa-trash-can"
                                    aria-hidden="true"
                                ></i>
                                {{ $gettext("Delete") }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
    </section>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { useGettext } from "vue3-gettext";
import type { User } from "@tuleap/core-rest-api-types";
import type { CsrfToken, Item, UserGroup } from "../../type";
import type { SubscriberList } from "../../api/notifications-rest-querier";
import { FROM_PARENT } from "../../api/notifications-rest-querier";
import RemoveModal from "./RemoveModal.vue";
import AddModal from "./AddModal.vue";

const { $gettext } = useGettext();

const props = defineProps<{
    item: Item;
    subscribers: SubscriberList;
    action_url: string;
    project_id: number;
    csrf_token: CsrfToken;
    project_ugroups: ReadonlyArray<UserGroup> | null;
}>();

const user_to_remove = ref<User | null>(null);
const ugroup_to_remove = ref<UserGroup | null>(null);

const add_modal_should_be_displayed = ref(false);

const text_for_disabled_delete_button = $gettext(
    "You cannot delete because a parent folder is monitored with its sub-hierarchy. You can delete that monitoring only from the parent itself.",
);

const has_no_subscribers = computed(() => {
    return props.subscribers.ugroups.length <= 0 && props.subscribers.users.length <= 0;
});
</script>
