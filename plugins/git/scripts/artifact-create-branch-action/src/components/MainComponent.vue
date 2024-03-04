<!--
  - Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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
    <Teleport to="#main-content">
        <div class="git-tracker-create-branch-modal">
            <modal-content
                v-bind:repositories="repositories"
                v-bind:branch_name_preview="branch_name_preview"
                v-bind:are_pullrequest_endpoints_available="are_pullrequest_endpoints_available"
            />
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import ModalContent from "./ModalContent.vue";
import type { GitRepository } from "../types";

defineProps<{
    repositories: ReadonlyArray<GitRepository>;
    branch_name_preview: string;
    are_pullrequest_endpoints_available: boolean;
}>();
</script>

<style lang="scss" scoped>
@use "sass:meta";

.git-tracker-create-branch-modal :deep() {
    @include meta.load-css("@tuleap/tlp-styles/components/typography");
    @include meta.load-css(
        "@tuleap/tlp-styles/components/forms",
        (
            "assets-base-path": "@tuleap/tlp-styles/",
        )
    );
    @include meta.load-css("@tuleap/tlp-modal");
    @include meta.load-css("@tuleap/tlp-button");
}
</style>
