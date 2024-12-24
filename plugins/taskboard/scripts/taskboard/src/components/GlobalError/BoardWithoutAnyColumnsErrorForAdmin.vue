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
  -
  -->
<template>
    <section class="empty-state-page">
        <div class="empty-state-illustration">
            <board-without-any-columns-error-for-admin-illustration />
        </div>
        <h1 class="empty-state-title">
            {{ $gettext("This taskboard is not properly configured") }}
        </h1>
        <p class="empty-state-text">
            {{ $gettext("You have to configure the columns mapping.") }}
        </p>
        <a class="tlp-button-primary empty-state-action" v-bind:href="admin_url">
            <i class="fas fa-long-arrow-alt-right tlp-button-icon" aria-hidden="true"></i>
            {{ $gettext("Define columns") }}
        </a>
    </section>
</template>
<script setup lang="ts">
import { useState } from "vuex-composition-helpers";
import BoardWithoutAnyColumnsErrorForAdminIllustration from "./BoardWithoutAnyColumnsErrorForAdminIllustration.vue";
const { admin_url } = useState(["admin_url"]);
</script>
