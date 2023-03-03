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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->

<template>
    <div class="tlp-form-element">
        <label class="tlp-label" for="document-new-item-status">
            {{ $gettext("Status") }}
            <i class="fa-solid fa-asterisk"></i>
        </label>
        <select
            class="tlp-select"
            id="document-new-item-status"
            name="status"
            v-on:change="onchange"
            v-bind:value="value"
            ref="input"
            data-test="document-new-item-status"
        >
            <option name="none" value="none" data-test="value-none">{{ $gettext("None") }}</option>
            <option name="draft" value="draft" data-test="value-draft">
                {{ $gettext("Draft") }}
            </option>
            <option name="approved" value="approved" data-test="value-approved">
                {{ $gettext("Approved") }}
            </option>
            <option name="rejected" value="rejected" data-test="value-rejected">
                {{ $gettext("Rejected") }}
            </option>
        </select>
    </div>
</template>

<script setup lang="ts">
import emitter from "../../../../helpers/emitter";

defineProps<{ value: string }>();

function onchange($event: Event): void {
    if ($event.target instanceof HTMLSelectElement) {
        emitter.emit("update-status-property", $event.target.value);
    }
}
</script>
