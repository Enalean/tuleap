<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
function autoloadc8028b5a2207ce542e3b8516902af436($class) {
    static $classes = null;
    if ($classes === null) {
        $classes = array(
            'botmattermostplugin' => '/botmattermostPlugin.class.php',
            'router' => '/BotMattermost/Router.php',
            'tuleap\\botmattermost\\bot\\bot' => '/BotMattermost/Bot/Bot.php',
            'tuleap\\botmattermost\\bot\\botdao' => '/BotMattermost/Bot/BotDao.php',
            'tuleap\\botmattermost\\bot\\botfactory' => '/BotMattermost/Bot/BotFactory.php',
            'tuleap\\botmattermost\\botmattermostlogger' => '/BotMattermostLogger.php',
            'tuleap\\botmattermost\\controller\\admincontroller' => '/BotMattermost/Controller/AdminController.php',
            'tuleap\\botmattermost\\exception\\botalreadyexistexception' => '/BotMattermost/Exception/BotAlreadyExistException.php',
            'tuleap\\botmattermost\\exception\\botnotfoundexception' => '/BotMattermost/Exception/BotNotFoundExceptionException.php',
            'tuleap\\botmattermost\\exception\\cannotcreatebotexception' => '/BotMattermost/Exception/CannotCreateBotException.php',
            'tuleap\\botmattermost\\exception\\cannotdeletebotexception' => '/BotMattermost/Exception/CannotDeleteBotException.php',
            'tuleap\\botmattermost\\exception\\cannotupdatebotexception' => '/BotMattermost/Exception/CannotUpdateBotException.php',
            'tuleap\\botmattermost\\exception\\channelsnotfoundexception' => '/BotMattermost/Exception/ChannelsNotFoundException.php',
            'tuleap\\botmattermost\\plugin\\plugindescriptor' => '/BotMattermost/Plugin/PluginDescriptor.php',
            'tuleap\\botmattermost\\plugin\\plugininfo' => '/BotMattermost/Plugin/PluginInfo.php',
            'tuleap\\botmattermost\\presenter\\adminpresenter' => '/BotMattermost/Presenter/AdminPresenter.php',
            'tuleap\\botmattermost\\senderservices\\clientbotmattermost' => '/SenderServices/ClientBotMattermost.php',
            'tuleap\\botmattermost\\senderservices\\encodermessage' => '/SenderServices/EncoderMessage.php',
            'tuleap\\botmattermost\\senderservices\\markdownengine\\markdownmustache' => '/SenderServices/MarkdownEngine/MarkdownMustache.php',
            'tuleap\\botmattermost\\senderservices\\markdownengine\\markdownmustacherenderer' => '/SenderServices/MarkdownEngine/MarkdownMustacheRenderer.php',
            'tuleap\\botmattermost\\senderservices\\markdownengine\\markdowntemplaterendererfactory' => '/SenderServices/MarkdownEngine/MarkdownTemplateRendererFactory.php',
            'tuleap\\botmattermost\\senderservices\\message' => '/SenderServices/Message.php',
            'tuleap\\botmattermost\\senderservices\\sender' => '/SenderServices/Sender.php',
            'tuleap\\botmattermost\\senderservicesexception\\exception\\hasnomessagecontentexception' => '/SenderServices/Exception/HasNoMessageContentException.php',
            'tuleap\\botmattermostgit\\senderservices\\attachment' => '/SenderServices/Attachment.php'
        );
    }
    $cn = strtolower($class);
    if (isset($classes[$cn])) {
        require dirname(__FILE__) . $classes[$cn];
    }
}
spl_autoload_register('autoloadc8028b5a2207ce542e3b8516902af436');
// @codeCoverageIgnoreEnd
