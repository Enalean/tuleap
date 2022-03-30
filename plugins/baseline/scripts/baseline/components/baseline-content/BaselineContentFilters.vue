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
    <div class="tlp-form-element">
        <label class="tlp-label" for="hidden-trackers" v-translate>Hidden trackers</label>
        <trackers-multi-select
            id="hidden-trackers"
            v-bind:trackers="all_trackers"
            v-on:change="onChange"
        />
    </div>
</template>
<script>
import TrackersMultiSelect from "../common/TrackersMultiSelect.vue";
import { mapGetters } from "vuex";

export default {
    name: "BaselineContentFilters",
    components: { TrackersMultiSelect },

    computed: mapGetters("current_baseline", ["all_trackers"]),

    methods: {
        onChange(trackers) {
            this.$store.commit("current_baseline/filterTrackers", trackers);
        },
    },
};
</script>
