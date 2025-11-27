<!--
  - Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
    <section class="empty-state-page">
        <div class="empty-state-illustration">
            <empty-state-tumbleweed />
        </div>
        <h1 class="empty-state-title">
            {{ $gettext("No fields") }}
        </h1>
        <p class="empty-state-text">
            {{ $gettext("This tracker does not have any field yet.") }}
        </p>
    </section>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import EmptyStateTumbleweed from "./EmptyStateTumbleweed.vue";

const { $gettext } = useGettext();
</script>
