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
        class="tlp-modal"
        role="dialog"
        v-bind:aria-label="$gettext('Switch to…')"
        id="switch-to-modal"
        data-test="switch-to-modal"
        ref="root"
    >
        <switch-to-header class="tlp-modal-header" v-bind:modal="modal" />
        <switch-to-body class="tlp-modal-body" />
    </div>
</template>
<script setup lang="ts">
import { onMounted, onUnmounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import SwitchToHeader from "./Header/SwitchToHeader.vue";
import SwitchToBody from "./Body/SwitchToBody.vue";
import { useRootStore } from "../stores/root";

const { $gettext } = useGettext();

let modal: Modal | null = null;
let trigger: HTMLElement | null = null;
const root = ref<HTMLElement | null>(null);

onMounted((): void => {
    listenToTrigger();
});

function listenToTrigger(): void {
    trigger = document.getElementById("switch-to-button");
    if (!(trigger instanceof HTMLElement)) {
        return;
    }

    if (!root.value) {
        return;
    }

    modal = createModal(root.value);
    trigger.addEventListener("click", toggleModal);
}

onUnmounted((): void => {
    if (!(trigger instanceof HTMLElement)) {
        return;
    }

    trigger.removeEventListener("click", toggleModal);
});

function toggleModal(): void {
    useRootStore().loadHistory();
    if (modal) {
        modal.toggle();
    }
}
</script>
