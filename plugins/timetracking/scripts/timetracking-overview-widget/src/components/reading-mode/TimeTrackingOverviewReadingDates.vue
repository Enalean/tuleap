<!--
  - Copyright Enalean (c) 2019 - Present. All rights reserved.
  -
  -  Tuleap and Enalean names and logos are registrated trademarks owned by
  -  Enalean SAS. All other trademarks or names are properties of their respective
  -  owners.
  -
  -  This file is a part of Tuleap.
  -
  -  Tuleap is free software; you can redistribute it and/or modify
  -  it under the terms of the GNU General Public License as published by
  -  the Free Software Foundation; either version 2 of the License, or
  -  (at your option) any later version.
  -
  -  Tuleap is distributed in the hope that it will be useful,
  -  but WITHOUT ANY WARRANTY; without even the implied warranty of
  -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  -  GNU General Public License for more details.
  -
  -  You should have received a copy of the GNU General Public License
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
    <div class="timetracking-reading-mode-dates">
        <div class="tlp-property timetracking-reading-date">
            <label class="tlp-label">{{ $gettext("From") }}</label>
            <span>{{ overview_store.start_date }}</span>
        </div>
        <div class="tlp-property timetracking-reading-date">
            <label class="tlp-label">{{ $gettext("To") }}</label>
            <span>{{ overview_store.end_date }}</span>
        </div>
    </div>
</template>

<script>
import { inject } from "vue";
import { useOverviewWidgetStore } from "../../store/index.js";

export default {
    name: "TimeTrackingOverviewReadingDates",
    setup: () => {
        const overview_store = useOverviewWidgetStore(inject("report_id"))();
        return { overview_store };
    },
};
</script>
