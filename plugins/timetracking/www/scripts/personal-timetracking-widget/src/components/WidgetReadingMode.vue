/**
* Copyright (c) Enalean, 2018. All Rights Reserved.
*
* This file is a part of Tuleap.
*
* Tuleap is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* Tuleap is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
*/

(
<template>
    <div class="timetracking-reading-mode"
         v-on:click="toggleReadingMode()"
    >
        <div class="timetracking-reading-mode-dates">
            <div class="tlp-property timetracking-reading-date">
                <label class="tlp-label">{{ start_date_label }}</label>
                <span>{{ start_date }}</span>
            </div>
            <div class="tlp-property timetracking-reading-date">
                <label class="tlp-label">{{ end_date_label }}</label>
                <span>{{ end_date }}</span>
            </div>
        </div>
    </div>
</template>
)(
<script>
import { gettext_provider } from "../gettext-provider.js";
import { mapState, mapMutations } from "vuex";

export default {
    name: "WidgetReadingMode",
    computed: {
        ...mapState(["start_date", "end_date"]),
        start_date_label: () => gettext_provider.gettext("From"),
        end_date_label: () => gettext_provider.gettext("To")
    },
    methods: {
        ...mapMutations(["toggleReadingMode"])
    }
};
</script>
)
