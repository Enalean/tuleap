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
    <div class="switch-to-modal-body-global-empty">
        <global-empty-state-svg />

        <translate class="switch-to-modal-body-global-empty-text">
            You don’t belong to any project or haven’t viewed anything yet
        </translate>

        <a
            v-if="is_trove_cat_enabled"
            href="/softwaremap/trove_list.php"
            class="tlp-button-secondary switch-to-projects-softwaremap-empty-state"
            data-test="trove-cat-link"
        >
            {{ trove_cat_label }}
        </a>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import GlobalEmptyStateSvg from "./GlobalEmptyStateSvg.vue";
import { State } from "vuex-class";
@Component({
    components: { GlobalEmptyStateSvg },
})
export default class GlobalEmptyState extends Vue {
    @State
    private readonly is_trove_cat_enabled!: boolean;

    get trove_cat_label(): string {
        return this.$gettext("Start exploring Tuleap");
    }
}
</script>
