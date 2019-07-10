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
    <div class="project-release-toggle" v-on:click="$emit('toggleReleaseDetails')">
        <div class="project-release-icon">
            <i class="fa fa-ellipsis-h"></i>
        </div>
        <h1 class="project-release-title">
            <translate v-bind:translate-params="{ release_label: releaseData.label }">
                Release %{release_label}
            </translate>
        </h1>
        <span class="project-release-date" v-if="startDateExist()">
            {{ formatDate(releaseData.start_date) }}
            <i class="fa fa-long-arrow-right" data-test="display-arrow"></i>
            {{ formatDate(releaseData.end_date) }}
        </span>
    </div>
</template>

<script>
import { formatDateYearMonthDay } from "../../helpers/date-formatters";

export default {
    name: "ReleaseHeader",
    props: {
        releaseData: Object
    },
    methods: {
        formatDate(date) {
            return formatDateYearMonthDay(date);
        },
        startDateExist() {
            return this.releaseData.start_date;
        }
    }
};
</script>
