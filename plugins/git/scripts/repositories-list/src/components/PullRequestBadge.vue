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
    <a
        v-bind:href="pullrequest_url()"
        class="git-pullrequest-badge-link"
        v-if="number_pull_request > 0"
        data-test="pull-requests-badge"
    >
        <span class="tlp-badge-primary">{{ pull_requests }}</span>
    </a>
</template>
<script setup lang="ts">
import { computed } from "vue";
import { getProjectId } from "../repository-list-presenter";
import { getPullRequestsHomepageUrl } from "../helpers/pull-requests-homepage-url-builder";
import { useGettext } from "vue3-gettext";

const { interpolate, $ngettext } = useGettext();

const props = defineProps<{
    number_pull_request: number;
    repository_id: number;
}>();

const pullrequest_url = (): string => {
    return String(getPullRequestsHomepageUrl(location, getProjectId(), props.repository_id));
};

const pull_requests = computed(() => {
    const nb = props.number_pull_request;
    return interpolate(
        $ngettext(
            "%{ number_pull_request } pull request",
            "%{ number_pull_request } pull requests",
            nb,
        ),
        { number_pull_request: nb },
    );
});
</script>
