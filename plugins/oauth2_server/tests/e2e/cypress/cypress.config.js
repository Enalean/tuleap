// eslint-disable-next-line import/no-extraneous-dependencies,no-undef
const { defineConfig } = require("cypress");
// eslint-disable-next-line no-undef
module.exports = defineConfig({
    reporter: "junit",
    reporterOptions: {
        mochaFile: "/output/oauth2-server-results-[hash].xml",
    },
    screenshotsFolder: "/output/oauth2-server-screenshots",
    videosFolder: "/output/oauth2-server-videos",
    video: true,
    videoUploadOnPasses: false,
    pageLoadTimeout: 120000,
    chromeWebSecurity: false,
    retries: 1,
    viewportWidth: 1366,
    viewportHeight: 768,
    e2e: {
        baseUrl: "https://tuleap",
    },
});
