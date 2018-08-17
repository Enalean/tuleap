module.exports = function(grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON("package.json"),
        sass: {
            dist: {
                options: {
                    sourcemap: "none",
                    style: "compressed",
                    loadPath: ["/tuleap/plugins/botmattermost/www/themes/BurningParrot/css"]
                },
                files: {
                    "www/themes/BurningParrot/css/style-blue.css":
                        "www/themes/BurningParrot/css/style-blue.scss",
                    "www/themes/BurningParrot/css/style-green.css":
                        "www/themes/BurningParrot/css/style-green.scss",
                    "www/themes/BurningParrot/css/style-grey.css":
                        "www/themes/BurningParrot/css/style-grey.scss",
                    "www/themes/BurningParrot/css/style-orange.css":
                        "www/themes/BurningParrot/css/style-orange.scss",
                    "www/themes/BurningParrot/css/style-purple.css":
                        "www/themes/BurningParrot/css/style-purple.scss",
                    "www/themes/BurningParrot/css/style-red.css":
                        "www/themes/BurningParrot/css/style-red.scss"
                }
            }
        }
    });

    grunt.loadNpmTasks("grunt-contrib-sass");

    grunt.registerTask("build", ["sass"]);

    grunt.registerTask("default", ["build"]);
};
