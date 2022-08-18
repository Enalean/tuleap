<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\OnlyOffice\Open;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use org\bovigo\vfs\vfsStream;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\PHPUnit\TestCase;

final class OnlyOfficeEditorCrossOriginAssetsControllerTest extends TestCase
{
    private const ASSET_NAME    = 'onlyoffice-editor.abc123.js';
    private const ASSET_CONTENT = 'some_js_content';

    public function testLoadsOnlyOfficeEditorLoaderScripts(): void
    {
        $controller = $this->buildController();

        $request = (new NullServerRequest())->withQueryParams(['name' => '/assets/onlyoffice/assets/' . self::ASSET_NAME]);

        $response = $controller->handle($request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(self::ASSET_CONTENT, $response->getBody()->getContents());
        self::assertEquals('null', $response->getHeaderLine('Access-Control-Allow-Origin'));
        self::assertEquals('application/javascript', $response->getHeaderLine('Content-Type'));
    }

    public function testRejectsUnexpectedAssetRequest(): void
    {
        $controller = $this->buildController();

        $request = (new NullServerRequest())->withQueryParams(['name' => '/assets/onlyoffice/assets/foo.png']);

        $this->expectException(NotFoundException::class);
        $controller->handle($request);
    }

    public function testRejectsRequestThatDoNotAskForAnExistingFile(): void
    {
        $controller = $this->buildController();

        $request = (new NullServerRequest())->withQueryParams(['name' => '/assets/onlyoffice/assets/a.abc.js']);

        $this->expectException(NotFoundException::class);
        $controller->handle($request);
    }

    private function buildController(): OnlyOfficeEditorCrossOriginAssetsController
    {
        $assets_dir = vfsStream::setup()->url();
        file_put_contents($assets_dir . '/' . self::ASSET_NAME, self::ASSET_CONTENT);

        return new OnlyOfficeEditorCrossOriginAssetsController(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $assets_dir,
            $this->createStub(EmitterInterface::class),
        );
    }
}
