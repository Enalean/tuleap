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
        <span class="tlp-badge-primary">
            <translate
                v-bind:translate-n="numberPullRequest"
                translate-plural="%{ numberPullRequest } pull requests"
            >
                %{ numberPullRequest } pull request
            </translate>
        </span>
    </a>
</template>
<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { getProjectId, isOldPullRequestDashboardViewEnabled } from "../repository-list-presenter";
import {
    getOldPullRequestsDashboardUrl,
    getPullRequestsHomepageUrl,
} from "../helpers/pull-requests-homepage-url-builder";

@Component
export default class PullRequestBadge extends Vue {
    @Prop({ required: true })
    readonly numberPullRequest!: number;

    @Prop({ required: true })
    readonly repositoryId!: number;

    pullrequest_url(): string {
        if (isOldPullRequestDashboardViewEnabled()) {
            return String(
                getOldPullRequestsDashboardUrl(location, getProjectId(), this.repositoryId),
            );
        }

        return String(getPullRequestsHomepageUrl(location, getProjectId(), this.repositoryId));
    }
}
</script>
