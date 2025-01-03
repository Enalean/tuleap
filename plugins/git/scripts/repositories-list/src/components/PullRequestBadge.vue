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
        v-if="numberPullRequest > 0"
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
    numberPullRequest: number;
    repositoryId: number;
}>();

const pullrequest_url = (): string => {
    return String(getPullRequestsHomepageUrl(location, getProjectId(), props.repositoryId));
};

const pull_requests = computed(() => {
    const nb = props.numberPullRequest;
    return interpolate(
        $ngettext(
            "%{ numberPullRequest } pull request",
            "%{ numberPullRequest } pull requests",
            nb,
        ),
        { numberPullRequest: nb },
    );
});
</script>
