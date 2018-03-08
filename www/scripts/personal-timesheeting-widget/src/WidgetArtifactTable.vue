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

(<template>
    <div class="timesheeting-artifacts-table">
        <table class="tlp-table">
            <thead>
                <tr>
                    <th>{{ artifact_label }}</th>
                    <th>{{ project_label }}</th>
                    <th>{{ time_label }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="tlp-table-cell-section" colspan="3">{{ period_label }}</td>
                </tr>
            </tbody>
            <tbody>
                <tr>
                    <td colspan="3" class="tlp-table-cell-empty">
                        {{ empty_state }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>)
(<script>
    import { DateTime }         from 'luxon';
    import { gettext_provider } from './gettext-provider.js';

    export default {
        name: "WidgetArtifactTable",
        props: {
            startDate  : String,
            endDate    : String
        },
        data() {
            return {
                date_format: 'dd LLL yyyy'
            }
        },
        computed: {
            period_label() {
                return gettext_provider.gettext("From")
                    + ' '
                    + this.getFormattedDate(this.startDate)
                    + ' '
                    + gettext_provider.gettext('To')
                    + ' '
                    + this.getFormattedDate(this.endDate);
            },
            empty_state   : () => gettext_provider.gettext('There is nothing here ... for now ...'),
            artifact_label: () => gettext_provider.gettext('Artifact'),
            project_label : () => gettext_provider.gettext('Project'),
            time_label    : () => gettext_provider.gettext('Time')
        },
        methods: {
            getFormattedDate(string_date) {
                return DateTime.fromISO(string_date).toFormat(this.date_format);
            }
        }
    }
</script>)
