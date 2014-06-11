module.exports = function(config){
  config.set({

    basePath : '../',

    files : [
      'angular.js',
      'angular-resource.js',
      'angular-ui-tree.js',
      'ng-infinite-scroll.min.js',
      'angular-mocks.js',
      'app/submilestone/submilestone-service.js',
      'app/submilestone/submilestone-controller.js',
      'app/submilestone/submilestone.js',
      'app/**/*.js',
      'test/unit/**/*.js'
    ],

    autoWatch : true,

    frameworks: ['jasmine'],

    browsers : ['Chrome'],

    plugins : [
            'karma-chrome-launcher',
            'karma-firefox-launcher',
            'karma-jasmine'
            ],

    junitReporter : {
      outputFile: 'test_out/unit.xml',
      suite: 'unit'
    }

  });
};
