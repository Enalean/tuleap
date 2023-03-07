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
    <div class="tlp-form-element docman-item-version-title-update">
        <label class="tlp-label" for="document-update-version-title">
            {{ $gettext("Version name") }}
        </label>
        <input
            type="text"
            class="tlp-input"
            id="document-update-version-title"
            data-test="document-update-version-title"
            name="version_title"
            v-bind:placeholder="`${$gettext('My new version name')}`"
            v-bind:value="value"
            v-on:input="oninput"
            ref="root"
        />
    </div>
</template>

<script setup lang="ts">
import emitter from "../../../../helpers/emitter";
import { onMounted, ref } from "vue";

defineProps<{
    value: string;
}>();

const root = ref<InstanceType<typeof HTMLElement>>();

onMounted((): void => {
    if (root.value) {
        root.value.focus();
    }
});

function oninput($event: Event): void {
    if ($event.target instanceof HTMLInputElement) {
        emitter.emit("update-version-title", $event.target.value);
    }
}
</script>
