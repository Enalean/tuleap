<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <div
        class="timetracking-reading-mode"
        v-on:click="toggleReadingMode()"
        data-test="timetracking-switch-reading-mode"
    >
        <div class="timetracking-reading-mode-dates">
            <div class="tlp-property timetracking-reading-date">
                <label class="tlp-label">{{ $gettext("From") }}</label>
                <span>{{ start_date }}</span>
            </div>
            <div class="tlp-property timetracking-reading-date">
                <label class="tlp-label">{{ $gettext("To") }}</label>
                <span>{{ end_date }}</span>
            </div>
        </div>
    </div>
</template>
<script>
import { mapState, mapMutations } from "vuex";

export default {
    name: "WidgetReadingMode",
    computed: {
        ...mapState(["start_date", "end_date"]),
    },
    methods: {
        ...mapMutations(["toggleReadingMode"]),
    },
};
</script>
