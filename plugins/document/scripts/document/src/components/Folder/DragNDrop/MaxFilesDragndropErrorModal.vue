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
        <p>{{ error_message }}</p>
        <p>{{ $gettext("Please start again.") }}</p>
    </error-modal>
</template>

<script setup lang="ts">
import ErrorModal from "./ErrorModal.vue";
import { useGettext } from "vue3-gettext";
import { useNamespacedState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../store/configuration";
import { computed } from "vue";

const { interpolate, $ngettext } = useGettext();

const emit = defineEmits<{
    (e: "error-modal-hidden"): void;
}>();

const { max_files_dragndrop } = useNamespacedState<Pick<ConfigurationState, "max_files_dragndrop">>(
    "configuration",
    ["max_files_dragndrop"],
);

const error_message = computed(() => {
    const translated = $ngettext(
        "You are not allowed to drag 'n drop more than %{ nb } file at once.",
        "You are not allowed to drag 'n drop more than %{ nb } files at once.",
        max_files_dragndrop.value,
    );

    return interpolate(translated, { nb: max_files_dragndrop.value });
});

function bubbleErrorModalHidden() {
    emit("error-modal-hidden");
}
</script>
