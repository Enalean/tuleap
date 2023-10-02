<!--
  - Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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
    <div class="tlp-form-element document-search-criterion document-search-criterion-number">
        <label class="tlp-label" v-bind:for="id">{{ criterion.label }}</label>
        <input
            type="number"
            class="tlp-input"
            v-bind:id="id"
            v-bind:value="value"
            v-on:input="updateCriteria"
            v-bind:data-test="id"
        />
    </div>
</template>

<script setup lang="ts">
import type { SearchCriterionText } from "../../../type";
import { computed } from "vue";
import emitter from "../../../helpers/emitter";

const props = defineProps<{ criterion: SearchCriterionText; value: string }>();

function updateCriteria($event: Event): void {
    if ($event.target instanceof HTMLInputElement) {
        emitter.emit("update-criteria", {
            criteria: props.criterion.name,
            value: $event.target.value,
        });
    }
}

const id = computed((): string => {
    return "document-criterion-number-" + props.criterion.name;
});
</script>
