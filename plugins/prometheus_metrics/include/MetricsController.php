<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\PrometheusMetrics;

use ForgeConfig;
use HTTPRequest;
use Prometheus\RenderTextFormat;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\MessageFactoryBuilder;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class MetricsController implements DispatchableWithRequestNoAuthz
{

    private $config_dir_root;

    public function __construct($config_dir_root)
    {
        $this->config_dir_root = $config_dir_root;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param HTTPRequest $request
     * @param BaseLayout $layout
     * @param array $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        header('Content-type: ' . RenderTextFormat::MIME_TYPE);

        echo $this->getTuleapMetrics();
        echo $this->getTuleapComputedMetrics();
        echo $this->getNodeExporterMetrics();
    }

    private function getTuleapMetrics()
    {
        echo Prometheus::instance()->renderText();
    }

    private function getTuleapComputedMetrics()
    {
        $prometheus = Prometheus::getInMemory();
        $collector  = MetricsCollector::build($prometheus);

        $collector->collect();

        return $prometheus->renderText();
    }

    private function getNodeExporterMetrics()
    {
        try {
            $node_exporter_url = ForgeConfig::get(Prometheus::CONFIG_PROMETHEUS_NODE_EXPORTER, '');
            if ($node_exporter_url === '') {
                return '';
            }
            $request_factory = MessageFactoryBuilder::build();
            $http_client = HttpClientFactory::createClient();
            $request = $request_factory->createRequest('GET', $node_exporter_url);
            $response = $http_client->sendRequest($request);
            return (string) $response->getBody();
        } catch (\Exception $exception) {
            return '';
        }
    }

    /**
     * @param \URLVerification $url_verification
     * @param \HTTPRequest $request
     * @param array $variables
     *
     * @return boolean Whether access is granted or not
     */
    public function userCanAccess(\URLVerification $url_verification, \HTTPRequest $request, array $variables)
    {
        if (! isset($_SERVER['PHP_AUTH_USER']) ||
            $_SERVER['PHP_AUTH_USER'] == '' ||
            ! isset($_SERVER['PHP_AUTH_PW']) ||
            $_SERVER['PHP_AUTH_PW'] == ''
        ) {
            $this->basicAuthenticationChallenge();
        }

        if ($_SERVER['PHP_AUTH_USER'] === 'metrics' && hash_equals($_SERVER['PHP_AUTH_PW'], $this->getSecret())) {
            return true;
        }
        $this->basicAuthenticationChallenge();
    }

    private function basicAuthenticationChallenge()
    {
        header('WWW-Authenticate: Basic realm="'. ForgeConfig::get('sys_name').' /metrics authentication"');
        header('HTTP/1.0 401 Unauthorized');
        exit;
    }

    private function getSecret()
    {
        $path = $this->config_dir_root.'/metrics_secret.key';
        if (! file_exists($path)) {
            throw new \RuntimeException('Configuration not complete. Admin should define a metrics_secret.key');
        }
        $secret = trim(file_get_contents($path));
        if (strlen($secret) < 16) {
            throw new \RuntimeException('Configuration not complete. Secret not strong enough (min len 16)');
        }
        return $secret;
    }
}
