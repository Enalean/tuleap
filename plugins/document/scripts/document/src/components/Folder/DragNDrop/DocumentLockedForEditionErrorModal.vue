<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
    <error-modal v-on:close="bubbleErrorModalHidden">
        <p v-dompurify-html="lock_message"></p>
    </error-modal>
</template>

<script setup lang="ts">
import ErrorModal from "./ErrorModal.vue";
import { computed, onMounted } from "vue";
import type { Reason } from "../../../type";
import { useGettext } from "vue3-gettext";

const { $gettext, $gettextInterpolate } = useGettext();

const props = defineProps<{
    reasons: Array<Reason>;
}>();
const emit = defineEmits<{
    (e: "error-modal-hidden"): void;
}>();

const lock_owner = computed(() => props.reasons[0].lock_owner);
const lock_message = computed(() => {
    let translated =
        $gettext(`%{ filename } has been locked for edition by <a href="%{ lock_owner_url }">%{ lock_owner_name }</a>.
                You can't upload a new version of this file until the lock is released.`);
    return $gettextInterpolate(translated, {
        filename: props.reasons[0].filename,
        lock_owner_url: lock_owner.value.user_url,
        lock_owner_name: lock_owner.value.display_name,
    });
});

onMounted(() => {
    if (props.reasons.length === 0) {
        throw new Error("This error modal should not be mounted without reason");
    }
    if (props.reasons[0].lock_owner === undefined) {
        throw new Error("This error modal should be mounted with a lock reason");
    }
});

function bubbleErrorModalHidden(): void {
    emit("error-modal-hidden");
}
</script>
