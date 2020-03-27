<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
        v-bind:href="pullrequest_url"
        class="git-pullrequest-badge-link"
        v-if="numberPullRequest > 0"
    >
        <span class="tlp-badge-primary git-pullrequest-badge">
            <translate
                v-bind:translate-n="numberPullRequest"
                translate-plural="%{ numberPullRequest } pull requests"
            >
                %{ numberPullRequest } pull request
            </translate>
        </span>
    </a>
</template>
<script>
import { getProjectId } from "../repository-list-presenter.js";

export default {
    name: "PullRequestBadge",
    props: {
        numberPullRequest: Number,
        repositoryId: Number,
    },
    computed: {
        pullrequest_url() {
            return `/plugins/git/?action=pull-requests&group_id=${getProjectId()}&repo_id=${
                this.repositoryId
            }#/dashboard`;
        },
    },
};
</script>
