<!--
  - Copyright Enalean (c) 2018. All rights reserved.
  -
  - Tuleap and Enalean names and logos are registrated trademarks owned by
  - Enalean SAS. All other trademarks or names are properties of their respective
  - owners.
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

(<template>
    <tr>
        <td>
            <a v-bind:href="timeData.artifact.html_url">
                <span class="tlp-badge-primary tlp-badge-outline timetracking-badge-direct-link-to-artifact">
                    {{ timeData.artifact.xref }}
                </span>
                <span>
                    {{ timeData.artifact.title }}
                </span>
            </a>
        </td>
        <td>{{ timeData.project.label }}</td>
        <td class="tlp-table-cell-numeric">{{ getFormattedMinutes(timeData.minutes) }}</td>
    </tr>
</template>)
(<script>
    import { formatMinutesToISO } from "./time-formatters.js";

    export default {
        name: 'WidgetArtifactTableRow',
        props: {
            timeData: Object
        },
        methods: {
            getFormattedMinutes: (minutes) => formatMinutesToISO(minutes)
        }
    }
</script>)
