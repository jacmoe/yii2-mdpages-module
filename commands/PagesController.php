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
        echo "Hi\n";
        //$git = Yii::createObject('jacmoe\mdpages\components\yii2tech\Git');

        $result = Shell::execute('(cd {projectRoot}; {binPath} -a)', [
            '{binPath}' => 'ls',
            '{projectRoot}' => '.',
        ]);
        echo $result;

    }
}
