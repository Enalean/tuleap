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
            <translate>This is a private folder</translate>
        </h1>
        <p class="empty-state-text" v-translate>
            You may only access documents you are granted read permission on.
        </p>
        <a href="/my/" class="empty-state-action tlp-button-primary">
            <i class="fa fa-long-arrow-right tlp-button-icon"></i>
            <translate>Go to my home page</translate>
        </a>
    </section>
</template>

<script setup lang="ts">
import PermissionErrorSvg from "../../svg/error/PermissionErrorSvg.vue";
</script>
<script lang="ts">
import { defineComponent } from "@vue/composition-api";
export default defineComponent({});
</script>
