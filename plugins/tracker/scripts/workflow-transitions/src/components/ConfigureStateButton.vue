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
    <button
        type="button"
        class="tlp-button-primary tlp-button-small tracker-workflow-transition-column-button"
        data-test="configure-state"
        v-bind:class="{ 'tlp-button-success': transition.updated }"
        v-on:click="openModal()"
    >
        {{ $gettext("Configure state") }}
    </button>
</template>
<script>
export default {
    name: "ConfigureStateButton",
    props: {
        transition: {
            type: Object,
            required: true,
        },
    },
    methods: {
        openModal() {
            this.$store.dispatch(
                "transitionModal/showTransitionConfigurationModal",
                this.transition,
            );
        },
    },
};
</script>
