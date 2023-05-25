<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ForumML\OneThread;

use org\bovigo\vfs\vfsStream;
use Project;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\ForumML\ThreadsDao;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\User\UserEmailCollection;

final class MessageInfoToMessagePresenterConvertorTest extends TestCase
{
    use GlobalLanguageMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\UserHelper
     */
    private $user_helper;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ThreadsDao
     */
    private $dao;
    private MessageInfoToMessagePresenterConvertor $convertor;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\PFUser
     */
    private $current_user;

    protected function setUp(): void
    {
        $this->user_helper = $this->createMock(\UserHelper::class);
        $this->dao         = $this->createMock(ThreadsDao::class);

        $this->convertor = new MessageInfoToMessagePresenterConvertor(
            $this->user_helper,
            new TlpRelativeDatePresenterBuilder(),
            $this->dao,
        );

        $this->current_user = $this->createMock(\PFUser::class);
        $this->current_user->method('getPreference')->willReturn('relative_first-absolute_tooltip');
        $this->current_user->method('getLocale')->willReturn('en_US');

        $GLOBALS['Language']
            ->method('getText')
            ->with('system', 'datefmt')
            ->willReturn('d/m/Y H:i');
    }

    public function testRawSenderIfItIsNotPartOfSenderCollection(): void
    {
        $message_info = new MessageInfo(
            2,
            'John Doe <jdoe@example.com>',
            'subject',
            'body',
            'text/plain',
            'text/plain',
            'body',
            new \DateTimeImmutable('@1234567890'),
        );

        $sender_collection = [];

        $project = $this->createMock(Project::class);

        $presenter = $this->convertor->convertToMessagePresenter(
            $message_info,
            new UserEmailCollection(),
            $sender_collection,
            $this->current_user,
            $project,
            10,
            1
        );

        self::assertEquals('John Doe <jdoe@example.com>', $presenter->user_name);
        self::assertEquals('', $presenter->avatar_url);
        self::assertFalse($presenter->has_avatar);
    }

    public function testRawSenderNameIfItIsNotKnownByTuleap(): void
    {
        $message_info = new MessageInfo(
            2,
            'John Doe <jdoe@example.com>',
            'subject',
            'body',
            'text/plain',
            'text/plain',
            'body',
            new \DateTimeImmutable('@1234567890'),
        );

        $sender_collection = [
            'John Doe <jdoe@example.com>' => new Sender(
                'jdoe@example.com',
                'John Doe',
            ),
        ];

        $project = $this->createMock(Project::class);

        $presenter = $this->convertor->convertToMessagePresenter(
            $message_info,
            new UserEmailCollection(),
            $sender_collection,
            $this->current_user,
            $project,
            10,
            1
        );

        self::assertEquals('John Doe', $presenter->user_name);
        self::assertEquals('', $presenter->avatar_url);
        self::assertFalse($presenter->has_avatar);
    }

    public function testSenderIsKnownByTuleap(): void
    {
        $message_info = new MessageInfo(
            2,
            'John Doe <jdoe@example.com>',
            'subject',
            'body',
            'text/plain',
            'text/plain',
            'body',
            new \DateTimeImmutable('@1234567890'),
        );

        $sender_collection = [
            'John Doe <jdoe@example.com>' => new Sender(
                'jdoe@example.com',
                'John Doe',
            ),
        ];

        $project = $this->createMock(Project::class);

        $user = UserTestBuilder::aUser()
            ->withRealName('John Doe')
            ->withEmail('jdoe@example.com')
            ->withAvatarUrl('/path/to/avatar.png')
            ->build();

        $this->user_helper
            ->method('getDisplayNameFromUser')
            ->with($user)
            ->willReturn('John Doe (jdoe)');

        $presenter = $this->convertor->convertToMessagePresenter(
            $message_info,
            new UserEmailCollection($user),
            $sender_collection,
            $this->current_user,
            $project,
            10,
            1
        );

        self::assertEquals('John Doe (jdoe)', $presenter->user_name);
        self::assertEquals('/path/to/avatar.png', $presenter->avatar_url);
        self::assertTrue($presenter->has_avatar);
    }

    public function testBodyIsCachedAsPurifiedHtmlInDatabaseEvenIfItIsAnHeresyButThisIsLegacy(): void
    {
        $message_info = new MessageInfo(
            2,
            'John Doe <jdoe@example.com>',
            'subject',
            '<p>body</p>',
            'text/plain',
            'text/plain',
            null,
            new \DateTimeImmutable('@1234567890'),
        );

        $sender_collection = [];

        $project = ProjectTestBuilder::aProject()->withId(102)->build();

        $this->dao
            ->method('storeCachedHtml')
            ->with(2, '&lt;p&gt;body&lt;/p&gt;');

        $presenter = $this->convertor->convertToMessagePresenter(
            $message_info,
            new UserEmailCollection(),
            $sender_collection,
            $this->current_user,
            $project,
            10,
            1
        );

        self::assertEquals('&lt;p&gt;body&lt;/p&gt;', $presenter->body_html);
    }

    public function testBodyCanBeInHtmlFormat(): void
    {
        $message_info = new MessageInfo(
            2,
            'John Doe <jdoe@example.com>',
            'subject',
            '<p>body</p>',
            'text/html',
            'text/html',
            null,
            new \DateTimeImmutable('@1234567890'),
        );

        $sender_collection = [];

        $project = ProjectTestBuilder::aProject()->withId(102)->build();

        $this->dao
            ->method('storeCachedHtml')
            ->with(2, '<p>body</p>');

        $presenter = $this->convertor->convertToMessagePresenter(
            $message_info,
            new UserEmailCollection(),
            $sender_collection,
            $this->current_user,
            $project,
            10,
            1
        );

        self::assertEquals('<p>body</p>', $presenter->body_html);
    }

    public function testBodyIsTakenFromFirstHtmlAttachmentInCaseOfMultipartAlternative(): void
    {
        $path = vfsStream::setup()->url() . '/toto.html';
        file_put_contents($path, '<html><body><p>multipart body</p></body></html>');

        $message_info = new MessageInfo(
            2,
            'John Doe <jdoe@example.com>',
            'subject',
            'body',
            'multipart/alternative',
            'text/html',
            null,
            new \DateTimeImmutable('@1234567890'),
        );
        $message_info->addAttachment(
            new AttachmentPresenter(
                1,
                'toto.html',
                $path,
                'url'
            )
        );

        $sender_collection = [];

        $project = ProjectTestBuilder::aProject()->withId(102)->build();

        $this->dao
            ->method('storeCachedHtml')
            ->with(2, '<p>multipart body</p>');

        $presenter = $this->convertor->convertToMessagePresenter(
            $message_info,
            new UserEmailCollection(),
            $sender_collection,
            $this->current_user,
            $project,
            10,
            1
        );

        self::assertEquals('<p>multipart body</p>', $presenter->body_html);
    }
}
