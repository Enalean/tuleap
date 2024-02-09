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
    <div class="tlp-form-element">
        <div class="tlp-form-element tlp-form-element-append">
            <select
                class="timetracking-overview-users-selector-input tlp-select tlp-select-small"
                id="tracker"
                ref="select"
                v-on:input="setSelected"
                data-test="timetracking-overview-users-selector"
            >
                <option v-bind:value="null">{{ $gettext("All users") }}</option>
                <option
                    v-for="user in overview_store.users"
                    v-bind:value="user.user_id"
                    v-bind:key="user.user_id"
                >
                    {{ user.user_name }}
                </option>
            </select>
        </div>
    </div>
</template>
<script>
import { inject } from "vue";
import { useOverviewWidgetStore } from "../store/index.js";

export default {
    name: "TimeTrackingOverviewUserList",
    setup: () => {
        const overview_store = useOverviewWidgetStore(inject("report_id"))();
        return { overview_store };
    },
    methods: {
        setSelected() {
            this.overview_store.setSelectedUser(this.$refs.select.value);
        },
    },
};
</script>
