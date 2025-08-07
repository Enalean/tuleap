<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <a v-bind:href="url" class="tracker-creation-navigation-back-to-legacy">
        {{ $gettext("I want to migrate a tracker v3") }}
    </a>
</template>
<script setup lang="ts">
import { computed } from "vue";
import { useState } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();
const { project_id } = useState(["project_id"]);

const url = computed(() => {
    return `/plugins/tracker/?group_id=${encodeURIComponent(project_id.value)}&func=create`;
});
</script>
