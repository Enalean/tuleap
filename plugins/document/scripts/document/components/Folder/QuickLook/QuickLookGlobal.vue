<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->
<template>
    <div class="tlp-pane-container">
        <div class="tlp-pane-header document-quick-look-header">
            <h2 class="tlp-pane-title document-quick-look-title" v-bind:title="item.title">
                <i class="tlp-pane-title-icon"></i>
                {{ item.title }}
            </h2>
            <div class="document-quick-look-close-button" v-on:click="closeQuickLookEvent">
                ×
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "QuicklookGlobal",
    props: {
        item: Object
    },
    methods: {
        closeQuickLookEvent() {
            this.$emit("closeQuickLookEvent", false);
        }
    }
};
</script>
