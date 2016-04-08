module.exports = function(grunt) {
    grunt.loadNpmTasks('grunt-jasmine-nodejs');

    grunt.initConfig({
        jasmine_nodejs: {
            options: {
                reporters: {
                    console: {
                        //use defaults
                    },
                    junit: {
                        savePath      : './build/reports/jasmine',
                        filePrefix    : 'test-results.xml',
                        consolidateAll: true
                    }
                }
            },
            test: {
                specs: [
                    'spec/**/*.spec.js'
                ]
            }
        }
    });

    grunt.registerTask('test', ['jasmine_nodejs']);
};
