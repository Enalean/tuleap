<!--
  - Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <div class="popover-information">
        <span ref="popover_icon">
            <i class="fas fa-question-circle popover-search-icon"></i>
        </span>
        <section class="tlp-popover" ref="popover_content">
            <div class="tlp-popover-arrow"></div>
            <div class="tlp-popover-header">
                <h1 class="tlp-popover-title" v-translate>Global search information</h1>
            </div>
            <div class="tlp-popover-body">
                <p v-translate>
                    Global search will search in all text properties of document (but does not look
                    inside the document).
                </p>
                <p v-translate>Search allowed pattern:</p>
                <ul>
                    <li>{{ exact_message_pattern }}</li>
                    <li>{{ starting_message_pattern }}</li>
                    <li>{{ finishing_message_pattern }}</li>
                    <li>{{ containing_message_pattern }}</li>
                </ul>
            </div>
        </section>
    </div>
</template>

<script lang="ts">
import type { Popover } from "tlp";
import { createPopover } from "tlp";
import Vue from "vue";
import { Component } from "vue-property-decorator";

@Component
export default class SearchInformationPopover extends Vue {
    private popover: Popover | undefined;

    mounted(): void {
        const trigger = this.$refs.popover_icon;
        if (!(trigger instanceof HTMLElement)) {
            return;
        }

        const content = this.$refs.popover_content;
        if (!(content instanceof HTMLElement)) {
            return;
        }

        this.popover = createPopover(trigger, content, {
            anchor: trigger,
            placement: "bottom-start",
        });
    }

    beforeDestroy(): void {
        if (this.popover) {
            this.popover.destroy();
        }
    }

    get exact_message_pattern(): string {
        return this.$gettext('lorem => exactly "lorem"');
    }

    get starting_message_pattern(): string {
        return this.$gettext('lorem* => starting by "lorem"');
    }

    get finishing_message_pattern(): string {
        return this.$gettext('*lorem => finishing by "lorem"');
    }

    get containing_message_pattern(): string {
        return this.$gettext('lorem => exactly "lorem"');
    }
}
</script>
