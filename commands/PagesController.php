<?php
namespace jacmoe\mdpages\commands;
/*
* This file is part of
*     the yii2   _
*  _ __ ___   __| |_ __   __ _  __ _  ___  ___
* | '_ ` _ \ / _` | '_ \ / _` |/ _` |/ _ \/ __|
* | | | | | | (_| | |_) | (_| | (_| |  __/\__ \
* |_| |_| |_|\__,_| .__/ \__,_|\__, |\___||___/
*                 |_|          |___/
*                 module
*
*	Copyright (c) 2016 Jacob Moen
*	Licensed under the MIT license
*/

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
use jacmoe\mdpages\components\Meta;
use JamesMoss\Flywheel\Document;
use JamesMoss\Flywheel\Config;
use JamesMoss\Flywheel\Repository;
use Imagine\Gd\Imagine;
use Imagine\Image\ImageInterface;

/**
 * The command-line interface to the mdpages module
 */
class PagesController extends Controller
{
    /**
    * @var Mutex|array|string the mutex object or the application component ID of the mutex.
    * After the controller object is created, if you want to change this property, you should only assign it
    * with a mutex connection object.
    */
    public $mutex = 'yii\mutex\FileMutex';

    /**
     * Flywheel Config instance
     * @var JamesMoss\Flywheel\Config
     */
    protected $flywheel_config = null;

    /**
     * Flywheel Repository instance
     * @var JamesMoss\Flywheel\Repository
     */
    protected $flywheel_repo = null;

    /**
     * Array of possible version control systems
     * @var Object of type VersionControlSystem
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
     * Updates the Flywheel database
     * @param  array $files          List of files to update
     * @param  bool $update_updated  If true, then updates the updated post field
     */
    protected function updateDB($files, $update_updated = true) {
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

                    $this->deleteCachekeys($module, $result->url);

                    $repo->delete($result);
                }
                $file_action = 'deleted';
                $this->stdout($file_field . ' was ' . $file_action . "\n");
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

            list($contributors, $dates) = $this->committersFromFile(ltrim($values['file'],'/'), $module);

            $updated_date = 0;
            $created_date = 0;
            if(count($dates) === 1) {
                $updated_date = $dates[0];
                $created_date = $dates[0];
            } else {
                $updated_date = reset($dates);
                $created_date = end($dates);
            }

            $file_action = 'created';

            // if the post exists, delete it
            $page = $repo->query()->where('file', '==', $values['file'])->execute();
            $result = $page->first();
            if($result != null) {
                $file_action = 'updated';
                $values['created'] = $result->created;
                $values['updated'] = $update_updated ? $updated_date : $result->updated;

                $this->deleteCachekeys($module, $result->url);

                $repo->delete($result);
            }

            if($file_action == 'created') {
                $values['created'] = $created_date;
                $values['updated'] = $created_date;
            }

            $values['contributors'] = $contributors;

            $page = new Document($values);
            $repo->store($page);

            $this->stdout($file_field . ' was ' . $file_action . "\n");

        }

    }

    /**
     * [deleteCachekeys description]
     * @param  [type] $module [description]
     * @param  [type] $url    [description]
     */
    private function deleteCachekeys($module, $url) {
        $breadcrumbs_cacheKey = 'breadcrumbs-' . $url;
        $breadcrumbs_cache = $module->cache->get($breadcrumbs_cacheKey);
        if ($breadcrumbs_cache) {
            $module->cache->delete($breadcrumbs_cacheKey);
        }
        $content_cacheKey = 'content-' . $url;
        $content_cache = $module->cache->get($content_cacheKey);
        if ($content_cache) {
            $module->cache->delete($content_cacheKey);
        }
        $headings_cacheKey = 'content-' . $url . '-headings';
        $headings_cache = $module->cache->get($headings_cacheKey);
        if ($headings_cache) {
            $module->cache->delete($headings_cacheKey);
        }
    }

    /**
    * Updates the database if there are remote changes
    * @return integer success or fail
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
            $this->stdout("No changes detected\n\n", Console::FG_GREEN);
        }

        $this->releaseMutex();
        return self::EXIT_CODE_NORMAL;

    }

    /**
    * Initializes the content directory and Flywheel database from scratch
    * @return integer exit code (success or error)
    */
    public function actionInit()
    {
        $module = \jacmoe\mdpages\Module::getInstance();

        if(is_dir(Yii::getAlias($module->pages_directory))) {
            $this->stdout("Content directory already cloned - terminating.\n", Console::FG_GREEN);
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

        $files = FileHelper::findFiles(Yii::getAlias('@pages'), [
            'only' => ['*' . $module->page_extension],
        ]);

        $this->updateDB($files);

        $this->createImageSymlink();

        $this->releaseMutex();
        return self::EXIT_CODE_NORMAL;
    }


    /**
     * Rebuilds the Flywheel database without updating the updated date
     */
    public function actionRebuild() {
        $module = \jacmoe\mdpages\Module::getInstance();

        if(!is_dir(Yii::getAlias($module->pages_directory))) {
            $this->stderr("Execution terminated: the repository to update does not exist - please run init first.\n", Console::FG_RED);
            return self::EXIT_CODE_ERROR;
        }

        if (!$this->acquireMutex()) {
            $this->stderr("Execution terminated: command is already running.\n", Console::FG_RED);
            return self::EXIT_CODE_ERROR;
        }

        $files = FileHelper::findFiles(Yii::getAlias('@pages'), [
            'only' => ['*' . $module->page_extension],
        ]);

        $this->updateDB($files, false);

        $this->releaseMutex();
        return self::EXIT_CODE_NORMAL;
    }

    /**
     * Creates a symlink from the images directory in content to the public web directory
     * This is called by the Deployer script, hence it being an action
     */
    public function actionSymlink() {
        $this->createImageSymlink();
    }

    /**
    * Creates a symlink from the images directory in content to the
    * public web directory
    */
    protected function createImageSymlink() {
        $image_dir = Yii::getAlias('@pages') . '/images';
        if(is_dir($image_dir)) {
            if(!is_link(Yii::getAlias('@app/web').'/images')) {
                $this->stdout("Creating images symlink\n\n", Console::FG_GREEN);
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
     * Gets the Flywheel Repository instance. Creates it if it doesn't exist.
     * @return JamesMoss\Flywheel\Repository A handle to the repository
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
     * Calls the Github API using Curl and a secret Github token.
     * @param  string $curl_url The Github API endpoint to call
     * @param  Object $module   Handle to the active module
     * @return string           The JSON response
     */
    private function runCurl($curl_url, $module) {

        $curl_token_auth = 'Authorization: token ' . $module->github_token;

        $ch = curl_init($curl_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: Awesome-Octocat-App', $curl_token_auth));
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    /**
     * Asks Github for the list of contributors to a file
     * @param  string $file     The file to generate contributors list for
     * @param  Object $module   Handle to the active module
     * @return array            Unique list of contributors to the file in question including dates
     */
    private function committersFromFile($file, $module) {

        $curl_url = "https://api.github.com/repos/$module->github_owner/$module->github_repo/commits?path=$file";

        $output = $this->runCurl($curl_url, $module);

        //TODO: what to do if the output is invalid?
        $commits = json_decode($output);

        // $debug_dir = Yii::getAlias('@app/debug');
        // if(!is_dir($debug_dir)) {
        //     FileHelper::createDirectory($debug_dir);
        // }
        // file_put_contents($debug_dir . DIRECTORY_SEPARATOR . str_replace('/', '_', $file) . '-commits', print_r($commits, true));

        $contributors = array();
        $dates = array();

        foreach($commits as $commit) {
            $contributor = array();
            $contributor['login'] = $commit->author->login;
            $contributor['avatar_url'] = $commit->author->avatar_url;
            $contributor['html_url'] = $commit->author->html_url;
            $contributors[] = $contributor;
            $dates[] = strtotime($commit->commit->author->date);
        }
        $unique_contributors = $this->unique_multidim_array($contributors, 'login');

        $this->createAvatar($unique_contributors, $module);
        sort($dates, SORT_NUMERIC);
        return [$unique_contributors, $dates];
    }

    /**
     * [createAvatar description]
     * @param  [type] $contributors [description]
     * @param  [type] $module       [description]
     * @return [type]               [description]
     */
    private function createAvatar($contributors, $module) {

        $avatars_dir = Yii::getAlias('@app/web') . DIRECTORY_SEPARATOR . 'avatars';
        if(!is_dir($avatars_dir)) {
            FileHelper::createDirectory($avatars_dir);
        }
        $imagine = new Imagine();
        $mode = ImageInterface::THUMBNAIL_OUTBOUND;
        $size    = new \Imagine\Image\Box(24, 24);

        foreach($contributors as $contributor) {
            $imagine->open($contributor['avatar_url'])
                ->thumbnail($size, $mode)
                ->save($avatars_dir . DIRECTORY_SEPARATOR . $contributor['login'] . '.png');
        }

    }

    /**
     * Produces an array with unique entries from an array with possible duplicate entries
     * @param  array $array Array with possible duplicate entries
     * @param  string $key  The key to use when determining what is duplicate
     * @return array        An array of unique entries
     */
    private function unique_multidim_array($array, $key)
    {
        $temp_array = array();
        $i = 0;
        $key_array = array();

        foreach($array as $val) {
            if (!in_array($val[$key], $key_array)) {
                $key_array[$i] = $val[$key];
                $temp_array[$i] = $val;
            }
            $i++;
        }
        return $temp_array;
    }

    /**
     * Removes content and data directories
     */
    public function actionClearAll()
    {
        $module = \jacmoe\mdpages\Module::getInstance();

        if($this->confirm('Delete content and data directories ?')) {
            FileHelper::removeDirectory(Yii::getAlias($module->pages_directory));
            FileHelper::removeDirectory(Yii::getAlias($module->flywheel_config));
            FileHelper::removeDirectory(Yii::getAlias('@app/web') . DIRECTORY_SEPARATOR . 'avatars');
            $this->stdout("\nContent and data and avatars directories deleted.\n", Console::FG_GREEN);
        } else {
            $this->stdout("Nothing was deleted - command interrupted.\n", Console::FG_GREEN);
        }
    }

    // /**
    //  * This is for testing out new commands
    //  */
    // public function actionTest()
    // {
    // }

}
