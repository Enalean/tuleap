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
    <div>
        <div class="release-description" v-dompurify-html="releaseData.description"></div>
        <a v-bind:href="getOverviewLink" data-test="overview-link">
            <i class="fa fa-long-arrow-right"></i>
            <translate> Go to release overview </translate>
        </a>
    </div>
</template>

<script>
export default {
    name: "ReleaseDescription",
    props: {
        releaseData: Object
    },
    computed: {
        getOverviewLink() {
            return (
                "/plugins/agiledashboard/?group_id=" +
                encodeURIComponent(this.$store.state.project_id) +
                "&planning_id=" +
                encodeURIComponent(this.releaseData.planning.id) +
                "&action=show&aid=" +
                encodeURIComponent(this.releaseData.id) +
                "&pane=details"
            );
        }
    }
};
</script>
