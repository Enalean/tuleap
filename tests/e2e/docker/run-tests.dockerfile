ARG DOCKER_REGISTRY
FROM ${DOCKER_REGISTRY}/cypress/base:24.12.0@sha256:b15406f2ef999e0cae3281cda74f369df1ef00ff779cbfae91a04aea7d6d38c8 AS cypress_bin_downloader
ARG CYPRESS_VERSION
RUN apt-get update \
    && apt-get install --yes unzip \
    && apt-get upgrade --yes ca-certificates \
    && wget https://download.cypress.io/desktop/${CYPRESS_VERSION}?platform=linux -O cypress_bin.zip \
    && unzip cypress_bin.zip

FROM ${DOCKER_REGISTRY}/cypress/base:24.12.0@sha256:b15406f2ef999e0cae3281cda74f369df1ef00ff779cbfae91a04aea7d6d38c8
RUN apt-get update \
    && apt-get install --yes curl \
    && rm -rf /var/lib/apt/lists/*
COPY --from=cypress_bin_downloader /Cypress/ /Cypress/
ENV CYPRESS_RUN_BINARY=/Cypress/Cypress
RUN npm install -g junit-report-merger
