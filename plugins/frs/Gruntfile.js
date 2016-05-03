module.exports = function (grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        less: {
            options: {
                paths: ["/tuleap", "../../"]
            },
            "www/themes/default/css/style.css": "www/themes/default/css/style.less",
            "www/themes/FlamingParrot/css/style.css": "www/themes/FlamingParrot/css/style.less"
        }
    });

    grunt.loadNpmTasks('grunt-contrib-less');

    grunt.registerTask('default', ['less']);
};
