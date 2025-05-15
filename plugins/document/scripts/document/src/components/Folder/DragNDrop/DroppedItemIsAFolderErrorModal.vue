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
  -->

<template>
    <error-modal v-on:error-modal-hidden="bubbleErrorModalHidden">
        <p>{{ error_message }}</p>
    </error-modal>
</template>

<script setup lang="ts">
import ErrorModal from "./ErrorModal.vue";
import { computed } from "vue";
import type { Reason } from "../../../type";
import { useGettext } from "vue3-gettext";

const { $ngettext } = useGettext();

const props = defineProps<{ reasons: Array<Reason> }>();

const nb_dropped_files = computed((): number => {
    return props.reasons[0].nb_dropped_files ? props.reasons[0].nb_dropped_files : 0;
});

const emit = defineEmits<{
    (e: "error-modal-hidden"): void;
}>();

function bubbleErrorModalHidden(): void {
    emit("error-modal-hidden");
}

const error_message = computed((): string => {
    return $ngettext(
        "The item you have dropped is not a file. If you want to upload a folder, we advise you to create a new folder first, then upload its content inside in a second time.",
        "The items you have dropped contain an item which is not a file. If you want to upload a folder, we advise you to create a new folder first, then upload its content inside in a second time.",
        nb_dropped_files.value,
    );
});
</script>
