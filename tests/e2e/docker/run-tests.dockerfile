ARG DOCKER_REGISTRY
FROM ${DOCKER_REGISTRY}/cypress/base@sha256:61ef29cc0d4955097c19807055e9d5155c08113d14bdbefdbfb41985f6c28132 AS cypress_bin_downloader
ARG CYPRESS_VERSION
RUN apt-get update \
    && apt-get install --yes unzip \
    && apt-get upgrade --yes ca-certificates \
    && wget https://download.cypress.io/desktop/${CYPRESS_VERSION}?platform=linux -O cypress_bin.zip \
    && unzip cypress_bin.zip

FROM ${DOCKER_REGISTRY}/cypress/base@sha256:61ef29cc0d4955097c19807055e9d5155c08113d14bdbefdbfb41985f6c28132
RUN apt-get update \
    && apt-get install --yes curl \
    && rm -rf /var/lib/apt/lists/*
COPY --from=cypress_bin_downloader /Cypress/ /Cypress/
ENV CYPRESS_RUN_BINARY /Cypress/Cypress
RUN npm install -g junit-report-merger
