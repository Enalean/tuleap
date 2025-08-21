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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="tlp-alert-info document-preview-item-is-locked-icon">
        {{ $gettext("This document is currently locked by") }}
        <user-name v-bind:user="lock_info.lock_by" />
    </div>
</template>

<script setup lang="ts">
import type { LockInfo } from "../../type";
import UserName from "../User/UserName.vue";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

defineProps<{
    lock_info: LockInfo;
}>();
</script>
