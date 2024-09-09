<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
    <span v-if="is_displayed" v-bind:class="`tlp-alert-${notification.type}`">{{
        notification.message
    }}</span>
</template>
<script setup lang="ts">
import { ref } from "vue";
import type { Notification, UseNotificationsStoreType } from "@/stores/useNotificationsStore";

export type NotificationMessageProps = {
    notification: Notification;
    delete_notification: UseNotificationsStoreType["deleteNotification"];
};

const props = defineProps<NotificationMessageProps>();
const is_displayed = ref(true);

setTimeout(() => {
    is_displayed.value = false;
    props.delete_notification(props.notification);
}, 5_000);
</script>
