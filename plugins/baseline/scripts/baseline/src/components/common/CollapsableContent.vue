<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
    <div>
        <a
            v-on:click="toggleCollapse()"
            class="baseline-collapse-link"
            data-test-action="toggle-expand-collapse"
        >
            <i
                class="fa fa-fw"
                v-bind:class="{ 'fa-caret-right': is_collapsed, 'fa-caret-down': !is_collapsed }"
            ></i>
        </a>
        <slot name="header" />

        <div v-show="!is_collapsed" data-test="collapsible-slot">
            <slot />
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from "vue";

const is_collapsed = ref(false);

function toggleCollapse(): void {
    is_collapsed.value = !is_collapsed.value;
}
</script>
