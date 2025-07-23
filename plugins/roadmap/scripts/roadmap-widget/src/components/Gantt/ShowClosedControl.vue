<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
    <div class="tlp-form-element roadmap-gantt-control roadmap-gantt-control-mini-switch">
        <label class="tlp-label roadmap-gantt-control-label" v-bind:for="id">
            {{ $gettext("Show closed items") }}
        </label>

        <div class="tlp-switch tlp-switch-mini">
            <input
                type="checkbox"
                v-bind:id="id"
                class="tlp-switch-checkbox"
                v-bind:value="is_checked"
                v-on:change="toggleClosedElements(is_checked)"
                v-on:input="updateIsChecked"
                data-test="input"
            />
            <label v-bind:for="id" class="tlp-switch-button">{{
                $gettext("Show closed items")
            }}</label>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useState, useMutations } from "vuex-composition-helpers";
import { getUniqueId } from "../../helpers/uniq-id-generator";

const { show_closed_elements } = useState(["show_closed_elements"]);
const { toggleClosedElements } = useMutations(["toggleClosedElements"]);

const is_checked = ref<boolean>(false);

onMounted(() => {
    is_checked.value = show_closed_elements.value;
});

const id = computed(() => getUniqueId("roadmap-gantt-show-closed"));

function updateIsChecked(event: Event): void {
    if (!(event.target instanceof HTMLInputElement)) {
        return;
    }

    is_checked.value = event.target.checked;
}
</script>
