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
    <tr>
        <td v-if="is_folder">
            <router-link v-bind:to="{ name: 'folder', params: { item_id: item.item_id }}">
                {{ item.name }}
            </router-link>
        </td>
        <td v-else> {{ item.name }}</td>
        <td><user-badge v-bind:user="item.owner"/></td>
        <td>{{ formatted_last_update_date }}</td>
    </tr>
</template>

<script>
import TimeAgo from "javascript-time-ago";
import { mapState } from "vuex";
import UserBadge from "./UserBadge.vue";
import { ITEM_TYPE_FOLDER } from "../../constants.js";

export default {
    name: "FolderContentRow",
    components: { UserBadge },
    props: {
        item: Object
    },
    computed: {
        ...mapState(["user_locale"]),
        formatted_last_update_date() {
            const date = new Date(this.item.last_update_date);
            const time_ago = new TimeAgo(this.user_locale);
            return time_ago.format(date);
        },
        is_folder() {
            return this.item.type === ITEM_TYPE_FOLDER;
        }
    }
};
</script>
