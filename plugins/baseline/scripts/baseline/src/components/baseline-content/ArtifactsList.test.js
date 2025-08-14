/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../support/global-options-for-tests";
import ContentArtifact from "./ContentArtifact.vue";
import ArtifactsList from "./ArtifactsList.vue";

describe("ArtifactsList", () => {
    function getWrapper() {
        return shallowMount(ArtifactsList, {
            global: { ...getGlobalTestOptions() },
            props: {
                artifacts: [
                    {
                        id: 101,
                        title: "Sprint-1",
                        status: "Planned",
                        tracker_id: 1,
                        initial_effort: null,
                        tracker_name: "Sprint",
                        description:
                            "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                        linked_artifact_ids: [],
                    },
                    {
                        id: 102,
                        title: "Sprint-2",
                        status: "Planned",
                        tracker_id: 1,
                        initial_effort: null,
                        tracker_name: "Sprint",
                        description:
                            "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                        linked_artifact_ids: [],
                    },
                    {
                        id: 103,
                        title: "Sprint-3",
                        status: "Planned",
                        tracker_id: 1,
                        initial_effort: null,
                        tracker_name: "Sprint",
                        description:
                            "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
                        linked_artifact_ids: [],
                    },
                ],
            },
        });
    }

    it("shows as many artifacts as given", () => {
        const wrapper = getWrapper();

        expect(wrapper.findAllComponents(ContentArtifact)).toHaveLength(3);
    });
});
