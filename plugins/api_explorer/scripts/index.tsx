/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import SwaggerUI from "swagger-ui";
import React from "react";
import "../themes/style.scss";

interface TuleapLayoutProps {
    errSelectors: {
        lastError(): {
            get(name: string): string;
        } | null;
    };
    specSelectors: {
        specStr(): string;
        loadingStatus(): string;
    };
    getComponent(name: string, bool?: boolean): React.ComponentType;
}

class TuleapLayout extends React.Component<TuleapLayoutProps> {
    override render() {
        const { errSelectors, specSelectors, getComponent } = this.props;

        const SvgAssets = getComponent("SvgAssets");
        const Operations = getComponent("operations", true);
        const Row = getComponent("Row");
        const Col = getComponent("Col");
        const Errors = getComponent("errors", true);

        const isSpecEmpty = !specSelectors.specStr();

        const loadingStatus = specSelectors.loadingStatus();

        let loadingMessage = null;

        if (loadingStatus === "loading") {
            loadingMessage = (
                <div className="info">
                    <div className="loading-container">
                        <div className="loading"></div>
                    </div>
                </div>
            );
        }

        if (loadingStatus === "failed") {
            loadingMessage = (
                <div className="info">
                    <div className="loading-container">
                        <h4 className="title">Failed to load API definition.</h4>
                        <Errors />
                    </div>
                </div>
            );
        }

        if (loadingStatus === "failedConfig") {
            const lastErr = errSelectors.lastError();
            const lastErrMsg = lastErr ? lastErr.get("message") : "";
            loadingMessage = (
                <div
                    className="info"
                    style={{
                        maxWidth: "880px",
                        marginLeft: "auto",
                        marginRight: "auto",
                        textAlign: "center",
                    }}
                >
                    <div className="loading-container">
                        <h4 className="title">Failed to load remote configuration.</h4>
                        <p>{lastErrMsg}</p>
                    </div>
                </div>
            );
        }

        if (!loadingMessage && isSpecEmpty) {
            loadingMessage = <h4>No API definition provided.</h4>;
        }

        if (loadingMessage) {
            return (
                <div className="swagger-ui">
                    <div className="loading-container">{loadingMessage}</div>
                </div>
            );
        }

        return (
            <div className="swagger-ui">
                <SvgAssets />
                <Row>
                    <Col>
                        <Operations />
                    </Col>
                </Row>
            </div>
        );
    }
}

const TuleapLayoutPlugin = () => {
    return {
        components: {
            TuleapLayout: TuleapLayout,
        },
    };
};

SwaggerUI({
    dom_id: "#api-explorer",
    url: "/api/explorer/swagger.json",
    docExpansion: "none",
    tagsSorter: "alpha",
    operationsSorter: "alpha",
    validatorUrl: null,
    deepLinking: true,
    plugins: [TuleapLayoutPlugin],
    layout: "TuleapLayout",
});
