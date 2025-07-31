<!--
  - Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
        v-bind:class="notification_class"
        v-if="is_displayed"
    >
        <template v-if="is_folder">{{ $gettext("The folder has been created below.") }}</template>
        <template v-else>{{ $gettext("The document has been created below.") }}</template>
        <i class="fa-solid fa-arrow-down document-new-item-under-the-fold-notification-icon"></i>
    </div>
</template>

<script setup lang="ts">
import { isFolder } from "../../../../helpers/type-check-helper";
import type { ItemHasBeenCreatedUnderTheFoldEvent } from "../../../../helpers/emitter";
import emitter from "../../../../helpers/emitter";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";

const is_displayed = ref<boolean>(false);
const is_folder = ref<boolean>(false);
const is_fadeout = ref<boolean>(false);
const is_fast_fadeout = ref<boolean>(false);
const fadeout_timeout_id = ref<number>();
const hidden_timeout_id = ref<number>();

const notification_class = computed(() => ({
    "document-notification-fadeout": is_fadeout.value,
    "document-notification-fast-fadeout": is_fast_fadeout.value,
}));

onMounted(() => {
    emitter.on("item-has-been-created-under-the-fold", show);
});

onBeforeUnmount(() => {
    emitter.off("item-has-been-created-under-the-fold", show);
});

function show(event: ItemHasBeenCreatedUnderTheFoldEvent) {
    is_folder.value = isFolder(event.detail.item);

    if (is_displayed.value) {
        clearTimeout(fadeout_timeout_id.value);
        clearTimeout(hidden_timeout_id.value);
    } else {
        window.addEventListener("scroll", scroll, { passive: true });
    }

    is_displayed.value = true;
    is_fadeout.value = false;
    is_fast_fadeout.value = false;
    fadeout_timeout_id.value = setTimeout(() => {
        is_fadeout.value = true;
    }, 2000);
    hidden_timeout_id.value = setTimeout(() => {
        is_displayed.value = false;
    }, 3000);
}

function scroll() {
    window.removeEventListener("scroll", scroll);
    clearTimeout(fadeout_timeout_id.value);
    clearTimeout(hidden_timeout_id.value);
    fadeout_timeout_id.value = setTimeout(() => {
        is_fast_fadeout.value = true;
    }, 0);
    hidden_timeout_id.value = setTimeout(() => {
        is_displayed.value = false;
    }, 250);
}
</script>
