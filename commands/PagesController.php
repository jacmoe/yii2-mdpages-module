<?php
/**
* @link http://www.yiiframework.com/
* @copyright Copyright (c) 2008 Yii Software LLC
* @license http://www.yiiframework.com/license/
*/

namespace jacmoe\mdpages\commands;

use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\caching\Cache;
use yii\console\Controller;
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
use JamesMoss\Flywheel\Config;
use JamesMoss\Flywheel\Repository;

class PagesController extends Controller
{
    /**
    * @var Mutex|array|string the mutex object or the application component ID of the mutex.
    * After the controller object is created, if you want to change this property, you should only assign it
    * with a mutex connection object.
    */
    public $mutex = 'yii\mutex\FileMutex';

    /**
     * [$flywheel_config description]
     * @var [type]
     */
    protected $flywheel_config = null;

    /**
     * [$flywheel_repo description]
     * @var [type]
     */
    protected $flywheel_repo = null;

    /**
     * [$versionControlSystems description]
     * @var [type]
     */
    public $versionControlSystems = [
        '.git' => [
            'class' => 'jacmoe\mdpages\components\yii2tech\Git'
        ],
        '.hg' => [
            'class' => 'jacmoe\mdpages\components\yii2tech\Mercurial'
        ],
    ];

    /**
    * [updateDB description]
    * @param  [type] $files [description]
    * @return [type]        [description]
    */
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

            $file_field = substr($file, strlen(Yii::getAlias('@pages')));

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
                $values['created'] = $result->created;
                $repo->delete($result);
            }

            if($file_action == 'created') {
                $values['created'] = new \DateTime('@' . time());
            }
            $values['updated'] = new \DateTime('@' . time());

            $page = new Document($values);
            $repo->store($page);

            echo $file_field . ' was ' . $file_action . "\n";

        }

    }

    /**
    * [actionUpdate description]
    * @return [type] [description]
    */
    public function actionUpdate()
    {
        $module = \jacmoe\mdpages\Module::getInstance();

        if(!is_dir(Yii::getAlias($module->pages_directory))) {
            $this->stderr("Execution terminated: the repository to update does not exist - please run init first.\n", Console::FG_RED);
            return self::EXIT_CODE_ERROR;
        }

        if (!$this->acquireMutex()) {
            $this->stderr("Execution terminated: command is already running.\n", Console::FG_RED);
            return self::EXIT_CODE_ERROR;
        }

        $git = Yii::createObject('jacmoe\mdpages\components\yii2tech\Git');

        $log = '';
        if($git->hasRemoteChanges(Yii::getAlias($module->pages_directory), $log)) {
            //echo $log;

            $placeholders = [
                '{binPath}' => 'git',
                '{projectRoot}' => Yii::getAlias($module->pages_directory),
                '{remote}' => 'origin',
                '{branch}' => 'master',
            ];
            $result = Shell::execute('(cd {projectRoot}; {binPath} diff --name-only HEAD {remote}/{branch})', $placeholders);
            $raw_files = $result->toString();

            $git->applyRemoteChanges(Yii::getAlias($module->pages_directory), $log);
            //echo $log;

            $files = explode("\n", $raw_files);
            array_shift($files); // the first entry is the command
            array_pop($files); // the last entry is the exit code

            $to_update = array();
            foreach($files as $file) {
                $to_update[] = Yii::getAlias('@pages') . '/' . $file;
            }

            $this->updateDB($to_update);

            $this->createImageSymlink();

        } else {
            echo "No changes detected\n\n";
        }

        $this->releaseMutex();
        return self::EXIT_CODE_NORMAL;

    }

    /**
    * [actionInit description]
    * @return [type] [description]
    */
    public function actionInit()
    {
        $module = \jacmoe\mdpages\Module::getInstance();

        if(is_dir(Yii::getAlias($module->pages_directory))) {
            $this->stderr("Execution terminated: content directory already cloned.\n", Console::FG_RED);
            return self::EXIT_CODE_ERROR;
        }

        if (!$this->acquireMutex()) {
            $this->stderr("Execution terminated: command is already running.\n", Console::FG_RED);
            return self::EXIT_CODE_ERROR;
        }

        $result = Shell::execute('(cd {projectRoot}; {binPath} clone {repository} content)', [
            '{binPath}' => 'git',
            '{projectRoot}' => Yii::getAlias($module->root_directory),
            '{repository}' => $module->repository_url,
            ]);
        $log = $result->toString();
        //echo $log . "\n\n";

        $repo = $this->getFlywheelRepo();

        $filter = '\jacmoe\mdpages\components\ContentFileFilterIterator';
        $files = Utility::getFiles(Yii::getAlias('@pages'), $filter);

        $this->updateDB($files);

        $this->createImageSymlink();

        $this->releaseMutex();
        return self::EXIT_CODE_NORMAL;
    }

    /**
    * [createImageSymlink description]
    * @return [type] [description]
    */
    protected function createImageSymlink() {
        $image_dir = Yii::getAlias('@pages') . '/images';
        if(is_dir($image_dir)) {
            if(!is_link(Yii::getAlias('@app/web').'/images')) {
                echo "Creating images symlink\n\n";
                symlink($image_dir, Yii::getAlias('@app/web').'/images');
            }
        }
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

    /**
     * [getFlywheelRepo description]
     * @return [type] [description]
     */
    protected function getFlywheelRepo()
    {
        $module = \jacmoe\mdpages\Module::getInstance();

        if(!isset($this->flywheel_config)) {
            $config_dir = Yii::getAlias($module->flywheel_config);
            if(!file_exists($config_dir)) {
                FileHelper::createDirectory($config_dir);
            }
            $this->flywheel_config = new Config($config_dir);
        }
        if(!isset($this->flywheel_repo)) {
            $this->flywheel_repo = new Repository($module->flywheel_repo, $this->flywheel_config);
        }
        return $this->flywheel_repo;
    }

    /**
     * This is for testing out new commands
     */
    public function actionTest()
    {
        $module = \jacmoe\mdpages\Module::getInstance();
        $result = Shell::execute('(cd {projectRoot}; {binPath} log {args} {file})', [
            '{binPath}' => 'git',
            '{projectRoot}' => Yii::getAlias($module->pages_directory),
            '{args}' => '--pretty=format:%an, %ae',
            '{file}' => 'README.md',
            ]);
        $committers = $result->toString();
        $committers = explode("\n", $committers);
        array_shift($committers); // the first entry is the command
        array_pop($committers); // the last entry is the exit code

        $committer_array = array();
        foreach($committers as $committer) {
            $commits = explode(",", $committer);
            if(count($commits) == 2) {
                $committer_array[$commits[1]] = $commits[0];
            }
        }
        print_r($committer_array);

    }

}
