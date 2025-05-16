<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <div
        class="document-notification tlp-alert-success"
        v-bind:class="{
            'document-notification-fadeout': is_fadeout,
        }"
        v-if="is_displayed"
    >
        {{ $gettext("The item has been deleted successfully.") }}
    </div>
</template>

<script setup lang="ts">
import { useMutations, useState } from "vuex-composition-helpers";
import type { State } from "../../../../type";
import { ref, watch } from "vue";

const { show_post_deletion_notification } = useState<
    Pick<State, "show_post_deletion_notification">
>(["show_post_deletion_notification"]);
const { hidePostDeletionNotification } = useMutations(["hidePostDeletionNotification"]);

const is_displayed = ref(false);
const is_fadeout = ref(false);

watch(
    () => show_post_deletion_notification.value,
    (value: boolean): void => {
        if (value) {
            show();
        }
    },
    { immediate: true },
);

function show(): void {
    is_displayed.value = true;
    is_fadeout.value = false;
    setTimeout(() => {
        is_fadeout.value = true;
    }, 2000);
    setTimeout(() => {
        is_displayed.value = false;
        hidePostDeletionNotification();
    }, 3000);
}
</script>
