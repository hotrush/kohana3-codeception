<?php
namespace Codeception\Module;

use Codeception\Exception\ModuleConfigException;
use Codeception\Lib\Connector\Kohana3 as KohanaConnector;
use Codeception\Lib\Framework;
use Codeception\Lib\ModuleContainer;
use Codeception\Lib\Interfaces\ORM;

class Kohana3 extends Framework implements ORM
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
                'environment_file' => '.env.testing',
                'api_mode' => false,
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
            define('API_MODE', $this->config['api_mode']);
        }

        $this->config['bootstrap_file'] = APPPATH . $this->config['bootstrap'];

        parent::__construct($container);
    }

    /**
     * Initialize hook.
     */
    public function _initialize()
    {
        $_SERVER['KOHANA_ENV'] = 'testing';

        if (!class_exists('Kohana_Core'))
        {
            $this->checkBootstrapFileExists();
            $this->loadBootstrap();
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
     * Inserts record into the database.
     * If you pass the name of a database table as the first argument, this method returns an integer ID.
     * You can also pass the class name of an Eloquent model, in that case this method returns an Eloquent model.
     *
     * ``` php
     * <?php
     * $user_id = $I->haveRecord('users', array('name' => 'Davert')); // returns integer
     * $user = $I->haveRecord('App\User', array('name' => 'Davert')); // returns Eloquent model
     * ?>
     * ```
     *
     * @param string $table
     * @param array $attributes
     * @return integer|ORM
     * @part orm
     */
    public function haveRecord($table, $attributes = [])
    {
        if (class_exists($this->getModelClassName($table))) {
            $model = \ORM::factory($table);
            if (! $model instanceof \ORM) {
                throw new \RuntimeException("Class $table is not an ORM model");
            }
            $model->values($attributes)->create();
            return $model;
        }
        try {
            list($id, ) = \DB::insert($table, array_keys($attributes))->values(array_values($attributes))->execute();
            return $id;
        } catch (\Exception $e) {
            $this->fail("Could not insert record into table '$table':\n\n" . $e->getMessage());
        }
    }

    /**
     * Checks that record exists in database.
     * You can pass the name of a database table or the class name of an Eloquent model as the first argument.
     *
     * ``` php
     * <?php
     * $I->seeRecord('users', array('name' => 'davert'));
     * $I->seeRecord('App\User', array('name' => 'davert'));
     * ?>
     * ```
     *
     * @param string $table
     * @param array $attributes
     * @part orm
     */
    public function seeRecord($table, $attributes = [])
    {
        if (class_exists($this->getModelClassName($table))) {
            if (! $this->findModel($table, $attributes)) {
                $this->fail("Could not find $table with " . json_encode($attributes));
            }
        } elseif (! $this->findRecord($table, $attributes)) {
            $this->fail("Could not find matching record in table '$table'");
        }
    }
    /**
     * Checks that record does not exist in database.
     * You can pass the name of a database table or the class name of an Eloquent model as the first argument.
     *
     * ``` php
     * <?php
     * $I->dontSeeRecord('users', array('name' => 'davert'));
     * $I->dontSeeRecord('App\User', array('name' => 'davert'));
     * ?>
     * ```
     *
     * @param string $table
     * @param array $attributes
     * @part orm
     */
    public function dontSeeRecord($table, $attributes = [])
    {
        if (class_exists($this->getModelClassName($table))) {
            if ($this->findModel($table, $attributes)) {
                $this->fail("Unexpectedly found matching $table with " . json_encode($attributes));
            }
        } elseif ($this->findRecord($table, $attributes)) {
            $this->fail("Unexpectedly found matching record in table '$table'");
        }
    }
    /**
     * Retrieves record from database
     * If you pass the name of a database table as the first argument, this method returns an array.
     * You can also pass the class name of an Eloquent model, in that case this method returns an Eloquent model.
     *
     * ``` php
     * <?php
     * $record = $I->grabRecord('users', array('name' => 'davert')); // returns array
     * $record = $I->grabRecord('App\User', array('name' => 'davert')); // returns Eloquent model
     * ?>
     * ```
     *
     * @param string $table
     * @param array $attributes
     * @return array|EloquentModel
     * @part orm
     */
    public function grabRecord($table, $attributes = [])
    {
        if (class_exists($this->getModelClassName($table))) {
            if (! $model = $this->findModel($table, $attributes)) {
                $this->fail("Could not find $table with " . json_encode($attributes));
            }
            return $model;
        }
        if (! $record = $this->findRecord($table, $attributes)) {
            $this->fail("Could not find matching record in table '$table'");
        }
        return $record;
    }
    /**
     * Checks that number of given records were found in database.
     * You can pass the name of a database table or the class name of an Eloquent model as the first argument.
     *
     * ``` php
     * <?php
     * $I->seeNumRecords(1, 'users', array('name' => 'davert'));
     * $I->seeNumRecords(1, 'App\User', array('name' => 'davert'));
     * ?>
     * ```
     *
     * @param integer $expectedNum
     * @param string $table
     * @param array $attributes
     * @part orm
     */
    public function seeNumRecords($expectedNum, $table, $attributes = [])
    {
        if (class_exists($this->getModelClassName($table))) {
            $currentNum = $this->countModels($table, $attributes);
            if ($currentNum != $expectedNum) {
                $this->fail("The number of found $table ($currentNum) does not match expected number $expectedNum with " . json_encode($attributes));
            }
        } else {
            $currentNum = $this->countRecords($table, $attributes);
            if ($currentNum != $expectedNum) {
                $this->fail("The number of found records ($currentNum) does not match expected number $expectedNum in table $table with " . json_encode($attributes));
            }
        }
    }
    /**
     * Retrieves number of records from database
     * You can pass the name of a database table or the class name of an Eloquent model as the first argument.
     *
     * ``` php
     * <?php
     * $I->grabNumRecords('users', array('name' => 'davert'));
     * $I->grabNumRecords('App\User', array('name' => 'davert'));
     * ?>
     * ```
     *
     * @param string $table
     * @param array $attributes
     * @return integer
     * @part orm
     */
    public function grabNumRecords($table, $attributes = [])
    {
        return class_exists($this->getModelClassName($table))? $this->countModels($table, $attributes) : $this->countRecords($table, $attributes);
    }
    /**
     * @param string $modelClass
     * @param array $attributes
     *
     * @return \ORM
     */
    protected function findModel($modelClass, $attributes = [])
    {
        $query = $this->getQueryBuilderFromModel($modelClass);
        foreach ($attributes as $key => $value) {
            $query->where($key, '=', $value);
        }
        return $query->find();
    }
    /**
     * @param string $table
     * @param array $attributes
     * @return array
     */
    protected function findRecord($table, $attributes = [])
    {
        $query = $this->getQueryBuilderFromTable($table);
        foreach ($attributes as $key => $value) {
            $query->where($key, '=', $value);
        }
        return (array) $query->execute()->current();
    }
    /**
     * @param string $modelClass
     * @param array $attributes
     * @return integer
     */
    protected function countModels($modelClass, $attributes = [])
    {
        $query = $this->getQueryBuilderFromModel($modelClass);
        foreach ($attributes as $key => $value) {
            $query->where($key, '=', $value);
        }
        return $query->count();
    }
    /**
     * @param string $table
     * @param array $attributes
     * @return integer
     */
    protected function countRecords($table, $attributes = [])
    {
        $query = $this->getQueryBuilderFromTable($table)->select([\DB::expr('COUNT(*)'), 'count']);
        foreach ($attributes as $key => $value) {
            $query->where($key, '=', $value);
        }
        return $query->execute()->get('count');
    }
    /**
     * @param string $modelClass
     *
     * @return EloquentModel
     */
    protected function getQueryBuilderFromModel($modelClass)
    {
        $model = \ORM::factory($modelClass);
        if (!$model instanceof \ORM) {
            throw new \RuntimeException("Class $modelClass is not an ORM model");
        }
        return $model;
    }
    /**
     * @param string $table
     *
     * @return EloquentModel
     */
    protected function getQueryBuilderFromTable($table)
    {
        return \DB::select()->from($table);
    }

    protected function getModelClassName($table)
    {
        return 'Model_'.$table;
    }

}
