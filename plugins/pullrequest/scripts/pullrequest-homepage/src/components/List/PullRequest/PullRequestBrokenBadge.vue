<!--
  - Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
    <div v-if="pull_request.is_git_reference_broken" class="wrapper">
        <span class="tlp-badge-danger" data-test="broken-git-ref-badge">
            <i class="fa-solid fa-triangle-exclamation tlp-badge-icon" aria-hidden="true"></i
            >{{ $gettext("Error") }}</span
        >
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";

const { $gettext } = useGettext();

defineProps<{
    pull_request: PullRequest;
}>();
</script>

<style scoped lang="scss">
.wrapper {
    display: flex;
    flex: 0 1 25%;
    justify-content: flex-end;
}
</style>
