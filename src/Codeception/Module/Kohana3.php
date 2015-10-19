<?php
namespace Codeception\Module;

use Codeception\Exception\ModuleConfigException;
use Codeception\Lib\Connector\Kohana3 as KohanaConnector;
use Codeception\Lib\Framework;
use Codeception\Lib\ModuleContainer;

class Kohana3 extends Framework
{
    /**
     * @var array
     */
    public $config = [];

    /**
     * Constructor.
     *
     * @param ModuleContainer $container
     * @param $config
     */
    public function __construct(ModuleContainer $container, $config = null)
    {
        $this->config = array_merge(
            [
                'bootstrap' => 'bootstrap.php',
                'application_dir' => 'application',
                'modules_dir' => 'modules',
                'system_dir' => 'system',
                'custom_config_reader' => null,
            ],
            (array)$config
        );

        $projectDir = \Codeception\Configuration::projectDir();

        if (!defined('EXT')) {
            define('EXT', '.php');
        }
        if (!defined('DOCROOT')) {
            define('DOCROOT', realpath($projectDir).DIRECTORY_SEPARATOR);
        }
        if (!defined('APPPATH')) {
            define('APPPATH', realpath(DOCROOT.$this->config['application_dir']).DIRECTORY_SEPARATOR);
        }
        if (!defined('MODPATH')) {
            define('MODPATH', realpath(DOCROOT.$this->config['modules_dir']).DIRECTORY_SEPARATOR);
        }
        if (!defined('SYSPATH')) {
            define('SYSPATH', realpath(DOCROOT.$this->config['system_dir']).DIRECTORY_SEPARATOR);
        }

        if (!defined('KOHANA_START_TIME')) {
            define('KOHANA_START_TIME', microtime(TRUE));
        }
        if (!defined('KOHANA_START_MEMORY')) {
            define('KOHANA_START_MEMORY', memory_get_usage());
        }

        if (!defined('API_MODE')) {
            define('API_MODE', true);
        }

        $this->config['bootstrap_file'] = APPPATH . $this->config['bootstrap'];

        parent::__construct($container);
    }

    /**
     * Initialize hook.
     */
    public function _initialize()
    {
        if (!class_exists('Kohana_Core'))
        {
            $this->checkBootstrapFileExists();
            $this->loadBootstrap();
            $this->setConfigReader();
        }
        $this->client = new KohanaConnector();
    }

    /**
     * Before hook.
     *
     * @param \Codeception\TestCase $test
     */
    public function _before(\Codeception\TestCase $test)
    {

    }

    /**
     * After hook.
     *
     * @param \Codeception\TestCase $test
     */
    public function _after(\Codeception\TestCase $test)
    {

    }

    /**
     * After step hook.
     *
     * @param \Codeception\Step $step
     */
    public function _afterStep(\Codeception\Step $step)
    {
        parent::_afterStep($step);
    }

    /**
     * Make sure the Kohana bootstrap file exists.
     *
     * @throws ModuleConfigException
     */
    protected function checkBootstrapFileExists()
    {
        $bootstrapFile = $this->config['bootstrap_file'];

        if (!file_exists($bootstrapFile)) {
            throw new ModuleConfigException(
                $this,
                "Kohana bootstrap file not found in $bootstrapFile.\nPlease provide a valid path to it using 'bootstrap' config param. "
            );
        }
    }

    /**
     * Load Kohana bootstrap
     */
    protected function loadBootstrap()
    {
        require $this->config['bootstrap_file'];
    }

    /**
     * Load modified config loader
     */
    protected function setConfigReader()
    {
        if ($this->config['custom_config_reader'])
        {
            \Kohana::$config->detach(new \Config_File);
            \Kohana::$config->attach(new $this->config['custom_config_reader']);
        }
    }

}
