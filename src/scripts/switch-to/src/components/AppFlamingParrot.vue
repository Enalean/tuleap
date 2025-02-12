<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <div
        class="modal hide fade"
        id="switch-to-modal"
        data-test="switch-to-modal"
        role="dialog"
        v-bind:aria-label="$gettext('Switch to…')"
        ref="modal"
    >
        <switch-to-header class="modal-header" v-bind:modal="null" />
        <switch-to-body class="modal-body" />
    </div>
</template>
<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import jQuery from "jquery";
import SwitchToHeader from "./Header/SwitchToHeader.vue";
import SwitchToBody from "./Body/SwitchToBody.vue";
import { useRootStore } from "../stores/root";

const { $gettext } = useGettext();

const modal = ref<HTMLElement | null>(null);

onMounted((): void => {
    if (!(modal.value instanceof HTMLElement)) {
        return;
    }
    const store = useRootStore();

    jQuery(modal.value)
        // Force autofocus for bootstrap modal
        .on("shown", () => {
            store.loadHistory();
            if (modal.value) {
                const input = modal.value.querySelector("input");
                if (input) {
                    input.focus();
                }
            }
        })
        // Clear filter for bootstrap modal
        .on("hidden", () => {
            store.updateFilterValue("");
        });
});
</script>
