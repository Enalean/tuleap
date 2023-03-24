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
    <div class="tlp-property">
        <label class="tlp-label">
            {{ $gettext("Author") }}
        </label>
        <div v-if="props.pull_request_author" data-test="pullrequest-author-info">
            <div class="tlp-avatar-medium">
                <img
                    v-bind:src="props.pull_request_author.avatar_url"
                    data-test="pullrequest-author-avatar"
                />
            </div>
            <a
                class="pullrequest-author-name"
                v-bind:href="props.pull_request_author.user_url"
                data-test="pullrequest-author-name"
            >
                {{ props.pull_request_author.display_name }}
            </a>
        </div>
        <property-skeleton v-if="!props.pull_request_author" />
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import type { User } from "@tuleap/plugin-pullrequest-rest-api-types";
import PropertySkeleton from "./PropertySkeleton.vue";

const { $gettext } = useGettext();

const props = defineProps<{
    pull_request_author: User | null;
}>();
</script>

<style lang="scss">
.pullrequest-author-name {
    margin: 0 0 0 var(--tlp-small-spacing);
    color: var(--tlp-dark-color);
}
</style>
