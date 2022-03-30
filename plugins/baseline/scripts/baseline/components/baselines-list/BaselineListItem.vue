<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
  -
  -->

<template>
    <tr>
        <td class="tlp-table-cell-numeric">
            <a href="#" v-on:click.prevent="showBaseline(baseline)">
                {{ baseline.id }}
            </a>
        </td>
        <td class="baselines-table-column-name">{{ baseline.name }}</td>
        <td class="baselines-table-column-milestone">
            <artifact-link class="baselines-table-column-milestone" v-bind:artifact="milestone">
                <artifact-badge v-bind:artifact="milestone" v-bind:tracker="milestone_tracker" />
                {{ milestone.title }}
            </artifact-link>
        </td>
        <td class="baselines-table-column-snapshot-date">
            <humanized-date v-bind:date="baseline.snapshot_date" v-bind:start_with_capital="true" />
        </td>
        <td class="baselines-table-column-author">
            <user-badge v-bind:user="author" class="baseline-badge-avatar" />
        </td>
        <td class="tlp-table-cell-actions">
            <consult-baseline-button
                class="tlp-table-cell-actions-button"
                v-bind:baseline="baseline"
            />
            <delete-baseline-button
                class="tlp-table-cell-actions-button"
                v-bind:baseline="baseline"
            />
        </td>
    </tr>
</template>

<script>
import HumanizedDate from "../common/HumanizedDate.vue";
import UserBadge from "../common/UserBadge.vue";
import ArtifactLink from "../common/ArtifactLink.vue";
import ArtifactBadge from "../common/ArtifactBadge.vue";
import DeleteBaselineButton from "./DeleteBaselineButton.vue";
import ConsultBaselineButton from "./ConsultBaselineButton.vue";
import { mapGetters } from "vuex";

export default {
    name: "BaselineListItem",
    components: {
        ConsultBaselineButton,
        ArtifactLink,
        ArtifactBadge,
        HumanizedDate,
        UserBadge,
        DeleteBaselineButton,
    },
    props: {
        baseline: { required: true, type: Object },
    },
    computed: {
        ...mapGetters(["findArtifactById", "findTrackerById", "findUserById"]),
        milestone() {
            return this.findArtifactById(this.baseline.artifact_id);
        },
        milestone_tracker() {
            return this.findTrackerById(this.milestone.tracker.id);
        },
        author() {
            return this.findUserById(this.baseline.author_id);
        },
    },
    methods: {
        showBaseline(baseline) {
            this.$router.push({
                name: "BaselineContentPage",
                params: { baseline_id: baseline.id },
            });
        },
    },
};
</script>
