<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace jacmoe\mdpages\commands;

use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\caching\Cache;
use yii\console\Controller;
use Yii;
use yii\di\Instance;
use yii\helpers\Console;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use yii\mail\BaseMailer;
use yii\mutex\Mutex;

use jacmoe\mdpages\components\yii2tech\Shell;

class PagesController extends Controller
{
    /**
     * @var Mutex|array|string the mutex object or the application component ID of the mutex.
     * After the controller object is created, if you want to change this property, you should only assign it
     * with a mutex connection object.
     */
    public $mutex = 'yii\mutex\FileMutex';

    public $versionControlSystems = [
        '.git' => [
            'class' => 'jacmoe\mdpages\components\yii2tech\Git'
        ],
        '.hg' => [
            'class' => 'jacmoe\mdpages\components\yii2tech\Mercurial'
        ],
    ];

    public function actionIndex()
    {
    }

    public function actionUpdate()
    {
    }

    public function actionInit()
    {
        $module = \jacmoe\mdpages\Module::getInstance();

        if(file_exists(\Yii::getAlias($module->pages_directory))) {
            $this->stderr("Execution terminated: content directory already cloned.\n", Console::FG_RED);
            return self::EXIT_CODE_ERROR;
        }
        //if (!is_file(__DIR__ . '/config.php')) {
        //    throw new Exception("The configuration file does not exist: $configFile");
        //}
        //Yii::configure($this, require __DIR__ . '/config.php');

        if (!$this->acquireMutex()) {
            $this->stderr("Execution terminated: command is already running.\n", Console::FG_RED);
            return self::EXIT_CODE_ERROR;
        }
        //$git = Yii::createObject('jacmoe\mdpages\components\yii2tech\Git');

        $result = Shell::execute('(cd {projectRoot}; {binPath} clone {repository} content)', [
            '{binPath}' => 'git',
            '{projectRoot}' => \Yii::getAlias($module->projectRootPath),
            '{repository}' => $module->repository_url,
        ]);
        $log = $result->toString();
        echo $log . "\n\n";

        $this->releaseMutex();
        return self::EXIT_CODE_NORMAL;
    }
    /**
     * Acquires current action lock.
     * @return boolean lock acquiring result.
     */
    protected function acquireMutex()
    {
        $this->mutex = Instance::ensure($this->mutex, Mutex::className());
        return $this->mutex->acquire($this->composeMutexName());
    }
    /**
     * Release current action lock.
     * @return boolean lock release result.
     */
    protected function releaseMutex()
    {
        return $this->mutex->release($this->composeMutexName());
    }
    /**
     * Composes the mutex name.
     * @return string mutex name.
     */
    protected function composeMutexName()
    {
        return $this->className() . '::' . $this->action->getUniqueId();
    }
}
