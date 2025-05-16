<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <section class="empty-state-page document-folder-with-error">
        <div class="empty-state-illustration">
            <permission-error-svg />
        </div>
        <h1 class="empty-state-title">
            {{ $gettext("This is a private folder") }}
        </h1>
        <p class="empty-state-text">
            {{ $gettext("You may only access documents you are granted read permission on.") }}
        </p>
        <a href="/my/" class="empty-state-action tlp-button-primary">
            <i class="fa-solid fa-arrow-right-long tlp-button-icon"></i>
            {{ $gettext("Go to my home page") }}
        </a>
    </section>
</template>

<script setup lang="ts">
import PermissionErrorSvg from "../../svg/error/PermissionErrorSvg.vue";
</script>
