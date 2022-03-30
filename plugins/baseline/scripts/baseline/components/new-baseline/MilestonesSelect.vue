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
    <div>
        <div class="new-baseline-modal-milestone-list-scrollbar">
            <div v-if="milestones.length === 0">
                <p
                    class="baseline-empty-information-message"
                    data-test-type="empty-milestones"
                    v-translate
                >
                    No milestone available
                </p>
            </div>
            <div v-bind:key="milestone.id" v-else v-for="milestone in sorted_milestones">
                <label class="tlp-label tlp-radio" data-test-type="milestone">
                    <input
                        name="label"
                        required
                        type="radio"
                        v-bind:value="milestone.id"
                        v-on:change="onMilestoneSelected"
                    />
                    {{ milestone.label }}
                </label>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "NewBaselineMilestoneSelect",

    props: {
        milestones: { mandatory: true, type: Array },
    },

    computed: {
        sorted_milestones() {
            return [...this.milestones].sort(function (milestone_a, milestone_b) {
                const id_a = milestone_a.id;
                const id_b = milestone_b.id;

                if (id_a < id_b) {
                    return 1;
                }
                if (id_a > id_b) {
                    return -1;
                }
                return 0;
            });
        },
    },

    methods: {
        onMilestoneSelected(event) {
            const milestone_id = Number(event.target.value);
            const milestone = this.milestones.find((milestone) => milestone.id === milestone_id);
            this.$emit("change", milestone);
        },
    },
};
</script>
