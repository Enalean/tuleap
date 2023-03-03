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
        <div v-if="author" data-test="pullrequest-author-info">
            <div class="tlp-avatar-medium">
                <img v-bind:src="author.avatar_url" data-test="pullrequest-author-avatar" />
            </div>
            <a
                class="pullrequest-author-name"
                v-bind:href="author.user_url"
                data-test="pullrequest-author-name"
            >
                {{ author.display_name }}
            </a>
        </div>
        <property-skeleton v-if="!props.pull_request_info || !author" />
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import { useGettext } from "vue3-gettext";
import { fetchUserInfo } from "../../api/tuleap-rest-querier";
import type { PullRequest, User } from "@tuleap/plugin-pullrequest-rest-api-types";
import PropertySkeleton from "./PropertySkeleton.vue";
import { strictInject } from "../../helpers/strict-inject";
import { DISPLAY_TULEAP_API_ERROR } from "../../constants";

const { $gettext } = useGettext();

const author = ref<User | null>(null);
const props = defineProps<{
    pull_request_info: PullRequest | null;
}>();

const displayTuleapAPIFault = strictInject(DISPLAY_TULEAP_API_ERROR);

watch(
    () => props.pull_request_info,
    (new_pull_request_info) => {
        if (!new_pull_request_info) {
            return;
        }

        fetchUserInfo(new_pull_request_info.user_id).match(
            (result) => {
                author.value = result;
            },
            (fault) => {
                displayTuleapAPIFault(fault);
            }
        );
    }
);
</script>

<style lang="scss">
.pullrequest-author-name {
    margin: 0 0 0 var(--tlp-small-spacing);
    color: var(--tlp-dimmed-color);
}
</style>
