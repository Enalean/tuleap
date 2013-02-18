/**
 * Copyright (c) Anhang Zhu, 2011. All rights reserved
 * http://blog.anhangzhu.com/2011/07/20/html-5-local-storage-with-expiration/
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

var AZHU = { }
AZHU.storage = {
  save: function(key, jsonData, expirationSec){
    var expirationMS = expirationSec * 1000;
    var record = {value: JSON.stringify(jsonData), timestamp: new Date().getTime() + expirationMS}
    localStorage.setItem(key, JSON.stringify(record));
    return jsonData;
  },
  load: function(key){
    var record = JSON.parse(localStorage.getItem(key));
    if (!record){return false;}
    return (new Date().getTime() < record.timestamp && JSON.parse(record.value));
  }
}
