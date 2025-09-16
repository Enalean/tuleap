ARG DOCKER_REGISTRY
FROM ${DOCKER_REGISTRY}/cypress/base:22.19.0@sha256:8af716b7f3b71a05e1e7342f4217ab8599448c514b09743d4620ac6865592073 AS cypress_bin_downloader
ARG CYPRESS_VERSION
RUN apt-get update \
    && apt-get install --yes unzip \
    && apt-get upgrade --yes ca-certificates \
    && wget https://download.cypress.io/desktop/${CYPRESS_VERSION}?platform=linux -O cypress_bin.zip \
    && unzip cypress_bin.zip

FROM ${DOCKER_REGISTRY}/cypress/base:22.19.0@sha256:8af716b7f3b71a05e1e7342f4217ab8599448c514b09743d4620ac6865592073
RUN apt-get update \
    && apt-get install --yes curl \
    && rm -rf /var/lib/apt/lists/*
COPY --from=cypress_bin_downloader /Cypress/ /Cypress/
ENV CYPRESS_RUN_BINARY /Cypress/Cypress
RUN npm install -g junit-report-merger
