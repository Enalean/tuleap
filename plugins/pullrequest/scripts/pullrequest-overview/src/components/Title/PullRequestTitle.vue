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
    <div class="tlp-pane-header pull-request-header">
        <h2
            v-if="props.pull_request"
            data-test="pullrequest-title"
            v-dompurify-html="props.pull_request.title"
            ref="pull_request_title"
        ></h2>
        <h2 v-if="props.pull_request === null">
            <span class="tlp-skeleton-text" data-test="pullrequest-title-skeleton"></span>
        </h2>
    </div>
</template>

<script setup lang="ts">
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import { loadTooltips } from "@tuleap/tooltip";
import { watch, ref } from "vue";

const props = defineProps<{
    pull_request: PullRequest | null;
}>();

const pull_request_title = ref<HTMLElement | undefined>();

watch(
    () => (props.pull_request ? props.pull_request.title : ""),
    () => {
        if (props.pull_request === null) {
            return;
        }

        setTimeout(() => {
            loadTooltips(pull_request_title.value, false);
        });
    },
);
</script>

<styles lang="scss"></styles>
