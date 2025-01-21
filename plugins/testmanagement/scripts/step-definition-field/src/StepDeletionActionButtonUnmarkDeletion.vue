<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <button class="btn" type="button" v-on:click="unmarkDeletion()">
        <i class="fa fa-undo"></i>
        {{ $gettext("Undo deletion") }}
    </button>
</template>
<script>
export default {
    name: "StepDeletionActionButtonUnmarkDeletion",
    props: {
        step: {
            type: Object,
            required: true,
        },
    },
    methods: {
        unmarkDeletion() {
            this.$store.commit("setStepDeleted", [this.step, false]);
        },
    },
};
</script>
