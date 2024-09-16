<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <input
        class="tlp-search"
        autocomplete="off"
        v-bind:placeholder="$gettext('Repository name')"
        type="search"
        v-model="filter_value"
        size="30"
    />
</template>
<script setup lang="ts">
import type { Ref } from "vue";
import { onMounted, ref, watch } from "vue";
import { useMutations, useState } from "vuex-composition-helpers";

const { filter } = useState(["filter"]);
const { setFilter } = useMutations(["setFilter"]);

let filter_value: Ref<string | null> = ref(null);

onMounted(() => {
    filter_value.value = filter.value;
});

watch(filter_value, (value: string | null) => {
    setFilter(value);
});
</script>
