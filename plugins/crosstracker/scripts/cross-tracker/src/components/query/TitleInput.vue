<!--
  - Copyright (c) Enalean, 2025-present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
  -
  -  Tuleap is free software; you can redistribute it and/or modify
  -  it under the terms of the GNU General Public License as published by
  -  the Free Software Foundation; either version 2 of the License, or
  -  (at your option) any later version.
  -
  -  Tuleap is distributed in the hope that it will be useful,
  -  but WITHOUT ANY WARRANTY; without even the implied warranty of
  -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  -  GNU General Public License for more details.
  -
  -  You should have received a copy of the GNU General Public License
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="tlp-form-element create-new-query-title">
        <label class="tlp-label" for="create-query-title"
            >{{ $gettext("Query title") }} <i class="fa-solid fa-asterisk" aria-hidden="true"></i
        ></label>
        <input
            type="text"
            id="create-query-title"
            class="tlp-input"
            v-bind:placeholder="$gettext('Name your query')"
            v-on:input="handleInput"
            required
            v-bind:value="title"
        />
    </div>
</template>

<script setup lang="ts">
defineProps<{
    title: string;
}>();
const emit = defineEmits<{
    (e: "update:title", value: string): void;
}>();

function handleInput(event: Event): void {
    if (!(event.target instanceof HTMLInputElement)) {
        return;
    }
    emit("update:title", event.target.value);
}
</script>

<style scoped lang="scss">
.create-new-query-title {
    width: 33%;
}
</style>
