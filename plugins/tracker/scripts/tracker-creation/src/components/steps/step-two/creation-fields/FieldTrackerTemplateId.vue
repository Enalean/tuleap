<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <input type="hidden" name="tracker-template-id" v-bind:value="tracker_id" />
</template>
<script setup lang="ts">
import { computed } from "vue";
import { useState, useGetters } from "vuex-composition-helpers";

const { selected_tracker_template, selected_project_tracker_template } = useState([
    "selected_tracker_template",
    "selected_project_tracker_template",
]);

const { is_created_from_default_template, is_a_duplication } = useGetters([
    "is_created_from_default_template",
    "is_a_duplication",
]);

const tracker_id = computed((): string => {
    if (is_a_duplication.value || is_created_from_default_template.value) {
        return selected_tracker_template.value.id;
    }

    return selected_project_tracker_template.value.id;
});
</script>
