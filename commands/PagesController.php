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
use jacmoe\mdpages\components\Utility;
use jacmoe\mdpages\components\Meta;
use JamesMoss\Flywheel\Document;

class PagesController extends Controller
{
    /**
     * @var Mutex|array|string the mutex object or the application component ID of the mutex.
     * After the controller object is created, if you want to change this property, you should only assign it
     * with a mutex connection object.
     */
    public $mutex = 'yii\mutex\FileMutex';

    protected $flywheel_config = null;
    protected $flywheel_repo = null;

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

    protected function updateDB($files) {
        $module = \jacmoe\mdpages\Module::getInstance();
        $repo = $this->getFlywheelRepo();

        $filename_pattern = '/\.md$/';

        $metaParser = new Meta;

        $file_action = '';

        foreach ($files as $file) {
            if (!preg_match($filename_pattern, $file)) {
                continue;
            }

            $file_field = substr($file, strlen(\Yii::getAlias('@pages')));

            if(!file_exists($file)) {
                // if the post exists, delete it
                $page = $repo->query()->where('file', '==', $file_field)->execute();
                $result = $page->first();
                if($result != null) {
                    echo "File $result->file exists - deleting ..\n";
                    $repo->delete($result);
                }
                $file_action = 'deleted';
                echo $file_field . ' was ' . $file_action . "\n";
                continue;
            }

            $metatags = array();
            $metatags = $metaParser->parse(file_get_contents($file));

            $url = $file_field;
            $url = ltrim($url, '/');
            $raw_url = pathinfo($url);
            $url = $raw_url['filename'];
            if($url == 'README') continue;
            if($raw_url['dirname'] != '.') {
                $url = $raw_url['dirname'] . '/' . $url;
            }

            $values = array();

            foreach($metatags as $key => $value) {
                $values[$key] = $value;
            }
            $values['url'] = $url;
            $values['file'] = $file_field;

            $file_action = 'created';

            // if the post exists, delete it
            $page = $repo->query()->where('file', '==', $values['file'])->execute();
            $result = $page->first();
            if($result != null) {
                $file_action = 'updated';
                $repo->delete($result);
            }

            $page = new Document($values);
            $repo->store($page);

            echo $file_field . ' was ' . $file_action . "\n";

        }

    }

    public function actionUpdate()
    {
        $module = \jacmoe\mdpages\Module::getInstance();

        if(!file_exists(\Yii::getAlias($module->pages_directory))) {
            $this->stderr("Execution terminated: the repository to update does not exist - please run init first.\n", Console::FG_RED);
            return self::EXIT_CODE_ERROR;
        }

        if (!$this->acquireMutex()) {
            $this->stderr("Execution terminated: command is already running.\n", Console::FG_RED);
            return self::EXIT_CODE_ERROR;
        }

        $git = Yii::createObject('jacmoe\mdpages\components\yii2tech\Git');

        $log = '';
        if($git->hasRemoteChanges(\Yii::getAlias($module->pages_directory), $log)) {
            //echo $log;

            $placeholders = [
                '{binPath}' => 'git',
                '{projectRoot}' => \Yii::getAlias($module->pages_directory),
                '{remote}' => 'origin',
                '{branch}' => 'master',
            ];
            $result = Shell::execute('(cd {projectRoot}; {binPath} diff --name-only HEAD {remote}/{branch})', $placeholders);
            $raw_files = $result->toString();

            $git->applyRemoteChanges(\Yii::getAlias($module->pages_directory), $log);
            //echo $log;

            $files = explode("\n", $raw_files);
            array_shift($files); // the first entry is the command
            array_pop($files); // the last entry is the exit code

            $to_update = array();
            foreach($files as $file) {
                $to_update[] = \Yii::getAlias('@pages') . '/' . $file;
            }

            $this->updateDB($to_update);

        } else {
            echo "No changes detected\n\n";
        }

        $this->releaseMutex();
        return self::EXIT_CODE_NORMAL;

    }

    public function actionInit()
    {
        $module = \jacmoe\mdpages\Module::getInstance();

        if(file_exists(\Yii::getAlias($module->pages_directory))) {
            $this->stderr("Execution terminated: content directory already cloned.\n", Console::FG_RED);
            return self::EXIT_CODE_ERROR;
        }

        if (!$this->acquireMutex()) {
            $this->stderr("Execution terminated: command is already running.\n", Console::FG_RED);
            return self::EXIT_CODE_ERROR;
        }

        $result = Shell::execute('(cd {projectRoot}; {binPath} clone {repository} content)', [
            '{binPath}' => 'git',
            '{projectRoot}' => \Yii::getAlias($module->root_directory),
            '{repository}' => $module->repository_url,
        ]);
        $log = $result->toString();
        //echo $log . "\n\n";

        $repo = $this->getFlywheelRepo();

        $filter = '\jacmoe\mdpages\components\ContentFileFilterIterator';
        $files = Utility::getFiles(\Yii::getAlias('@pages'), $filter);

        $this->updateDB($files);

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

    protected function getFlywheelRepo()
    {
        $module = \jacmoe\mdpages\Module::getInstance();

        if(!isset($this->flywheel_config)) {
            $config_dir = \Yii::getAlias($module->flywheel_config);
            if(!file_exists($config_dir)) {
                \yii\helpers\FileHelper::createDirectory($config_dir);
            }
            $this->flywheel_config = new \JamesMoss\Flywheel\Config($config_dir);
        }
        if(!isset($this->flywheel_repo)) {
            $this->flywheel_repo = new \JamesMoss\Flywheel\Repository($module->flywheel_repo, $this->flywheel_config);
        }
        return $this->flywheel_repo;
    }

}
