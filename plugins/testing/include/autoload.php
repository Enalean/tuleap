<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
function autoload87666910ab39e847533e761126997d07($class) {
    static $classes = null;
    if ($classes === null) {
        $classes = array(
            'testing_campaign_campaign' => '/Campaign/Campaign.class.php',
            'testing_campaign_campaigncollectionpresenter' => '/Campaign/CampaignCollectionPresenter.class.php',
            'testing_campaign_campaigncontroller' => '/Campaign/CampaignController.class.php',
            'testing_campaign_campaigncreator' => '/Campaign/CampaignCreator.class.php',
            'testing_campaign_campaigndao' => '/Campaign/CampaignDao.class.php',
            'testing_campaign_campaignfactory' => '/Campaign/CampaignFactory.class.php',
            'testing_campaign_campaignmanager' => '/Campaign/CampaignManager.class.php',
            'testing_campaign_campaignpresenter' => '/Campaign/CampaignPresenter.class.php',
            'testing_campaign_campaignpresentercollectionfactory' => '/Campaign/CampaignPresenterCollectionFactory.class.php',
            'testing_campaign_campaignstatpresenter' => '/Campaign/CampaignStatPresenter.class.php',
            'testing_requirement_requirementcontroller' => '/Requirement/RequirementController.class.php',
            'testing_testexecution_testexecutioncontroller' => '/TestExecution/TestExecutionController.class.php',
            'testingplugin' => '/testingPlugin.class.php',
            'testingplugindescriptor' => '/TestingPluginDescriptor.class.php',
            'testingplugininfo' => '/TestingPluginInfo.class.php',
            'testingrouter' => '/TestingRouter.class.php'
        );
    }
    $cn = strtolower($class);
    if (isset($classes[$cn])) {
        require dirname(__FILE__) . $classes[$cn];
    }
}
spl_autoload_register('autoload87666910ab39e847533e761126997d07');
// @codeCoverageIgnoreEnd