<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
    <div class="pull-requests-sort-control">
        <label class="tlp-label pull-requests-sort-control-label" for="pull-requests-sort-order">{{
            $gettext("Sort")
        }}</label>
        <select
            class="tlp-select pull-requests-sort-control-select"
            id="pull-requests-sort-order"
            name="pull-requests-sort-order"
            data-test="sort-order-select"
            v-model="sort_direction"
        >
            <option v-bind:value="SORT_DESCENDANT">
                {{ $gettext("Newest") }}
            </option>
            <option v-bind:value="SORT_ASCENDANT">
                {{ $gettext("Latest") }}
            </option>
        </select>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PULL_REQUEST_SORT_ORDER, SORT_ASCENDANT, SORT_DESCENDANT } from "../../injection-symbols";

const { $gettext } = useGettext();

const sort_direction = strictInject(PULL_REQUEST_SORT_ORDER);
</script>

<style scoped lang="scss">
.pull-requests-sort-control-label {
    margin: 0;
}

.pull-requests-sort-control-select {
    width: 200px;
}

.pull-requests-sort-control {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: var(--tlp-small-spacing);
}
</style>
