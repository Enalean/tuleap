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
            <board-without-any-columns-error-for-users-illustration />
        </div>
        <h1 class="empty-state-title">
            {{ $gettext("This taskboard is not ready to be used") }}
        </h1>
        <p class="empty-state-text">
            {{ $gettext("Your project administrator needs to finish the planning configuration.") }}
        </p>
    </section>
</template>
<script setup lang="ts">
import BoardWithoutAnyColumnsErrorForUsersIllustration from "./BoardWithoutAnyColumnsErrorForUsersIllustration.vue";
</script>
