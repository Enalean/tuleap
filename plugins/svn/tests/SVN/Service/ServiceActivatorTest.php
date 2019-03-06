<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

namespace Tuleap\SVN\Service;

use Service;
use SvnPlugin;
use TuleapTestCase;

require_once __DIR__ .'/../../bootstrap.php';

class ServiceActivatorTest extends TuleapTestCase
{
    /**
     * @var ServiceActivator
     */
    private $activator;

    public function setUp()
    {
        parent::setUp();

        $this->service_manager = mock('ServiceManager');
        $this->service_creator = mock('Tuleap\Service\ServiceCreator');
        $this->activator       = new ServiceActivator($this->service_manager, $this->service_creator);

        $this->template = aMockProject()->withId(101)->build();
        $this->data     = mock('ProjectCreationData');

        $this->params = array(
            'template'              => $this->template,
            'project_creation_data' => $this->data
        );

        $this->svn_core_service   = stub('Service')->getId()->returns(101);
        $this->svn_plugin_service = stub('Service')->getId()->returns(106);

        stub($this->svn_core_service)->getShortName()->returns('svn');
        stub($this->svn_plugin_service)->getShortName()->returns('plugin_svn');
    }

    public function itActivatesPluginInsteadOfLegacyService()
    {
        stub($this->service_manager)->getListOfAllowedServicesForProject($this->template)->returns(
            array($this->svn_core_service, $this->svn_plugin_service)
        );

        stub($this->svn_core_service)->isUsed()->returns(true);
        stub($this->svn_plugin_service)->isUsed()->returns(false);
        stub($this->data)->projectShouldInheritFromTemplate()->returns(true);

        expect($this->data)->unsetProjectServiceUsage(101)->once();
        expect($this->data)->forceServiceUsage(106)->once();

        $this->activator->unuseLegacyService($this->params);
    }

    public function itActivatesOnlyPluginWhenBothServicesAreActivatedIntoTemplate()
    {
        stub($this->service_manager)->getListOfAllowedServicesForProject($this->template)->returns(
            array($this->svn_core_service, $this->svn_plugin_service)
        );

        stub($this->svn_core_service)->isUsed()->returns(true);
        stub($this->svn_plugin_service)->isUsed()->returns(true);
        stub($this->data)->projectShouldInheritFromTemplate()->returns(true);

        expect($this->data)->unsetProjectServiceUsage(101)->once();
        expect($this->data)->forceServiceUsage(106)->once();

        $this->activator->unuseLegacyService($this->params);
    }

    public function itUnsetsSVNCoreEvenIfItsTheOnlySVNServiceInTemplate()
    {
        stub($this->service_manager)->getListOfAllowedServicesForProject($this->template)->returns(
            array($this->svn_core_service)
        );

        stub($this->data)->projectShouldInheritFromTemplate()->returns(true);

        expect($this->data)->unsetProjectServiceUsage(101)->once();
        expect($this->data)->forceServiceUsage()->never();

        $this->activator->unuseLegacyService($this->params);
    }

    public function itDoesNothingIfSVNPluginIsTheOnlySVNServiceInTemplate()
    {
        stub($this->service_manager)->getListOfAllowedServicesForProject($this->template)->returns(
            array($this->svn_plugin_service)
        );

        stub($this->data)->projectShouldInheritFromTemplate()->returns(true);

        expect($this->data)->unsetProjectServiceUsage()->never();
        expect($this->data)->forceServiceUsage()->never();

        $this->activator->unuseLegacyService($this->params);
    }

    public function itDoesNotActivatePluginIfBothSVNServicesAreNotActivatedIntoTemplate()
    {
        stub($this->service_manager)->getListOfAllowedServicesForProject($this->template)->returns(
            array($this->svn_core_service, $this->svn_plugin_service)
        );

        stub($this->svn_core_service)->isUsed()->returns(false);
        stub($this->svn_plugin_service)->isUsed()->returns(false);
        stub($this->data)->projectShouldInheritFromTemplate()->returns(true);

        expect($this->data)->unsetProjectServiceUsage(101)->once();
        expect($this->data)->forceServiceUsage()->never();

        $this->activator->unuseLegacyService($this->params);
    }

    public function itDoesNothingIfServicesAreNotInheritedFromTemplate()
    {
        stub($this->service_manager)->getListOfAllowedServicesForProject($this->template)->returns(
            array($this->svn_core_service, $this->svn_plugin_service)
        );

        stub($this->svn_core_service)->isUsed()->returns(false);
        stub($this->svn_plugin_service)->isUsed()->returns(false);
        stub($this->data)->projectShouldInheritFromTemplate()->returns(false);

        expect($this->data)->unsetProjectServiceUsage()->never();
        expect($this->data)->forceServiceUsage()->never();

        $this->activator->unuseLegacyService($this->params);
    }

    public function itCreatesThePluginServiceIfPluginServiceDoesNotExistInTemplate()
    {
        $project = aMockProject()->withId(102)->build();
        $legacy  = array(Service::SVN => false);

        stub($this->service_manager)->getListOfAllowedServicesForProject($this->template)->returns(
            array($this->svn_core_service)
        );
        stub($this->service_manager)->getListOfAllowedServicesForProject($project)->returns(
            array()
        );

        expect($this->service_creator)->createService()->once();

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }

    public function itCreatesThePluginServiceIfNotAvailableInTemplate()
    {
        $project = aMockProject()->withId(102)->build();
        $legacy  = array(Service::SVN => false);

        stub($this->service_manager)->getListOfAllowedServicesForProject($this->template)->returns(
            array($this->svn_core_service, $this->svn_plugin_service)
        );
        stub($this->service_manager)->getListOfAllowedServicesForProject($project)->returns(
            array()
        );

        expect($this->service_creator)->createService()->once();

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }

    public function itDoesNotCreateServiceIfPreviouslyCreated()
    {
        $project = aMockProject()->withId(102)->build();
        $legacy  = array(Service::SVN => false);

        stub($this->service_manager)->getListOfAllowedServicesForProject($this->template)->returns(
            array($this->svn_core_service, $this->svn_plugin_service)
        );
        stub($this->service_manager)->getListOfAllowedServicesForProject($project)->returns(
            array($this->svn_plugin_service)
        );

        expect($this->service_creator)->createService()->never();

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }

    public function itDoesNotCreateServiceIfLegacyMustBeUsed()
    {
        $project = aMockProject()->withId(102)->build();
        $legacy  = array(Service::SVN => true);

        stub($this->service_manager)->getListOfAllowedServicesForProject($this->template)->returns(
            array($this->svn_core_service, $this->svn_plugin_service)
        );
        stub($this->service_manager)->getListOfAllowedServicesForProject($project)->returns(
            array($this->svn_plugin_service)
        );

        expect($this->service_creator)->createService()->never();

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }

    public function itDoesNotCreateThePluginServiceIfNoSVNAvailableInTemplate()
    {
        $project = aMockProject()->withId(102)->build();
        $legacy  = array(Service::SVN => false);

        stub($this->service_manager)->getListOfAllowedServicesForProject($this->template)->returns(
            array()
        );
        stub($this->service_manager)->getListOfAllowedServicesForProject($project)->returns(
            array()
        );

        expect($this->service_creator)->createService()->never();

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }
}
