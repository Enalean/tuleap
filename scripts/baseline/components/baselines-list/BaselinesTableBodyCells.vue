<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
    <tbody>
        <tr v-for="baseline in baselines" v-bind:key="baseline.id" data-test-type="baseline">
            <td class="tlp-table-cell-numeric">
                <a href="#" v-on:click.prevent="showBaseline(baseline)">
                    {{ baseline.id }}
                </a>
            </td>
            <td class="baselines-table-column-name">{{ baseline.name }}</td>
            <td class="baselines-table-column-milestone">
                <artifact-link class="baselines-table-column-milestone" v-bind:artifact="baseline.artifact">
                    <artifact-badge v-bind:artifact="baseline.artifact"/>{{ baseline.artifact.title }}
                </artifact-link>
            </td>
            <td class="baselines-table-column-snapshot-date">
                <humanized-date v-bind:date="baseline.snapshot_date"/>
            </td>
            <td class="baselines-table-column-author">
                <user-badge v-bind:user="baseline.author"/>
            </td>
            <td class="tlp-table-cell-actions">
                <show-baseline-button class="tlp-table-cell-actions-button" v-bind:baseline="baseline"/>
                <delete-baseline-button class="tlp-table-cell-actions-button" v-bind:baseline="baseline"/>
            </td>
        </tr>
    </tbody>
</template>

<script>
import HumanizedDate from "../common/HumanizedDate.vue";
import UserBadge from "../common/UserBadge.vue";
import ArtifactLink from "../common/ArtifactLink.vue";
import ArtifactBadge from "../common/ArtifactBadge.vue";
import DeleteBaselineButton from "./DeleteBaselineButton.vue";
import ShowBaselineButton from "./ShowBaselineButton.vue";

export default {
    name: "BaselinesTableBodyCells",
    components: {
        ShowBaselineButton,
        ArtifactLink,
        ArtifactBadge,
        HumanizedDate,
        UserBadge,
        DeleteBaselineButton
    },
    props: {
        baselines: { required: true, type: Array }
    },
    methods: {
        showBaseline(baseline) {
            this.$router.push({
                name: "BaselineContentPage",
                params: { baseline_id: baseline.id }
            });
        }
    }
};
</script>
