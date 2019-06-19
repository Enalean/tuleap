<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <div class="project-release"
         v-bind:class="{ 'project-release-toggle-closed': !is_open }"
         v-on:click="toggleReleaseDetails()"
    >
        <div class="project-release-toggle">
            <div class="project-release-icon">
                <i class="fa fa-ellipsis-h"></i>
            </div>
            <h1 class="project-release-title">
                <translate v-bind:translate-params="{ release_label: releaseData.label }">
                    Release %{release_label}
                </translate>
            </h1>
            <span class="project-release-date">
                {{ formatDate(releaseData.start_date) }}
                <i class="fa fa-long-arrow-right"></i>
                {{ formatDate(releaseData.end_date) }}
            </span>
        </div>
        <p v-if="is_open" class="empty-pane-text" v-translate>There is nothing here!</p>
    </div>
</template>

<script>
import { formatDateYearMonthDay } from "../../helpers/date-formatters";

export default {
    name: "ReleaseInformationDisplayer",
    props: {
        releaseData: Object
    },
    data() {
        return {
            is_open: false
        };
    },
    methods: {
        formatDate(date) {
            return formatDateYearMonthDay(date);
        },
        toggleReleaseDetails() {
            this.is_open = !this.is_open;
        }
    }
};
</script>
