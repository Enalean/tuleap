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
 */

namespace Tuleap\Docman\Upload;

use GuzzleHttp\Psr7\ServerRequest;
use HTTPRequest;
use Tuleap\Docman\Tus\TusServer;
use Tuleap\Http\MessageFactoryBuilder;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\REST\BasicAuthentication;
use Tuleap\REST\UserManager;

class FileUploadController implements DispatchableWithRequestNoAuthz
{
    const ONE_MB_FILE = 1048576;
    /**
     * @var string
     */
    private $root_path_storage;
    /**
     * @var UserManager
     */
    private $rest_user_manager;
    /**
     * @var BasicAuthentication
     */
    private $basic_rest_authentication;

    public function __construct(
        $root_path_storage,
        UserManager $rest_user_manager,
        BasicAuthentication $basic_rest_authentication
    ) {
        $this->root_path_storage         = $root_path_storage;
        $this->rest_user_manager         = $rest_user_manager;
        $this->basic_rest_authentication = $basic_rest_authentication;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $test_file = $this->root_path_storage . '/test_upload';
        if (! file_exists($test_file)) {
            touch($test_file);
        }
        $handle = fopen($test_file, 'ab');

        $document = new DocumentToUpload($handle, self::ONE_MB_FILE, filesize($test_file));

        $tus_server = new TusServer(MessageFactoryBuilder::build());
        $response   = $tus_server->serve(ServerRequest::fromGlobals(), $document);

        fclose($handle);

        http_response_code($response->getStatusCode());
        foreach ($response->getHeaders() as $header => $values) {
            foreach ($values as $value) {
                header("$header: $value", false);
            }
        }
        echo $response->getBody();
    }

    public function userCanAccess(\URLVerification $url_verification, \HTTPRequest $request, array $variables)
    {
        $this->basic_rest_authentication->__isAllowed();
        $current_user = $this->rest_user_manager->getCurrentUser();

        return ! $current_user->isAnonymous();
    }
}
