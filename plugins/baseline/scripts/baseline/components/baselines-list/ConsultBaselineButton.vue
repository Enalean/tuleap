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
  -
  -->
<template>
    <action-button icon="eye" v-on:click="showBaseline()">
        <span v-translate>Consult</span>
    </action-button>
</template>
<script>
import ActionButton from "../common/ActionButton.vue";
export default {
    name: "ConsultBaselineButton",
    components: { ActionButton },
    props: {
        baseline: {
            required: true,
            type: Object,
        },
    },
    methods: {
        showBaseline() {
            this.$router.push({
                name: "BaselineContentPage",
                params: { baseline_id: this.baseline.id },
            });
        },
    },
};
</script>
