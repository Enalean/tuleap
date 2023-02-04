/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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
const jwt = require("jsonwebtoken");

module.exports = function (private_key) {
    this.privateKey = private_key;

    /**
     * @access public
     *
     * Function to check the token send by
     * a tuleap client
     *
     * @param decoded (Object)
     */
    this.isTokenValid = function(decoded) {
        return _.has(decoded, 'exp')
            && _.has(decoded, 'data.user_id')
            && _.has(decoded, 'data.user_rights');
    };

    /**
     * @access public
     *
     * Function to check the token date
     * @param expiredDate (int): token expired date
     * @returns {boolean}
     */
    this.isDateExpired = function(expired_date) {
        return expired_date <= Math.floor(Date.now() /1000);
    };

    /**
     * @access public
     *
     * Function to verify and decode the token
     *
     * @param token (String)
     * @returns {*}
     */
    this.decodeToken = function(token, callback) {
        var data;
        try {
            data = jwt.verify(token, this.privateKey);
        } catch(err) {
            callback(err);
        }
        return data;
    };
};
