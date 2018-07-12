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
    <section class="tlp-pane git-repository-card"
             v-bind:class="{ 'git-repository-card-two-columns': ! isFolderDisplayMode }"
    >
        <div class="tlp-pane-container">
            <a v-bind:href="repository.normalized_path" class="git-repository-card-link">
                <div class="tlp-pane-header git-repository-card-header">
                    <div class="git-repository-card-header-line">
                        <h2 class="tlp-pane-title git-repository-card-title"
                            data-test="repository_name"
                        >
                            {{ repository_label }}
                        </h2>
                        <div class="git-repository-links-spacer"></div>
                        <pull-request-badge
                            v-bind:number-pull-request="number_pull_requests"
                            v-bind:repository-id="repository.id"
                        />
                        <a v-if="is_admin"
                           v-bind:href="repository_admin_url"
                           class="git-repository-card-admin-link"
                        >
                            <i class="fa fa-cog" v-bind:title="administration_link_title"></i>
                        </a>
                    </div>
                    <div class="git-repository-card-last-update">
                        <i class="fa fa-clock-o git-repository-card-last-update-icon"></i>
                        <translate>Last update %{ formatted_last_update_date }</translate>
                    </div>
                </div>
                <section class="tlp-pane-section">
                    <p v-if="hasRepositoryDescription" class="git-repository-card-description">
                        {{ repository.description }}
                    </p>
                    <p v-else v-translate class="git-repository-card-description">Empty description</p>
                </section>
            </a>
        </div>
    </section>
</template>
<script>
const DEFAULT_DESCRIPTION = "-- Default description --";

import { mapGetters } from "vuex";
import TimeAgo from "javascript-time-ago";
import { getProjectId, getUserIsAdmin, getDashCasedLocale } from "../repository-list-presenter.js";
import PullRequestBadge from "./PullRequestBadge.vue";

export default {
    name: "GitRepository",
    components: {
        PullRequestBadge
    },
    props: {
        repository: Object
    },
    computed: {
        hasRepositoryDescription() {
            return this.repository.description !== DEFAULT_DESCRIPTION;
        },
        repository_admin_url() {
            return `/plugins/git/?action=repo_management&group_id=${getProjectId()}&repo_id=${
                this.repository.id
            }`;
        },
        is_admin() {
            return getUserIsAdmin();
        },
        administration_link_title() {
            return this.$gettext("Go to repository administration");
        },
        formatted_last_update_date() {
            const date = new Date(this.repository.last_update_date);
            const time_ago = new TimeAgo(getDashCasedLocale());
            return time_ago.format(date);
        },
        number_pull_requests() {
            return Number.parseInt(this.repository.additional_information.opened_pull_requests, 10);
        },
        repository_label() {
            if (this.isFolderDisplayMode) {
                return this.repository.label;
            }

            return this.repository.normalized_path;
        },
        ...mapGetters(["isFolderDisplayMode"])
    }
};
</script>
