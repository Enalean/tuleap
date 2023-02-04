/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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

const _ = require("lodash");

/**
 * @access public
 *
 * Function to verify if content message sent
 * a presence for an artifact
 *
 * @param data (Object)
 * @returns {boolean}
 */
function hasPresencesOnExecutions(data) {
    return _.has(data, 'presence.execution_id')
        && _.has(data, 'presence.remove_from')
        && _.has(data, 'presence.uuid')
        && _.has(data, 'presence.user');
}

/**
 * @access public
 *
 * Function to verify if content message sent
 * is about deleting an execution
 *
 * @param data (Object)
 * @returns {boolean}
 */
function isExecutionDeleted(message) {
    return message.cmd === 'testmanagement_execution:delete'
        && _.has(message.data, 'artifact_id');
}

/**
 * @access public
 *
 * Function to verify if content message sent
 * a new status for an artifact
 *
 * @param data (Object)
 * @returns {boolean}
 */
function hasChangeStatusOnExecutions(data) {
    return _.has(data, 'status')
        && _.has(data, 'previous_status')
        && _.has(data, 'user')
        && _.has(data, 'previous_user');
}

module.exports = {
    hasPresencesOnExecutions   : hasPresencesOnExecutions,
    isExecutionDeleted         : isExecutionDeleted,
    hasChangeStatusOnExecutions: hasChangeStatusOnExecutions
};
