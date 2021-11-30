<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement;

use Tuleap\GlobalResponseMock;
use Tuleap\ProgramManagement\Domain\Redirections\IterationRedirectionParameters;
use Tuleap\ProgramManagement\Tests\Stub\IterationRedirectionParametersStub;

final class RedirectParameterInjectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalResponseMock;

    private const PROGRAM_INCREMENT_ID = '100';

    private mixed $response;
    private RedirectParameterInjector $injector;
    private \Tracker_Artifact_Redirect $redirect;

    protected function setUp(): void
    {
        $this->response = $GLOBALS['Response'];
        $this->injector = new RedirectParameterInjector();
        $this->redirect = new \Tracker_Artifact_Redirect();
    }

    public function testItInjectsAndInformsUserAboutCreatingIncrementIteration(): void
    {
        $this->injector->injectAndInformUserAboutCreatingIncrementIteration(
            $this->redirect,
            $this->response,
            IterationRedirectionParametersStub::withValues(
                IterationRedirectionParameters::REDIRECT_AFTER_CREATE_ACTION,
                self::PROGRAM_INCREMENT_ID
            )
        );

        self::assertEquals(
            [
                'redirect-to-planned-iterations' => 'create',
                'increment-id' => self::PROGRAM_INCREMENT_ID,
                'link-artifact-id' => self::PROGRAM_INCREMENT_ID,
                'link-type' => \Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD,
                'immediate' => 'true'
            ],
            $this->redirect->query_parameters
        );
    }
}
