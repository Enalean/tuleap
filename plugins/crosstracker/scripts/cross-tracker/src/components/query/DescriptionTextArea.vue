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
    <div class="tlp-form-element create-new-query-description">
        <label class="tlp-label" for="create-query-description"
            >{{ $gettext("Description") }}
        </label>
        <textarea
            id="create-query-description"
            class="tlp-textarea"
            v-bind:placeholder="$gettext('Write your description')"
            v-on:input="handleDescription"
        ></textarea>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    description: string;
}>();
const emit = defineEmits<{
    (e: "update:description", value: string): void;
}>();

function handleDescription(event: Event): void {
    if (!(event.target instanceof HTMLTextAreaElement)) {
        return;
    }
    emit("update:description", event.target.value);
}
</script>
<style scoped lang="scss">
.create-new-query-description {
    width: 66%;
}
</style>
