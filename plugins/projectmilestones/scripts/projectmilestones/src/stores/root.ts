/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
 */

import { defineStore } from "pinia";
import type {
    State,
    MilestoneData,
    MilestoneContent,
    TestManagementCampaign,
    TrackerNumberArtifacts,
} from "../type";
import { COUNT } from "../type";
import {
    getAllSprints,
    getCurrentMilestones as getAllCurrentMilestones,
    getLastRelease as getLast,
    getMilestonesBacklog,
    getMilestonesContent,
    getMilestonesContent as getContent,
    getNbOfPastRelease,
    getTestManagementCampaigns as getTTMCampaigns,
} from "../api/rest-querier";
import { getSortedSprints } from "../helpers/milestones-sprints-helper";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

export const useStore = defineStore("root", {
    state: (): State => ({
        project_id: 0,
        project_name: "",
        nb_upcoming_releases: 0,
        nb_backlog_items: 0,
        trackers_agile_dashboard: [],
        error_message: null,
        offset: 0,
        limit: 50,
        is_loading: false,
        current_milestones: [],
        label_tracker_planning: "",
        is_timeframe_duration: false,
        label_start_date: "",
        label_timeframe: "",
        user_can_view_sub_milestones_planning: false,
        burnup_mode: COUNT,
        nb_past_releases: 0,
        last_release: null,
    }),
    actions: {
        async getCurrentMilestones(): Promise<void> {
            this.resetErrorMessage();
            let milestones: MilestoneData[] = [];

            if (this.project_id !== null) {
                milestones = await getAllCurrentMilestones({
                    project_id: this.project_id,
                    limit: this.limit,
                    offset: this.offset,
                });
            }

            if (milestones.length > 0 && milestones[0].id !== undefined) {
                milestones.sort((milestone_1, milestone_2) => milestone_2.id - milestone_1.id);
                this.setCurrentMilestones(milestones);
            }
        },

        getEnhancedMilestones(milestone: MilestoneData): Promise<MilestoneData> {
            const milestone_data = async (): Promise<MilestoneData> => {
                const sprints = await getAllSprints(milestone.id, {
                    limit: this.limit,
                    offset: this.offset,
                });
                const sorted_sprints = getSortedSprints(sprints);
                await this.getInitialEffortOfRelease(milestone);
                await this.getNumberArtifactsInTrackerOfAgileDashboard(milestone, sprints);
                return {
                    ...milestone,
                    total_sprint: sprints.length,
                    total_closed_sprint: sorted_sprints.closed_sprints.length,
                    open_sprints: sorted_sprints.open_sprints,
                };
            };
            return milestone_data();
        },

        async getMilestones(): Promise<void> {
            try {
                this.setIsLoading(true);
                await this.getNumberOfPastRelease();
                await this.getCurrentMilestones();
                await this.getLastRelease();
            } catch (error) {
                await this.handleErrorMessage(error);
            } finally {
                this.setIsLoading(false);
            }
        },

        async getNumberOfPastRelease(): Promise<void> {
            this.resetErrorMessage();

            let total = 0;
            if (this.project_id !== null) {
                total = await getNbOfPastRelease({
                    project_id: this.project_id,
                    limit: this.limit,
                    offset: this.offset,
                });
            }

            this.setNbPastReleases(total);
        },

        async getLastRelease(): Promise<void> {
            this.resetErrorMessage();

            let last_milestone = null;
            if (this.project_id !== null) {
                last_milestone = await getLast(this.project_id, this.nb_past_releases);
            }

            if (last_milestone) {
                this.setLastRelease(last_milestone[0]);
            }
        },

        async getInitialEffortOfRelease(milestone: MilestoneData): Promise<void> {
            const milestone_contents = await getContent(milestone.id, {
                limit: this.limit,
                offset: this.offset,
            });

            milestone.initial_effort = milestone_contents.reduce(
                (nb_users_stories: number, milestone_content: MilestoneContent) => {
                    if (milestone_content.initial_effort !== null) {
                        return nb_users_stories + milestone_content.initial_effort;
                    }
                    return nb_users_stories;
                },
                0,
            );
        },

        async getItemsOfSprints(sprints: MilestoneData[]): Promise<MilestoneContent[]> {
            const items_on_sprint: MilestoneContent[] = [];

            for (const sprint of sprints) {
                items_on_sprint.push(
                    ...(await getMilestonesContent(sprint.id, {
                        limit: this.limit,
                        offset: this.offset,
                    })),
                );
            }

            return items_on_sprint;
        },

        async getNumberArtifactsInTrackerOfAgileDashboard(
            milestone: MilestoneData,
            sprints: MilestoneData[],
        ): Promise<void> {
            const backlog_items = await getMilestonesBacklog(milestone.id, {
                limit: this.limit,
                offset: this.offset,
            });
            const items_on_sprint = await this.getItemsOfSprints(sprints);

            const trackers_with_number_artifacts: TrackerNumberArtifacts[] = [];

            this.trackers_agile_dashboard.forEach((tracker) => {
                trackers_with_number_artifacts.push({
                    ...tracker,
                    total_artifact: 0,
                });
            });

            backlog_items.forEach((content) => {
                const tracker_with_number_artifacts = trackers_with_number_artifacts.find(
                    (tracker) => tracker.id === content.artifact.tracker.id,
                );

                if (tracker_with_number_artifacts) {
                    tracker_with_number_artifacts.total_artifact++;
                }
            });

            items_on_sprint.forEach((content) => {
                const tracker_with_number_artifacts = trackers_with_number_artifacts.find(
                    (tracker) => tracker.id === content.artifact.tracker.id,
                );

                if (tracker_with_number_artifacts) {
                    tracker_with_number_artifacts.total_artifact++;
                }
            });

            milestone.number_of_artifact_by_trackers = [...trackers_with_number_artifacts];
        },

        async handleErrorMessage(rest_error: unknown): Promise<void> {
            if (!(rest_error instanceof FetchWrapperError) || rest_error.response === undefined) {
                this.setErrorMessage("");
                throw rest_error;
            }
            try {
                const { error } = await rest_error.response.json();
                this.setErrorMessage(error.code + " " + error.message);
            } catch (error) {
                this.setErrorMessage("");
            }
        },

        async getTestManagementCampaigns(
            milestone: MilestoneData,
        ): Promise<TestManagementCampaign> {
            const campaign: TestManagementCampaign = {
                nb_of_notrun: 0,
                nb_of_blocked: 0,
                nb_of_failed: 0,
                nb_of_passed: 0,
            };

            const project_id = milestone.artifact.tracker.project.id;
            if (!project_id) {
                throw new Error("Project id should not be null.");
            }

            const campaigns = await getTTMCampaigns(milestone.id, {
                offset: this.offset,
                limit: this.limit,
                project_id: project_id,
            });

            campaigns.forEach((camp) => {
                campaign.nb_of_blocked += camp.nb_of_blocked;
                campaign.nb_of_notrun += camp.nb_of_notrun;
                campaign.nb_of_failed += camp.nb_of_failed;
                campaign.nb_of_passed += camp.nb_of_passed;
            });

            return campaign;
        },

        setIsLoading(loading: boolean): void {
            this.is_loading = loading;
        },

        setNbPastReleases(total: number): void {
            this.nb_past_releases = total;
        },

        setErrorMessage(error_message: string): void {
            this.error_message = error_message;
        },

        resetErrorMessage(): void {
            this.error_message = null;
        },

        setCurrentMilestones(milestones: MilestoneData[]): void {
            this.current_milestones = milestones;
        },

        setLastRelease(milestone: MilestoneData): void {
            this.last_release = milestone;
        },
    },
    getters: {
        has_rest_error: (state) => state.error_message !== null,
    },
});
