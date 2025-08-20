<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->

<template>
    <span>
        <a
            v-bind:href="user.user_url"
            class="document-badge-avatar-username"
            v-if="!user.is_anonymous"
            data-test="document-user-profile-link"
            data-shortcut-author
        >
            {{ user.display_name }}
        </a>
        <span v-else class="document-badge-avatar-username">{{ user.display_name }}</span>
    </span>
</template>

<script setup lang="ts">
import type { User } from "../../type";
import type { RestUser } from "../../api/rest-querier";

defineProps<{ user: User | RestUser }>();
</script>
