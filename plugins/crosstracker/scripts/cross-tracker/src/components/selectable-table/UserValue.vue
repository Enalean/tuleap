<!--
  - Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
    <a
        v-if="props.user.user_uri.isValue()"
        class="user"
        v-bind:href="props.user.user_uri.unwrapOr('')"
        ><span class="tlp-avatar-small"
            ><img v-bind:alt="$gettext('User avatar')" v-bind:src="props.user.avatar_uri" /></span
        >{{ props.user.display_name }}</a
    >
    <span v-if="!props.user.user_uri.isValue()" class="user"
        ><span class="tlp-avatar-small"></span>{{ props.user.display_name }}</span
    >
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import type { UserCellValue } from "../../domain/ArtifactsTable";

const { $gettext } = useGettext();

const props = defineProps<{
    user: UserCellValue;
}>();
</script>

<style scoped lang="scss">
@use "../../../themes/links";

.user {
    @include links.link;

    display: flex;
    align-items: center;
}

.tlp-avatar-small {
    margin: 0 5px 0 0;
}
</style>
