<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact;

use DateTimeImmutable;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_XMLImport;
use Tracker_FormElement_Field_String;
use Tracker_FormElementFactory;
use TrackerXmlFieldsMapping_FromAnotherPlatform;
use Tuleap\DB\DBConnection;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\XMLImport\TrackerPrivateCommentUGroupExtractor;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValuesContainer;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Artifact\XMLImport\MoveImportConfig;
use Tuleap\Tracker\Artifact\XMLImport\TrackerImportConfig;
use Tuleap\Tracker\Artifact\XMLImport\TrackerXmlImportConfig;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueTextTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use UserManager;
use Workflow;
use XML_RNGValidator;
use XMLImportHelper;

#[DisableReturnValueGenerationForTestDoubles]
final class XmlImportTest extends TestCase
{
    private const SUMMARY_FIELD_ID = 50;
    private const TRACKER_ID       = 100;

    private TrackerXmlImportConfig $import_config;
    private string $extraction_path;
    private PFUser $john_doe;
    private Tracker $tracker;
    private TrackerArtifactCreator&MockObject $artifact_creator;
    private NewChangesetCreator&MockObject $new_changeset_creator;
    private Tracker_FormElementFactory&MockObject $formelement_factory;
    private Tracker_Artifact_XMLImport $importer;
    private TrackerXmlFieldsMapping_FromAnotherPlatform $xml_mapping;
    private Tracker_FormElement_Field_String $tracker_formelement_field_string;
    private CreatedFileURLMapping $url_mapping;
    private ExternalFieldsExtractor&MockObject $external_field_extractor;
    private TrackerPrivateCommentUGroupExtractor&MockObject $private_comment_extractor;

    public function setUp(): void
    {
        $workflow = $this->createStub(Workflow::class);
        $workflow->method('disable');
        $this->tracker = TrackerTestBuilder::aTracker()->withId(self::TRACKER_ID)->withWorkflow($workflow)->build();

        $this->artifact_creator      = $this->createMock(TrackerArtifactCreator::class);
        $this->new_changeset_creator = $this->createMock(NewChangesetCreator::class);
        $this->formelement_factory   = $this->createMock(Tracker_FormElementFactory::class);
        $this->xml_mapping           = new TrackerXmlFieldsMapping_FromAnotherPlatform([]);
        $this->url_mapping           = new CreatedFileURLMapping();

        $this->tracker_formelement_field_string = $this->createMock(Tracker_FormElement_Field_String::class);
        $this->tracker_formelement_field_string->method('setTracker');
        $this->tracker_formelement_field_string->method('getName')->willReturn('summary');
        $this->tracker_formelement_field_string->method('getId')->willReturn(self::SUMMARY_FIELD_ID);
        $this->tracker_formelement_field_string->method('getTrackerId')->willReturn(self::TRACKER_ID);
        $this->tracker_formelement_field_string->method('getLabel')->willReturn('summary');
        $this->tracker_formelement_field_string->method('validateField')->willReturn(true);

        $this->john_doe = UserTestBuilder::aUser()->withId(200)->build();

        $user_manager = $this->createMock(UserManager::class);
        $user_manager->method('getUserByIdentifier')->with('john_doe')->willReturn($this->john_doe);
        $user_manager->method('getUserAnonymous')->willReturn(UserTestBuilder::anAnonymousUser()->build());

        $this->extraction_path = $this->getTmpDir();

        $rng_validator = $this->createMock(XML_RNGValidator::class);
        $rng_validator->method('validate');

        $this->import_config = new TrackerXmlImportConfig($this->john_doe, new DateTimeImmutable(), MoveImportConfig::buildForRegularImport(), false);

        $this->external_field_extractor = $this->createMock(ExternalFieldsExtractor::class);

        $db_connection = $this->createMock(DBConnection::class);
        $db_connection->method('reconnectAfterALongRunningProcess');

        $this->private_comment_extractor = $this->createMock(TrackerPrivateCommentUGroupExtractor::class);

        $this->importer = new Tracker_Artifact_XMLImport(
            $rng_validator,
            $this->artifact_creator,
            $this->new_changeset_creator,
            $this->formelement_factory,
            new XMLImportHelper($user_manager),
            $this->createStub(BindStaticValueDao::class),
            new NullLogger(),
            false,
            $this->createStub(TypeDao::class),
            $this->external_field_extractor,
            $this->private_comment_extractor,
            $db_connection,
        );
    }

    public function testImportChangesetInNewArtifactWithNoChangeSet(): void
    {
        $changeset_1 = $this->buildAChangeset($this->john_doe->getId(), strtotime('2014-01-15T10:38:06+01:00'), 'OK', 0);
        $changeset_2 = $this->buildAChangeset($this->john_doe->getId(), strtotime('2014-01-15T10:38:06+01:00'), 'Again', 1);
        $changeset_3 = $this->buildAChangeset($this->john_doe->getId(), strtotime('2014-01-15T10:38:06+01:00'), 'Value', 2);

        $artifact = ArtifactTestBuilder::anArtifact(101)->inTracker($this->tracker)->build();

        $xml_field_mapping = file_get_contents(__DIR__ . '/_fixtures/testImportChangesetInNewArtifact.xml');
        $xml_input         = simplexml_load_string($xml_field_mapping);

        $data = [
            self::SUMMARY_FIELD_ID => 'OK',
        ];

        $this->artifact_creator
            ->expects(self::once())
            ->method('createFirstChangeset')
            ->willReturnCallback(
                fn(
                    Artifact $arg_artifact,
                    InitialChangesetValuesContainer $changeset_values,
                    PFUser $user,
                    int $submitted_on,
                    bool $send_notification,
                    CreatedFileURLMapping $url_mapping,
                    TrackerImportConfig $tracker_import_config,
                ): ?Tracker_Artifact_Changeset => match (true) {
                    $arg_artifact === $artifact &&
                    $changeset_values->getFieldsData() === $data &&
                    $user === $this->john_doe &&
                    $tracker_import_config === $this->import_config &&
                    $send_notification === false
                    => $changeset_1
                }
            );

        $this->new_changeset_creator->expects(self::exactly(2))->method('create')
            ->with(
                self::callback(function (NewChangeset $new_changeset) use ($artifact) {
                    if ($new_changeset->getArtifact() !== $artifact) {
                        return false;
                    }
                    $first  = [self::SUMMARY_FIELD_ID => 'Again'];
                    $second = [self::SUMMARY_FIELD_ID => 'Value'];
                    if ($new_changeset->getFieldsData() !== $first && $new_changeset->getFieldsData() !== $second) {
                        return false;
                    }
                    if ($new_changeset->getSubmitter() !== $this->john_doe) {
                        return false;
                    }
                    if ($new_changeset->getUrlMapping() !== $this->url_mapping) {
                        return false;
                    }
                    return true;
                }),
                self::callback(function (PostCreationContext $context) {
                    if ($context->getImportConfig() !== $this->import_config) {
                        return false;
                    }
                    if ($context->shouldSendNotifications() !== false) {
                        return false;
                    }
                    return true;
                }),
            )
            ->willReturnOnConsecutiveCalls($changeset_2, $changeset_3);

        $this->artifact_creator->expects(self::once())->method('createBare')->willReturn($artifact);

        $this->formelement_factory->method('getUsedFieldByName')->with(self::TRACKER_ID, 'summary')->willReturn($this->tracker_formelement_field_string);
        $this->formelement_factory->method('getFormElementByName')->willReturn([]);

        $this->external_field_extractor->expects(self::once())->method('extractExternalFieldsFromArtifact');

        $this->importer->importFromXML(
            $this->tracker,
            $xml_input,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->import_config
        );
    }

    public function testImportChangesetWithPrivateCommentAndUpdateCommentInNewArtifact(): void
    {
        $changeset_1 = $this->buildAChangeset($this->john_doe->getId(), strtotime('2014-01-15T10:38:06+01:00'), 'OK', 0);
        $changeset_2 = $this->buildAChangeset($this->john_doe->getId(), strtotime('2014-01-15T10:38:06+01:00'), 'Again', 1);

        $artifact              = ArtifactTestBuilder::anArtifact(101)->inTracker($this->tracker)->build();
        $changeset_2->artifact = $artifact;

        $xml_field_mapping = file_get_contents(__DIR__ . '/_fixtures/testImportChangesetWithPrivateCommentInNewArtifact.xml');
        $xml_input         = simplexml_load_string($xml_field_mapping);

        $this->artifact_creator
            ->expects(self::once())
            ->method('createFirstChangeset')
            ->with(
                $artifact,
                self::callback(static fn(InitialChangesetValuesContainer $changeset_values) => $changeset_values->getFieldsData() === [self::SUMMARY_FIELD_ID => 'OK']),
                $this->john_doe,
                self::anything(),
                false,
                self::anything(),
                $this->import_config,
            )
            ->willReturn($changeset_1);

        $ugroup_2 = ProjectUGroupTestBuilder::aCustomUserGroup(1)->withName('my_group')->build();
        $ugroup_3 = ProjectUGroupTestBuilder::aCustomUserGroup(2)->withName('my_other_group')->build();

        $this->private_comment_extractor->expects(self::exactly(2))->method('extractUGroupsFromXML')
            ->with(
                $artifact,
                self::callback(function (SimpleXMLElement $comment): bool {
                    return (string) $comment->body === 'My First Comment' &&
                           (string) $comment->private_ugroups->ugroup[0] === 'my_group'
                           || ((string) $comment->body === 'My Second Comment' &&
                               (string) $comment->private_ugroups->ugroup[0] === 'my_other_group');
                }),
            )
            ->willReturnOnConsecutiveCalls([$ugroup_2], [$ugroup_3]);

        $this->new_changeset_creator->expects(self::once())->method('create')
            ->with(
                self::callback(function (NewChangeset $new_changeset) use ($artifact, $ugroup_2) {
                    if ($new_changeset->getArtifact() !== $artifact) {
                        return false;
                    }
                    $first = [self::SUMMARY_FIELD_ID => 'Again'];
                    if ($new_changeset->getFieldsData() !== $first) {
                        return false;
                    }
                    if ($new_changeset->getSubmitter() !== $this->john_doe) {
                        return false;
                    }
                    if ($new_changeset->getUrlMapping() !== $this->url_mapping) {
                        return false;
                    }
                    $comment = $new_changeset->getComment();
                    if ($comment->getUserGroupsThatAreAllowedToSee() !== [$ugroup_2]) {
                        return false;
                    }
                    return true;
                }),
                self::callback(function (PostCreationContext $context) {
                    if ($context->getImportConfig() !== $this->import_config) {
                        return false;
                    }
                    if ($context->shouldSendNotifications() !== false) {
                        return false;
                    }
                    return true;
                })
            )
            ->willReturn($changeset_2);

        $this->artifact_creator->expects(self::once())->method('createBare')->willReturn($artifact);

        $this->formelement_factory->method('getUsedFieldByName')->with(self::TRACKER_ID, 'summary')->willReturn($this->tracker_formelement_field_string);
        $this->formelement_factory->method('getFormElementByName')->willReturn([]);

        $this->external_field_extractor->expects(self::once())->method('extractExternalFieldsFromArtifact');

        $this->importer->importFromXML(
            $this->tracker,
            $xml_input,
            $this->extraction_path,
            $this->xml_mapping,
            $this->url_mapping,
            $this->import_config
        );
    }

    private function getTmpDir(): string
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('tuleap_tests'));

        return vfsStream::url('tuleap_tests');
    }

    private function buildAChangeset(
        int $submitted_by,
        int $submitted_on,
        string $changeset_field_value,
        int $changeset_id,
    ): Tracker_Artifact_Changeset {
        $tracker   = TrackerTestBuilder::aTracker()->withId(self::TRACKER_ID)->build();
        $changeset = ChangesetTestBuilder::aChangeset($changeset_id)
            ->submittedOn($submitted_on)
            ->submittedBy($submitted_by)
            ->ofArtifact(ArtifactTestBuilder::anArtifact(146554)->inTracker($tracker)->build())
            ->build();

        $field = StringFieldBuilder::aStringField(48641)->withName('summary')->build();
        $changeset->setFieldValue(
            $field,
            ChangesetValueTextTestBuilder::aValue(1453, $changeset, $field)->withValue($changeset_field_value)->build()
        );

        return $changeset;
    }
}
