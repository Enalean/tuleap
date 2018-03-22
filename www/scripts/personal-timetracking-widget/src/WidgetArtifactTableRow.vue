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
            <a v-bind:href="artifact.html_url">
                <span class="tlp-badge-outline timetracking-badge-direct-link-to-artifact"
                      v-bind:class="badge_color"
                >
                    {{ artifact.xref }}
                </span>
                <span>
                    {{ artifact.title }}
                </span>
            </a>
        </td>
        <td>{{ project.label }}</td>
        <td class="tlp-table-cell-numeric">{{ getFormattedAggregatedTime() }}</td>
    </tr>
</template>)
(<script>
    import { formatMinutes } from "./time-formatters.js";

    export default {
        name: 'WidgetArtifactTableRow',
        props: {
            timeData: Array
        },
        data() {
            const data = this.timeData[0];

            return {
                artifact: data.artifact,
                project : data.project
            };
        },
        computed: {
            badge_color() {
                return 'tlp-badge-' + this.artifact.badge_color;
            }
        },
        methods: {
            getFormattedAggregatedTime() {
                const minutes = this.timeData.reduce(
                    (sum, { minutes }) => minutes + sum,
                    0
                );

                return formatMinutes(minutes);
            }
        }
    }
</script>)
