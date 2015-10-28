module.exports = function(grunt) {

    grunt.initConfig({
        jasmine_node: {
            options: {
                forceExit: true,
                match: '.',
                matchall: false,
                extensions: 'js',
                specNameMatcher: 'spec',
                jUnit: {
                    report: true,
                    savePath : "./build/reports/jasmine/",
                    useDotNotation: true,
                    consolidate: true
                }
            },
            all: ['spec/']
        }
    });

    grunt.loadNpmTasks('grunt-jasmine-node-new');

    grunt.registerTask('test', ['jasmine_node']);

};