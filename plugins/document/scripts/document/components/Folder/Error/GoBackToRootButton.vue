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
    <router-link
        class="empty-state-action tlp-button-primary"
        v-bind:to="{ name: 'root_folder' }"
        v-if="route.name !== 'root_folder'"
        data-test="item-can-go-to-root-button"
    >
        <i class="fa-solid fa-arrow-right-long tlp-button-icon"></i>
        {{ $gettext("Go to Document manager home page") }}
    </router-link>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { useRoute } from "vue-router";

const { $gettext } = useGettext();
const route = useRoute();
</script>
