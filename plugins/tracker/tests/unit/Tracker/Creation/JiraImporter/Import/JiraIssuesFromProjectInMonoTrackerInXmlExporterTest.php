<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import;

use DOMDocument;
use org\bovigo\vfs\vfsStream;
use Psr\Log\NullLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\JiraImport\Project\CreateProjectFromJira;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfiguration;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\LinkedIssuesCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportAllIssuesExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportCreatedRecentlyExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportDefaultCriteriaExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportDoneIssuesExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportOpenIssuesExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportTableExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlReportUpdatedRecentlyExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Reports\XmlTQLReportExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Semantic\SemanticsXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\AppendFieldsFromCreateMetaAPI;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\AppendFieldsFromCreateMetaServer9API;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldAndValueIDGenerator;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraFieldRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraToTuleapFieldTypeMapper;
use Tuleap\Tracker\Creation\JiraImporter\IssueType;
use Tuleap\Tracker\Test\Tracker\Creation\JiraImporter\Import\JiraClientMonoTrackerStub;
use Tuleap\Tracker\TrackerColor;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;
use Tuleap\Tracker\XML\XMLTracker;
use XML_SimpleXMLCDATAFactory;

final class JiraIssuesFromProjectInMonoTrackerInXmlExporterTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testImportFromDebugTraces(): void
    {
        $fixture_path = __DIR__ . '/_fixtures/DI';
        $users        = [
            'user01@example.com' => UserTestBuilder::anActiveUser()->withId(101)->withUserName('yannis')->build(),
            'user02@example.com' => UserTestBuilder::anActiveUser()->withId(102)->withUserName('mag')->build(),
            'user03@example.com' => UserTestBuilder::anActiveUser()->withId(103)->withUserName('manon')->build(),
        ];

        $root = vfsStream::setup();

        \ForgeConfig::set('tmp_dir', $root->url());
        \ForgeConfig::set(\ForgeConfig::FEATURE_FLAG_PREFIX . CreateProjectFromJira::FLAG_JIRA_IMPORT_MONO_TRACKER_MODE, 1);

        $logger = new NullLogger();

        $jira_client = JiraClientMonoTrackerStub::buildJiraCloud($fixture_path);

        $error_collector = new ErrorCollector();

        $cdata_factory = new XML_SimpleXMLCDATAFactory();

        $jira_field_mapper         = new JiraToTuleapFieldTypeMapper($error_collector, $logger);
        $report_table_exporter     = new XmlReportTableExporter();
        $default_criteria_exporter = new XmlReportDefaultCriteriaExporter();

        $tql_report_exporter = new XmlTQLReportExporter(
            $default_criteria_exporter,
            $cdata_factory,
            $report_table_exporter
        );

        $user_manager = $this->createMock(\UserManager::class);

        $forge_user = UserTestBuilder::buildWithId(TrackerImporterUser::ID);

        $user_manager->method('getAndEventuallyCreateUserByEmail')->willReturnCallback(function ($email) use ($users) {
            if (isset($users[$email])) {
                return [$users[$email]];
            }
            throw new \Exception('User email ' . $email . ' is missing in test setup');
        });
        $user_manager->method('getUserById')->willReturnCallback(function ($id) use ($forge_user, $users) {
            if ($id == TrackerImporterUser::ID) {
                return $forge_user;
            }
            foreach ($users as $user) {
                if ($user->getId() == $id) {
                    return $user;
                }
            }
            throw new \Exception('User id ' . $id . ' is missing in test setup');
        });

        if (! $jira_client->isJiraCloud() && $jira_client->isJiraServer9()) {
            $append_fields_from_create = new AppendFieldsFromCreateMetaServer9API($jira_client, $logger);
        } else {
            $append_fields_from_create = new AppendFieldsFromCreateMetaAPI($jira_client, $logger);
        }

        $exporter = new JiraIssuesFromProjectInMonoTrackerInXmlExporter(
            $logger,
            $jira_client,
            $error_collector,
            new JiraFieldRetriever($jira_client, $logger, $append_fields_from_create),
            $jira_field_mapper,
            new XmlReportExporter(),
            new SemanticsXMLExporter(),
            new AlwaysThereFieldsExporter(),
            new XmlReportAllIssuesExporter(
                $default_criteria_exporter,
                $report_table_exporter
            ),
            new XmlReportOpenIssuesExporter(
                $default_criteria_exporter,
                $report_table_exporter,
            ),
            new XmlReportDoneIssuesExporter(
                $default_criteria_exporter,
                $report_table_exporter,
            ),
            new XmlReportCreatedRecentlyExporter($tql_report_exporter),
            new XmlReportUpdatedRecentlyExporter($tql_report_exporter),
        );

        $platform_configuration_collection = new PlatformConfiguration();

        $tracker_for_export = (new XMLTracker('T1', 'Issues'))
            ->withName('issues')
            ->withDescription('Issues')
            ->withColor(TrackerColor::default());

        $tracker_xml = $exporter->exportIssuesToXml(
            $platform_configuration_collection,
            $tracker_for_export,
            $jira_client->getJiraProject(),
            [
                new IssueType("10002", 'Task', false),
                new IssueType("10003", 'Sub-task', true),
                new IssueType("10001", 'Story', false),
                new IssueType("10004", 'Bug', false),
                new IssueType("10000", 'EpicEN', false),
            ],
            new FieldAndValueIDGenerator(),
            new LinkedIssuesCollection(),
        );

        // Uncomment below to update the fixture
        // file_put_contents($fixture_path . '/mono_tracker.xml', self::getTidyXML($tracker_xml));

        self::assertStringEqualsFile($fixture_path . '/mono_tracker.xml', self::getTidyXML($tracker_xml));

        $dom         = new DOMDocument("1.0", "UTF-8");
        $dom_element = $dom->importNode(dom_import_simplexml($tracker_xml), true);
        $dom->appendChild($dom_element);
        self::assertTrue($dom->relaxNGValidateSource(\file_get_contents(__DIR__ . '/../../../../../../resources/tracker.rng')));
    }

    private static function getTidyXML(\SimpleXMLElement $xml): string
    {
        $domxml                     = new DOMDocument('1.0');
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput       = true;
        $domxml->loadXML($xml->asXML());
        return $domxml->saveXML();
    }
}
