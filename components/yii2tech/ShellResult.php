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


use yii\base\ErrorHandler;
use yii\base\BaseObject;

/**
 * ShellResult represents shell command execution result.
 *
 * @property string $output shell command output.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class ShellResult extends BaseObject
{
    /**
     * @var string command being executed.
     */
    public $command;
    /**
     * @var integer shell command execution exit code
     */
    public $exitCode;
    /**
     * @var array shell command output lines.
     */
    public $outputLines = [];


    /**
     * @param string $glue lines glue.
     * @return string shell command output.
     */
    public function getOutput($glue = "\n")
    {
        return implode($glue, $this->outputLines);
    }

    /**
     * @return boolean whether exit code is OK.
     */
    public function isOk()
    {
        return $this->exitCode === 0;
    }

    /**
     * @return boolean whether command execution produced empty output.
     */
    public function isOutputEmpty()
    {
        return empty($this->outputLines);
    }

    /**
     * Checks if output contains given string
     * @param string $string needle string.
     * @return boolean whether output contains given string.
     */
    public function isOutputContains($string)
    {
        return stripos($this->getOutput(), $string) !== false;
    }

    /**
     * Checks if output matches give regular expression.
     * @param string $pattern regular expression
     * @return boolean whether output matches given regular expression.
     */
    public function isOutputMatches($pattern)
    {
        return preg_match($pattern, $this->getOutput()) > 0;
    }

    /**
     * @return string string representation of this object.
     */
    public function toString()
    {
        return $this->command . "\n" . $this->getOutput() . "\n" . 'Exit code: ' . $this->exitCode;
    }

    /**
     * PHP magic method that returns the string representation of this object.
     * @return string the string representation of this object.
     */
    public function __toString()
    {
        // __toString cannot throw exception
        // use trigger_error to bypass this limitation
        try {
            return $this->toString();
        } catch (\Exception $e) {
            ErrorHandler::convertExceptionToError($e);
            return '';
        }
    }
}
