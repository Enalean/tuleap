/*
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

module.exports = function(grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON("package.json"),
        sass: {
            dist: {
                options: {
                    sourcemap: "none",
                    style: "compressed",
                    loadPath: ["/tuleap/plugins/botmattermost_git/www/themes/FlamingParrot/css"]
                },
                files: {
                    "www/themes/default/css/style.css": "www/themes/default/css/style.scss",
                    "www/themes/FlamingParrot/css/style.css":
                        "www/themes/FlamingParrot/css/style.scss"
                }
            }
        }
    });

    grunt.loadNpmTasks("grunt-contrib-sass");

    grunt.registerTask("build", ["sass"]);

    grunt.registerTask("default", ["build"]);
};
