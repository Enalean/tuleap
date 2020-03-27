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
    <div class="document-badge-avatar">
        <div class="tlp-avatar-small document-badge-avatar-img">
            <img
                v-bind:src="user.avatar_url"
                v-if="user.has_avatar"
                data-test="document-user-avatar"
            />
        </div>
        <user-name v-bind:user="user" />
    </div>
</template>

<script>
import UserName from "./UserName.vue";

export default {
    name: "UserBadge",
    components: { UserName },
    props: {
        user: Object,
    },
};
</script>
