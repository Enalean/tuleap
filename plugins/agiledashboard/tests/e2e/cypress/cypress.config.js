// eslint-disable-next-line import/no-extraneous-dependencies,no-undef
const { defineConfig } = require("cypress");
// eslint-disable-next-line no-undef
module.exports = defineConfig({
    reporter: "junit",
    reporterOptions: {
        mochaFile: "/output/agiledashboard-results-[hash].xml",
    },
    screenshotsFolder: "/output/agiledashboard-screenshots",
    videosFolder: "/output/agiledashboard-videos",
    video: true,
    videoUploadOnPasses: false,
    pageLoadTimeout: 120000,
    viewportWidth: 1366,
    viewportHeight: 768,
    e2e: {
        baseUrl: "https://tuleap",
    },
});
