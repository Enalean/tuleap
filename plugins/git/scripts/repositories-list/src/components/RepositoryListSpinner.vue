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
    <div
        class="git-repository-list-loading"
        data-test="git-repository-spinner"
        v-if="show_spinner()"
    ></div>
</template>
<script setup lang="ts">
import { useGetters } from "vuex-composition-helpers";

const { hasError, isLoading } = useGetters(["hasError", "isLoading"]);

function show_spinner(): boolean {
    return !hasError.value && isLoading.value;
}
</script>
