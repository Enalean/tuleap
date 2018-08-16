/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

export { get, patch, put, post, recursiveGet, options };

async function get(input, init = {}) {
    const method = "GET";
    const { credentials = "same-origin", params } = init;

    let url = input;
    if (params) {
        url += encodeAllParamsToURI(params);
    }

    const response = await fetch(url, {
        method,
        credentials,
        ...init
    });
    return checkResponse(response);
}

const encodeAllParamsToURI = params => {
    let url_params = "";
    const [first_param, ...other_params] = Object.entries(params);

    url_params += "?" + encodeParamToURI(first_param);

    for (const param of other_params) {
        url_params += "&" + encodeParamToURI(param);
    }

    return url_params;
};

const encodeParamToURI = ([key, value]) => {
    return encodeURIComponent(key) + "=" + encodeURIComponent(value);
};

async function recursiveGet(input, init = {}) {
    const { params, getCollectionCallback = json => [].concat(json) } = init;

    const { limit = 100, offset = 0 } = params;

    const response = await get(input, {
        ...init,
        params: {
            ...params,
            limit,
            offset
        }
    });
    const json = await response.json();
    const results = getCollectionCallback(json);

    const total = Number.parseInt(response.headers.get("X-PAGINATION-SIZE"), 10);
    const new_offset = offset + limit;

    if (new_offset >= total) {
        return results;
    }

    const new_init = {
        ...init,
        params: {
            ...params,
            offset: new_offset
        }
    };

    const second_response = await recursiveGet(input, new_init);
    return results.concat(second_response);
}

function put(input, init = {}) {
    const method = "PUT",
        { credentials = "same-origin" } = init;

    return fetch(input, { method, credentials, ...init }).then(checkResponse);
}

function patch(input, init = {}) {
    const method = "PATCH",
        { credentials = "same-origin" } = init;

    return fetch(input, { method, credentials, ...init }).then(checkResponse);
}

function post(input, init = {}) {
    const method = "POST",
        { credentials = "same-origin" } = init;

    return fetch(input, { method, credentials, ...init }).then(checkResponse);
}

function options(input, init = {}) {
    const method = "OPTIONS",
        { credentials = "same-origin" } = init;

    return fetch(input, { method, credentials, ...init }).then(checkResponse);
}

function checkResponse(response) {
    if (response.ok) {
        return response;
    }

    const error = new Error(response.statusText);
    error.response = response;
    throw error;
}
