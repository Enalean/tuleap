import { defineConfig } from "cypress";

export default defineConfig({
    e2e: {
        baseUrl: "https://reverse-proxy",
    },
    reporter: "junit",
    reporterOptions: {
        mochaFile: "/output/results-[hash].xml",
    },
    screenshotsFolder: "/output/screenshots",
    videosFolder: "/output/videos",
    video: false,
    retries: 1,
});
