<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
    <div
        class="document-file-upload-progress"
        v-bind:class="{ 'document-file-upload-progress-error': nb_uploads_in_error > 0 }"
    >
        <span class="document-file-upload-progress-value">{{ progress }}%</span>
        <progress
            class="document-file-upload-progress-bar"
            max="100"
            v-bind:value="progress"
            data-test="document-progress-bar"
        ></progress>
    </div>
</template>

<script setup lang="ts">
defineProps<{ progress: number; nb_uploads_in_error: number }>();
</script>
