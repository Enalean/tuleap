<?php
/**
 * GitPHP ControllerBase
 *
 * Base class that all controllers extend
 *
 */

namespace Tuleap\Git\GitPHP;

use Smarty\Smarty;
use Tuleap\Git\GitPHP\SmartyPlugins\AgeString;
use Tuleap\Git\GitPHP\SmartyPlugins\Highlight;

require_once __DIR__ . '/../../../vendor/smarty-gettext/smarty-gettext/block.t.php';

/**
 * ControllerBase class
 *
 * @abstract
 */
abstract class ControllerBase
{
    /**
     * tpl
     *
     * Smarty instance
     *
     * @access protected
     */
    protected $tpl;

    /**
     * project
     *
     * Current project
     *
     * @access protected
     * @var Project
     */
    protected $project;

    /**
     * @var \GitRepository
     */
    private $tuleap_git_repository;

    /**
     * params
     *
     * Parameters
     *
     * @access protected
     */
    protected $params = [];

    /**
     * headers
     *
     * Headers
     *
     * @access protected
     */
    protected $headers = [];

    /**
     * __construct
     *
     * Constructor
     *
     * @access public
     * @return mixed controller object
     * @throws \Exception on invalid project
     */
    public function __construct()
    {
        $this->tpl = new Smarty();
        $this->tpl->registerPlugin(Smarty::PLUGIN_MODIFIER, AgeString::MODIFIER, AgeString::callback(...));
        $this->tpl->registerPlugin(Smarty::PLUGIN_MODIFIER, Highlight::MODIFIER, Highlight::callback(...));
        $this->tpl->registerPlugin(Smarty::PLUGIN_BLOCK, 't', smarty_block_t(...));
        $this->tpl->setTemplateDir(__DIR__ . '/../../../templates/gitphp/');
        $this->tpl->registerPlugin(Smarty::PLUGIN_MODIFIER, 'urlencode', fn(string $param): string => urlencode($param));
        $this->tpl->registerPlugin(Smarty::PLUGIN_MODIFIER, 'substr', fn (string $string, int $offset, int $length): string => substr($string, $offset, $length));
        $this->tpl->registerPlugin(
            Smarty::PLUGIN_FUNCTION,
            'display_potentially_dangerous_bidirectional_text_warning',
            function (array $params): string {
                if (! isset($params['diff']) || ! is_array($params['diff'])) {
                    return '';
                }
                $warning = \Tuleap\Git\Unicode\DangerousUnicodeText::getCodePotentiallyDangerousBidirectionalUnicodeTextWarning(
                    implode($params['diff'])
                );
                if ($warning === null) {
                    return '';
                }
                $html_purifier = \Codendi_HTMLPurifier::instance();
                return '<div class="tlp-alert-warning">' . $html_purifier->purify($warning) . '</div>';
            }
        );

        // Use a dedicated directory for smarty temporary files if needed.
        if (Config::GetInstance()->HasKey('smarty_tmp')) {
            $smarty_tmp = Config::GetInstance()->GetValue('smarty_tmp');
            if (! is_dir($smarty_tmp)) {
                mkdir($smarty_tmp, 0755, true);
            }

            $templates_c = $smarty_tmp . '/templates_c';
            if (! is_dir($templates_c)) {
                mkdir($templates_c, 0755, true);
            }
            $this->tpl->setCompileDir($templates_c);

            $cache = $smarty_tmp . '/cache';
            if (! is_dir($cache)) {
                mkdir($cache, 0755, true);
            }
            $this->tpl->setCacheDir($cache);
        }

        $this->project               = ProjectList::GetInstance()->GetProject();
        $this->tuleap_git_repository = ProjectList::GetInstance()->getRepository();

        if (isset($_GET['s'])) {
            $this->params['search'] = $_GET['s'];
        }
        if (isset($_GET['st'])) {
            $this->params['searchtype'] = $_GET['st'];
        }

        $this->ReadQuery();
    }

    /**
     * GetTemplate
     *
     * Gets the template for this controller
     *
     * @access protected
     * @abstract
     * @return string template filename
     */
    abstract protected function GetTemplate(); // phpcs:ignore

    /**
     * GetName
     *
     * Gets the name of this controller's action
     *
     * @abstract
     * @access public
     * @param bool $local true if caller wants the localized action name
     * @return string action name
     */
    abstract public function GetName($local = false); // phpcs:ignore

    /**
     * ReadQuery
     *
     * Read query into parameters
     *
     * @abstract
     * @access protected
     */
    abstract protected function ReadQuery(); // phpcs:ignore

    /**
     * SetParam
     *
     * Set a parameter
     *
     * @access protected
     * @param string $key key to set
     * @param mixed $value value to set
     */
    public function SetParam($key, $value) // phpcs:ignore
    {
        if (empty($key)) {
            return;
        }

        if (empty($value)) {
            unset($this->params[$key]);
        }

        $this->params[$key] = $value;
    }

    /**
     * LoadHeaders
     *
     * Loads headers for this template
     *
     * @access protected
     */
    protected function LoadHeaders() // phpcs:ignore
    {
    }

    /**
     * LoadData
     *
     * Loads data for this template
     *
     * @access protected
     * @abstract
     */
    abstract protected function LoadData(); // phpcs:ignore

    /**
     * LoadCommonData
     *
     * Loads common data used by all templates
     *
     * @access private
     */
    private function LoadCommonData() // phpcs:ignore
    {
        $this->tpl->assign('action', $this->GetName());
        $this->tpl->assign('actionlocal', $this->GetName(true));
        if ($this->project) {
            $this->tpl->assign('project', $this->project);
        }
        if (Config::GetInstance()->GetValue('search', true)) {
            $this->tpl->assign('enablesearch', true);
        }
        if (isset($this->params['search'])) {
            $this->tpl->assign('search', $this->params['search']);
        }
        if (isset($this->params['searchtype'])) {
            $this->tpl->assign('searchtype', $this->params['searchtype']);
        }

        $this->tpl->assign('snapshotformats', Archive::SupportedFormats());
    }

    /**
     * RenderHeaders
     *
     * Renders any special headers
     *
     * @access public
     */
    public function RenderHeaders() // phpcs:ignore
    {
        $this->LoadHeaders();

        if (count($this->headers) > 0) {
            foreach ($this->headers as $hdr) {
                header($hdr);
            }
        }
    }

    /**
     * Render
     *
     * Renders the output
     *
     * @access public
     */
    public function Render() // phpcs:ignore
    {
        $this->tpl->clearAllAssign();
        $this->LoadCommonData();
        $this->LoadData();

        $this->tpl->display($this->GetTemplate());
    }

    /**
     * @return \GitRepository
     */
    protected function getTuleapGitRepository()
    {
        return $this->tuleap_git_repository;
    }
}
