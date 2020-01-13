/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import {
    getCurrentMilestones as getAllCurrentMilestones,
    getMilestonesContent as getContent,
    getNbOfSprints
} from "../api/rest-querier";

import { Context, MilestoneContent, MilestoneData, TrackerNumberArtifacts } from "../type";
import { FetchWrapperError } from "tlp";

async function getCurrentMilestones(context: Context): Promise<void> {
    context.commit("resetErrorMessage");
    let milestones: MilestoneData[] = [];
    const project_id = context.state.project_id;
    if (project_id !== null) {
        milestones = await getAllCurrentMilestones({
            project_id,
            limit: context.state.limit,
            offset: context.state.offset
        });
    }
    return context.commit("setCurrentMilestones", milestones);
}

export function getEnhancedMilestones(
    context: Context,
    milestone: MilestoneData
): Promise<MilestoneData> {
    const milestone_data = async (): Promise<MilestoneData> => {
        await getInitialEffortAndNumberArtifactsInTrackers(context, milestone);
        return {
            ...milestone,
            total_sprint: await getNbOfSprints(milestone.id, context.state)
        };
    };
    return milestone_data();
}

export async function getMilestones(context: Context): Promise<void> {
    try {
        context.commit("setIsLoading", true);
        await getCurrentMilestones(context);
    } catch (error) {
        await handleErrorMessage(context, error);
    } finally {
        context.commit("setIsLoading", false);
    }
}

async function getInitialEffortAndNumberArtifactsInTrackers(
    context: Context,
    milestone: MilestoneData
): Promise<void> {
    const milestone_contents = await getContent(milestone.id, context.state);
    getInitialEffortOfRelease(context, milestone, milestone_contents);
    getNumberArtifactsInTrackerOfAgileDashboard(context, milestone, milestone_contents);
}

function getInitialEffortOfRelease(
    context: Context,
    milestone: MilestoneData,
    milestone_contents: MilestoneContent[]
): void {
    milestone.initial_effort = milestone_contents.reduce(
        (nb_users_stories: number, milestone_content: MilestoneContent) => {
            if (milestone_content.initial_effort !== null) {
                return nb_users_stories + milestone_content.initial_effort;
            }
            return nb_users_stories;
        },
        0
    );
}

function getNumberArtifactsInTrackerOfAgileDashboard(
    context: Context,
    milestone: MilestoneData,
    milestone_contents: MilestoneContent[]
): void {
    const trackers_with_number_artifacts: TrackerNumberArtifacts[] = [];

    context.state.trackers_agile_dashboard.forEach(tracker => {
        trackers_with_number_artifacts.push({
            ...tracker,
            total_artifact: 0
        });
    });

    milestone_contents.forEach(content => {
        const tracker_with_number_artifacts = trackers_with_number_artifacts.find(
            tracker => tracker.id === content.artifact.tracker.id
        );

        if (tracker_with_number_artifacts) {
            tracker_with_number_artifacts.total_artifact++;
        }
    });

    milestone.number_of_artifact_by_trackers = [...trackers_with_number_artifacts];
}

export async function handleErrorMessage(
    context: Context,
    rest_error: FetchWrapperError
): Promise<void> {
    if (rest_error.response === undefined) {
        context.commit("setErrorMessage", "");
        throw rest_error;
    }
    try {
        const { error } = await rest_error.response.json();
        context.commit("setErrorMessage", error.code + " " + error.message);
    } catch (error) {
        context.commit("setErrorMessage", "");
    }
}
