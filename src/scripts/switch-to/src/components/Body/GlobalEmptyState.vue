<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <section class="empty-state-pane switch-to-modal-body-global-empty">
        <global-empty-state-svg />

        <p
            class="empty-state-text empty-state-text-smaller switch-to-modal-body-global-empty-text"
            v-translate
        >
            You don’t belong to any project or haven’t viewed anything yet
        </p>

        <trove-cat-link
            class="tlp-button-secondary switch-to-projects-softwaremap-empty-state empty-state-action"
        >
            {{ trove_cat_label }}
        </trove-cat-link>
    </section>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import GlobalEmptyStateSvg from "./GlobalEmptyStateSvg.vue";
import TroveCatLink from "./TroveCatLink.vue";

@Component({
    components: { TroveCatLink, GlobalEmptyStateSvg },
})
export default class GlobalEmptyState extends Vue {
    get trove_cat_label(): string {
        return this.$gettext("Start exploring Tuleap");
    }
}
</script>
