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

<template>
    <tr>
        <td>
            <a class="direct-link-to-artifact" v-bind:href="artifact.badge.uri">
                <span class="tlp-badge-outline link-to-tracker-badge" v-bind:class="badge_color" >
                    {{ artifact.badge.cross_ref }}
                </span>{{ artifact.title }}
            </a>
        </td>
        <td>{{ artifact.project.label }}</td>
        <td>{{ artifact.status }}</td>
        <td>{{ artifact.formatted_last_update_date }}</td>
        <td><a v-bind:href="artifact.submitted_by.user_url">{{ artifact.submitted_by.display_name }}</a></td>
        <td><a v-bind:href="artifact.user_url" v-if="artifact.assigned_to === true">{{ artifact.display_name }}</a></td>
    </tr>
</template>

(<script>
    export default {
        name: 'ArtifactTableRow',
        props: [ 'artifact' ],
        computed: {
            badge_color() {
                return 'tlp-badge-' + this.artifact.badge.color;
            }
        }
    };
</script>)
