<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\User\Account\Register;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\InviteBuddy\InvitationByTokenRetrieverStub;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\InviteBuddy\InvitationTestBuilder;
use Tuleap\InviteBuddy\PrefixTokenInvitation;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class InvitationToEmailRequestExtractorTest extends TestCase
{
    public function testNullWhenTokenIsNotInTheRequest()
    {
        $extractor = new InvitationToEmailRequestExtractor(
            InvitationByTokenRetrieverStub::withoutMatchingInvitation(),
            new PrefixedSplitTokenSerializer(new PrefixTokenInvitation()),
        );

        self::assertNull(
            $extractor->getInvitationToEmail(
                HTTPRequestBuilder::get()->build(),
            ),
        );
    }

    public function testExceptionWhenTokenIsNotValid()
    {
        $extractor = new InvitationToEmailRequestExtractor(
            InvitationByTokenRetrieverStub::withoutMatchingInvitation(),
            new PrefixedSplitTokenSerializer(new PrefixTokenInvitation()),
        );

        $this->expectException(ForbiddenException::class);

        $extractor->getInvitationToEmail(
            HTTPRequestBuilder::get()->withParam('invitation-token', '123')->build(),
        );
    }

    public function testExceptionWhenInvitationIsNotFound()
    {
        $identifier = new PrefixedSplitTokenSerializer(new PrefixTokenInvitation());
        $extractor  = new InvitationToEmailRequestExtractor(
            InvitationByTokenRetrieverStub::withoutMatchingInvitation(),
            $identifier,
        );

        $this->expectException(ForbiddenException::class);

        $extractor->getInvitationToEmail(
            HTTPRequestBuilder::get()
                ->withParam(
                    'invitation-token',
                    $identifier->getIdentifier(new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString()))->getString(),
                )
                ->build(),
        );
    }

    public function testExceptionWhenInvitationTokenCannotBeVerified()
    {
        $identifier = new PrefixedSplitTokenSerializer(new PrefixTokenInvitation());
        $extractor  = new InvitationToEmailRequestExtractor(
            InvitationByTokenRetrieverStub::withoutValidInvitation(),
            $identifier,
        );

        $this->expectException(ForbiddenException::class);

        $extractor->getInvitationToEmail(
            HTTPRequestBuilder::get()
                ->withParam(
                    'invitation-token',
                    $identifier->getIdentifier(new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString()))->getString(),
                )
                ->build(),
        );
    }

    public function testExceptionWhenInvitationIsTargetToAnAlreadyRegisteredUser()
    {
        $identifier = new PrefixedSplitTokenSerializer(new PrefixTokenInvitation());
        $extractor  = new InvitationToEmailRequestExtractor(
            InvitationByTokenRetrieverStub::withMatchingInvitation(
                InvitationTestBuilder::aSentInvitation(1)
                    ->to(101)
                    ->build(),
            ),
            $identifier,
        );

        $this->expectException(ForbiddenException::class);

        $extractor->getInvitationToEmail(
            HTTPRequestBuilder::get()
                ->withParam(
                    'invitation-token',
                    $identifier->getIdentifier(new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString()))->getString(),
                )
                ->build(),
        );
    }

    public function testReturnInvitationToEmailObject()
    {
        $identifier = new PrefixedSplitTokenSerializer(new PrefixTokenInvitation());
        $extractor  = new InvitationToEmailRequestExtractor(
            InvitationByTokenRetrieverStub::withMatchingInvitation(
                InvitationTestBuilder::aSentInvitation(1)
                    ->to('jdoe@example.com')
                    ->build(),
            ),
            $identifier,
        );

        $invitation_to_email = $extractor->getInvitationToEmail(
            HTTPRequestBuilder::get()
                ->withParam(
                    'invitation-token',
                    $identifier->getIdentifier(new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString()))->getString(),
                )
                ->build(),
        );

        self::assertEquals('jdoe@example.com', $invitation_to_email->to_email);
    }
}
