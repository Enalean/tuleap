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
    <section v-if="item">
        <document-details-tabs v-bind:item="item" v-bind:active_tab="NotificationsTab" />
        <div class="tlp-framed-horizontally">
            <section class="tlp-pane">
                <div class="tlp-pane-container">
                    <div class="tlp-pane-header">
                        <h1 class="tlp-pane-title">{{ $gettext("Notifications") }}</h1>
                    </div>

                    <individual-notifications
                        v-bind:is_user_notified="is_current_user_in_subscriber_list"
                        v-bind:is_user_notified_for_cascade="is_user_notified_for_cascade"
                        v-bind:is_a_folder="item.type === TYPE_FOLDER"
                        v-bind:is_user_anonymous="current_user_id === 0"
                        v-bind:action_url="action_url"
                        v-bind:item_id="item_id"
                        v-bind:csrf_token="csrf_token"
                    />
                </div>
            </section>
            <section class="tlp-pane" v-if="item.can_user_manage">
                <div class="tlp-pane-container">
                    <div class="tlp-pane-header">
                        <h1 class="tlp-pane-title">{{ $gettext("Subscribers") }}</h1>
                    </div>
                    <subscribers-list
                        v-bind:item="item"
                        v-bind:subscribers="subscribers"
                        v-bind:action_url="action_url"
                        v-bind:project_id="project_id"
                        v-bind:csrf_token="csrf_token"
                        v-bind:project_ugroups="project_user_groups"
                    />
                </div>
            </section>
        </div>
    </section>
</template>

<script setup lang="ts">
import { ref, onBeforeMount, computed } from "vue";
import { useActions, useStore } from "vuex-composition-helpers";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { Item, UserGroup } from "../../type";
import { TYPE_FOLDER } from "../../constants";
import { CSRF_TOKEN, PROJECT, USER_ID } from "../../configuration-keys";
import { NotificationsTab } from "../../helpers/details-tabs";
import type { SubscriberList } from "../../api/notifications-rest-querier";
import {
    FROM_PARENT,
    TO_ITEM_AND_SUBHIERARCHY,
    getSubscribers,
} from "../../api/notifications-rest-querier";
import DocumentDetailsTabs from "../Folder/DocumentDetailsTabs.vue";
import IndividualNotifications from "./IndividualNotifications.vue";
import SubscribersList from "./SubscribersList.vue";
import { loadProjectUserGroups } from "../../helpers/permissions/ugroups";

const { loadDocumentWithAscendentHierarchy } = useActions(["loadDocumentWithAscendentHierarchy"]);

const props = defineProps<{ item_id: number }>();
const $store = useStore();

const project_id = strictInject(PROJECT).id;
const current_user_id = strictInject(USER_ID);
const csrf_token = strictInject(CSRF_TOKEN);

const item = ref<Item | null>(null);
const project_user_groups = ref<ReadonlyArray<UserGroup>>([]);
const subscribers = ref<SubscriberList>({
    users: [],
    ugroups: [],
});

const is_current_user_in_subscriber_list = computed((): boolean => {
    return subscribers.value.users.some(
        (user) => user.subscriber.id === current_user_id && user.subscription_type !== FROM_PARENT,
    );
});

const is_user_notified_for_cascade = computed((): boolean => {
    return subscribers.value.users.some(
        (user) =>
            user.subscriber.id === current_user_id &&
            user.subscription_type === TO_ITEM_AND_SUBHIERARCHY,
    );
});

const action_url = `/plugins/docman/?group_id=${encodeURIComponent(project_id)}&id=${encodeURIComponent(props.item_id)}&action=details&section=notifications`;

onBeforeMount(async () => {
    updateSubscribers();
    item.value = await loadDocumentWithAscendentHierarchy(props.item_id);
});

function updateSubscribers(): void {
    getSubscribers(props.item_id).match(
        (result) => {
            subscribers.value = result;
        },
        () => {
            subscribers.value = {
                users: [],
                ugroups: [],
            };
        },
    );

    loadProjectUserGroups($store, project_id).match(
        (user_groups) => {
            project_user_groups.value = user_groups;
        },
        () => {
            project_user_groups.value = [];
        },
    );
}
</script>
