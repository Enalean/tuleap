<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <div class="tlp-form-element">
        <label class="tlp-label" v-bind:for="id">
            {{ $gettext("Short name") }}
            <i class="fa fa-asterisk"></i>
        </label>
        <input
            type="text"
            class="tlp-input"
            v-bind:id="id"
            name="short_name"
            v-bind:placeholder="placeholder"
            size="15"
            maxlength="40"
            required
            v-bind:value="shortname"
            v-on:input="onInputEmit"
        />
    </div>
</template>
<script setup lang="ts">
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

defineProps<{
    shortname: string;
    id: string;
}>();

const emit = defineEmits<{
    (e: "input", short_name: string): void;
}>();

const placeholder = $gettext("my_service");

function onInputEmit(event: Event): void {
    if (!(event.target instanceof HTMLInputElement)) {
        return;
    }

    emit("input", event.target.value);
}
</script>
