<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
    <div class="tlp-framed">
        <div class="tlp-pane">
            <section class="empty-state-page">
                <div class="empty-state-illustration">
                    <slot name="empty-state-image"></slot>
                </div>
                <h1 class="empty-state-title">
                    {{ title }}
                </h1>
                <p class="empty-state-text">
                    {{ message }}
                </p>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps({
    title: {
        type: String,
        required: true,
    },
    message: {
        type: String,
        required: true,
    },
});
</script>

<style lang="scss" scoped>
.empty-state-page {
    margin-bottom: calc(4 * var(--tlp-large-spacing));
}
</style>
