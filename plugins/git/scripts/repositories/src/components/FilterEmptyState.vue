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
    <div class="empty-page" v-if="show_filter_empty_state">
        <div class="empty-page-text">
            <translate>No repository name matching your query has been found.</translate>
        </div>
    </div>
</template>
<script>
import { mapGetters } from "vuex";
export default {
    name: "FilterEmptyState",
    computed: {
        show_filter_empty_state() {
            return (
                !this.isCurrentRepositoryListEmpty &&
                this.isInitialLoadingDoneWithoutError &&
                this.isFiltering &&
                !this.isThereAResultInCurrentFilteredList
            );
        },
        ...mapGetters([
            "isThereAResultInCurrentFilteredList",
            "isCurrentRepositoryListEmpty",
            "isInitialLoadingDoneWithoutError",
            "isFiltering",
        ]),
    },
};
</script>
