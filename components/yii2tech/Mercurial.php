<?php
namespace jacmoe\mdpages\components\yii2tech;
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
*	Copyright (c) 2016 - 2017 Jacob Moen
*	Licensed under the MIT license
*/
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */


/**
 * Mercurial represents Mercurial (Hg) version control system.
 *
 * @see https://mercurial.selenic.com/
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class Mercurial extends VersionControlSystem
{
    /**
     * @var string path to the 'hg' bin command.
     * By default simple 'hg' is used assuming it available as global shell command.
     * It could be '/usr/bin/hg' for example.
     */
    public $binPath = 'hg';


    /**
     * Returns currently active Mercurial branch name for the project.
     * @param string $projectRoot VCS project root directory path.
     * @return string branch name.
     */
    public function getCurrentBranch($projectRoot)
    {
        $result = Shell::execute('(cd {projectRoot}; {binPath} branch)', [
            '{binPath}' => $this->binPath,
            '{projectRoot}' => $projectRoot,
        ]);
        return $result->outputLines[0];
    }

    /**
     * Checks, if there are some changes in remote repository.
     * @param string $projectRoot VCS project root directory path.
     * @param string $log if parameter passed it will be filled with related log string.
     * @return boolean whether there are changes in remote repository.
     */
    public function hasRemoteChanges($projectRoot, &$log = null)
    {
        $result = Shell::execute("(cd {projectRoot}; {binPath} incoming -b {branch} --newest-first --limit 1)", [
            '{binPath}' => $this->binPath,
            '{projectRoot}' => $projectRoot,
            '{branch}' => $this->getCurrentBranch($projectRoot),
        ]);
        $log = $result->toString();
        return $result->isOk();
    }

    /**
     * Applies changes from remote repository.
     * @param string $projectRoot VCS project root directory path.
     * @param string $log if parameter passed it will be filled with related log string.
     * @return boolean whether the changes have been applied successfully.
     */
    public function applyRemoteChanges($projectRoot, &$log = null)
    {
        $result = Shell::execute('(cd {projectRoot}; {binPath} pull -b {branch} -u)', [
            '{binPath}' => $this->binPath,
            '{projectRoot}' => $projectRoot,
            '{branch}' => $this->getCurrentBranch($projectRoot),
        ]);
        $log = $result->toString();
        return $result->isOk();
    }
}
