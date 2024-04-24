<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <div class="tlp-form-element">
        <label class="tlp-label" for="trovecat">
            {{ trovecat.fullname }}
            <i class="fa fa-asterisk" />
        </label>
        <select
            class="tlp-select tlp-select-large"
            id="trovecat"
            name="trovecat"
            required
            v-on:change="updateTroveCategories(trovecat.id, $event)"
            data-test="trove-category-list"
        >
            <option></option>
            <option
                v-for="children in trovecat.children"
                v-bind:value="children.id"
                v-bind:key="children.id"
            >
                {{ children.fullname }}
            </option>
        </select>
    </div>
</template>

<script setup lang="ts">
import type { TroveCatData } from "../../../type";
import EventBus from "../../../helpers/event-bus";

defineProps<{
    trovecat: TroveCatData;
}>();

function updateTroveCategories(category_id: string, event: Event): void {
    if (!(event.target instanceof HTMLSelectElement)) {
        return;
    }
    const value_id = event.target.value;
    EventBus.$emit("choose-trove-cat", { category_id, value_id });
}
</script>
