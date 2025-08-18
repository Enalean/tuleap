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
  -->

<template>
    <table class="tlp-table baselines-table">
        <thead>
            <tr>
                <th class="tlp-table-cell-numeric baselines-table-column-header">
                    {{ $gettext("Id") }}
                </th>
                <th class="baselines-table-column-header">{{ $gettext("Name") }}</th>
                <th class="baselines-table-column-header">{{ $gettext("Milestone") }}</th>
                <th class="baselines-table-column-header">{{ $gettext("Snapshot Date") }}</th>
                <th class="baselines-table-column-header">{{ $gettext("Author") }}</th>
                <th></th>
            </tr>
        </thead>

        <tbody v-if="are_baselines_loading">
            <baseline-skeleton />
            <baseline-skeleton />
            <baseline-skeleton />
        </tbody>

        <tbody v-else-if="are_baselines_available">
            <baseline-list-item
                v-for="baseline in baselines"
                v-bind:key="baseline.id"
                v-bind:baseline="baseline"
            />
        </tbody>

        <tbody v-else>
            <tr>
                <td
                    colspan="6"
                    class="tlp-table-cell-empty"
                    key="no-baseline"
                    data-test-type="empty-baseline"
                >
                    {{ $gettext("No baseline available") }}
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script>
import BaselineSkeleton from "./BaselineSkeleton.vue";
import BaselineListItem from "./BaselineListItem.vue";
import { mapState, mapGetters, mapActions } from "vuex";

export default {
    name: "BaselinesList",

    components: { BaselineSkeleton, BaselineListItem },

    props: {
        project_id: { required: true, type: Number },
    },

    computed: {
        ...mapState("baselines", ["baselines", "are_baselines_loading"]),
        ...mapGetters("baselines", ["are_baselines_available"]),
    },

    methods: {
        ...mapActions("baselines", ["load"]),
    },

    mounted() {
        this.load({ project_id: this.project_id });
    },
};
</script>
