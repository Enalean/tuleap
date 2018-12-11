<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
    <td
        class="tracker-workflow-transition-row-content"
        v-bind:class="{
            'tracker-workflow-transition-row-content-forbidden': !is_allowed,
            'tracker-workflow-transition-row-content-empty': is_empty
        }"
    >
        <template v-if="transition">
            <div class="tracker-workflow-transition-row-content-active"></div>
            <button
                class="tlp-button-primary tlp-button-mini tracker-workflow-advanced-transition-button"
                v-translate
                disabled
            >
                Configure
            </button>
        </template>
    </td>
</template>

<script>
export default {
    name: "TransitionMatrixContent",

    props: {
        from: {
            type: Object,
            mandatory: true
        },
        to: {
            type: Object,
            mandatory: true
        },
        transition: {
            type: Object
        }
    },

    computed: {
        is_allowed() {
            return this.from.id !== this.to.id;
        },
        is_empty() {
            return this.is_allowed && !this.transition;
        }
    }
};
</script>
