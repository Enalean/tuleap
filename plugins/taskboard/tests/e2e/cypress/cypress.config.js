// eslint-disable-next-line import/no-extraneous-dependencies,no-undef
const { defineConfig } = require("cypress");
// eslint-disable-next-line no-undef
module.exports = defineConfig({
    reporter: "junit",
    reporterOptions: {
        mochaFile: "/output/taskboard-results-[hash].xml",
    },
    screenshotsFolder: "/output/taskboard-screenshots",
    videosFolder: "/output/taskboard-videos",
    video: true,
    videoUploadOnPasses: false,
    pageLoadTimeout: 120000,
    retries: 1,
    viewportWidth: 1366,
    viewportHeight: 768,
    e2e: {
        baseUrl: "https://tuleap",
    },
});
