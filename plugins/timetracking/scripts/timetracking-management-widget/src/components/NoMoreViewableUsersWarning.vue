<!--
  - Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
    <div class="tlp-alert-warning" v-if="no_more_viewable_users.length > 0">
        {{
            $ngettext(
                "You don't have the permission to access this user times, user removed from list:",
                "You don't have the permission to access those users times, users removed from list:",
                no_more_viewable_users.length,
            )
        }}
        <code v-if="no_more_viewable_users.length === 1">{{
            no_more_viewable_users[0].display_name
        }}</code>
        <ul v-else>
            <li v-for="user in no_more_viewable_users" v-bind:key="user.id">
                <code>{{ user.display_name }}</code>
            </li>
        </ul>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { RETRIEVE_QUERY } from "../injection-symbols";

const { $ngettext } = useGettext();
const { no_more_viewable_users } = strictInject(RETRIEVE_QUERY);
</script>
