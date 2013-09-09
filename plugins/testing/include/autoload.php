<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
function autoloadb74c168e002e6b7fa8982a9ef56a4bce($class) {
    static $classes = null;
    if ($classes === null) {
        $classes = array(
            'testing_campaign_campaign' => '/Campaign/Campaign.class.php',
            'testing_campaign_campaigncollectionpresenter' => '/Campaign/CampaignCollectionPresenter.class.php',
            'testing_campaign_campaigncontroller' => '/Campaign/CampaignController.class.php',
            'testing_campaign_campaigncreationpresenter' => '/Campaign/CampaignCreationPresenter.class.php',
            'testing_campaign_campaigncreator' => '/Campaign/CampaignCreator.class.php',
            'testing_campaign_campaigndao' => '/Campaign/CampaignDao.class.php',
            'testing_campaign_campaignfactory' => '/Campaign/CampaignFactory.class.php',
            'testing_campaign_campaigninfocollectionpresenter' => '/Campaign/CampaignInfoCollectionPresenter.class.php',
            'testing_campaign_campaigninfopresenter' => '/Campaign/CampaignInfoPresenter.class.php',
            'testing_campaign_campaigninfopresentercollectionfactory' => '/Campaign/CampaignInfoPresenterCollectionFactory.class.php',
            'testing_campaign_campaigninfopresenterfactory' => '/Campaign/CampaignInfoPresenterFactory.class.php',
            'testing_campaign_campaignmanager' => '/Campaign/CampaignManager.class.php',
            'testing_campaign_campaignpresenter' => '/Campaign/CampaignPresenter.class.php',
            'testing_campaign_campaignpresentercollectionfactory' => '/Campaign/CampaignPresenterCollectionFactory.class.php',
            'testing_campaign_campaignpresenterfactory' => '/Campaign/CampaignPresenterFactory.class.php',
            'testing_campaign_campaignstatpresenter' => '/Campaign/CampaignStatPresenter.class.php',
            'testing_campaign_campaignstatpresenterfactory' => '/Campaign/CampaignStatPresenterFactory.class.php',
            'testing_defect_defect' => '/Defect/Defect.class.php',
            'testing_defect_defectcollection' => '/Defect/DefectCollection.class.php',
            'testing_defect_defectcollectionfeeder' => '/Defect/DefectCollectionFeeder.class.php',
            'testing_defect_defectcontroller' => '/Defect/DefectController.class.php',
            'testing_defect_defectdao' => '/Defect/DefectDao.class.php',
            'testing_defect_defectfactory' => '/Defect/DefectFactory.class.php',
            'testing_defect_defectpresenter' => '/Defect/DefectPresenter.class.php',
            'testing_defect_defectpresentercollection' => '/Defect/DefectPresenterCollection.class.php',
            'testing_report_reportcontroller' => '/Report/ReportController.class.php',
            'testing_requirement_requirement' => '/Requirement/Requirement.class.php',
            'testing_requirement_requirementcontroller' => '/Requirement/RequirementController.class.php',
            'testing_requirement_requirementinfocollectionpresenter' => '/Requirement/RequirementInfoCollectionPresenter.class.php',
            'testing_requirement_requirementinfocollectionpresenterfactory' => '/Requirement/RequirementInfoCollectionPresenterFactory.class.php',
            'testing_requirement_requirementinfopresenter' => '/Requirement/RequirementInfoPresenter.class.php',
            'testing_requirement_requirementpresenter' => '/Requirement/RequirementPresenter.class.php',
            'testing_requirement_testcaseassociationdao' => '/Requirement/TestCaseAssociationDao.class.php',
            'testing_requirement_testcasepresenter' => '/Requirement/TestCasePresenter.class.php',
            'testing_testcase_testcase' => '/TestCase/TestCase.class.php',
            'testing_testexecution_testexecution' => '/TestExecution/TestExecution.class.php',
            'testing_testexecution_testexecutioncollection' => '/TestExecution/TestExecutionCollection.class.php',
            'testing_testexecution_testexecutioncollectionfeeder' => '/TestExecution/TestExecutionCollectionFeeder.class.php',
            'testing_testexecution_testexecutioncontroller' => '/TestExecution/TestExecutionController.class.php',
            'testing_testexecution_testexecutiondao' => '/TestExecution/TestExecutionDao.class.php',
            'testing_testexecution_testexecutionfactory' => '/TestExecution/TestExecutionFactory.class.php',
            'testing_testexecution_testexecutioninfopresenter' => '/TestExecution/TestExecutionInfoPresenter.class.php',
            'testing_testexecution_testexecutioninfopresenterfactory' => '/TestExecution/TestExecutionInfoPresenterFactory.class.php',
            'testing_testexecution_testexecutionmanager' => '/TestExecution/TestExecutionManager.class.php',
            'testing_testexecution_testexecutionpresenter' => '/TestExecution/TestExecutionPresenter.class.php',
            'testing_testresult_testresult' => '/TestResult/TestResult.class.php',
            'testing_testresult_testresultcollection' => '/TestResult/TestResultCollection.class.php',
            'testing_testresult_testresultcollectionfeeder' => '/TestResult/TestResultCollectionFeeder.class.php',
            'testing_testresult_testresultcontroller' => '/TestResult/TestResultController.class.php',
            'testing_testresult_testresultdao' => '/TestResult/TestResultDao.class.php',
            'testing_testresult_testresultfactory' => '/TestResult/TestResultFactory.class.php',
            'testing_testresult_testresultnotrun' => '/TestResult/TestResultNotRun.class.php',
            'testing_testresult_testresultpresenter' => '/TestResult/TestResultPresenter.class.php',
            'testingconfiguration' => '/TestingConfiguration.class.php',
            'testingcontroller' => '/TestingController.class.php',
            'testingfacadetrackercreationpresenter' => '/TestingFacadeTrackerCreationPresenter.class.php',
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
spl_autoload_register('autoloadb74c168e002e6b7fa8982a9ef56a4bce');
// @codeCoverageIgnoreEnd