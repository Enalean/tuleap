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
    getNbOfBacklogItems as getBacklogs,
    getNbOfSprints as getSprints,
    getNbOfUpcomingReleases as getReleases
} from "../api/rest-querier.js";

async function getNumberOfBacklogItems(context) {
    context.commit("resetErrorMessage");
    const total = await getBacklogs(context.state);
    return context.commit("setNbBacklogItem", total);
}

async function getNumberOfUpcomingReleases(context) {
    context.commit("resetErrorMessage");
    const total = await getReleases(context.state);
    return context.commit("setNbUpcomingReleases", total);
}

async function getCurrentMilestones(context) {
    context.commit("resetErrorMessage");
    const milestones = await getAllCurrentMilestones(context.state);

    let promises = [];

    milestones.forEach(milestone => {
        promises.push(getInitialEffortOfRelease(context, milestone));
        promises.push(getNumberOfSprints(context, milestone));
    });

    await Promise.all(promises);

    return context.commit("setCurrentMilestones", milestones);
}

export async function getMilestones(context) {
    try {
        context.commit("setIsLoading", true);

        await getNumberOfUpcomingReleases(context);
        await getNumberOfBacklogItems(context);
        await getCurrentMilestones(context);
    } catch (error) {
        await handleErrorMessage(context, error);
    } finally {
        context.commit("setIsLoading", false);
    }
}

async function getNumberOfSprints(context, milestone) {
    context.commit("resetErrorMessage");
    milestone.total_sprint = await getSprints(milestone.id, context.state);
}

async function getInitialEffortOfRelease(context, milestone) {
    context.commit("resetErrorMessage");
    let user_stories = await getContent(milestone.id, context.state);

    milestone.initial_effort = user_stories.reduce((nb_users_stories, user_story) => {
        if (user_story.initial_effort !== null) {
            return nb_users_stories + user_story.initial_effort;
        }
        return nb_users_stories;
    }, 0);
}

export async function handleErrorMessage(context, rest_error) {
    try {
        const { error } = await rest_error.response.json();
        context.commit("setErrorMessage", error.code + " " + error.message);
    } catch (error) {
        context.commit("setErrorMessage", "");
    }
}
