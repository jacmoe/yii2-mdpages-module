<?php

namespace jacmoe\mdpages;

/**
* mdpages module definition class
*/
class Module extends \yii\base\Module
{
    public $repository_url = '';

    public $page_extension = '.md';

    public $pages_directory = '@app/content';

    /**
    * @inheritdoc
    */
    public $controllerNamespace = 'jacmoe\mdpages\controllers';

    /**
    * @inheritdoc
    */
    public $defaultRoute = 'page/index';

    /**
    * @inheritdoc
    */
    public function init()
    {
        parent::init();

        if (\Yii::$app instanceof \yii\console\Application) {
            $this->controllerNamespace = 'jacmoe\mdpages\commands';
        }

        // if (\Yii::$app instanceof \yii\web\Application) {
        //     \Yii::$app->getUrlManager()->addRules([
        //         [
        //             'class' => 'yii\web\UrlRule',
        //             'pattern' => $this->id . '/<controller:\w+>/<id:[\w_\/-]+>',
        //             'route' => $this->id . '/<controller>/view',
        //             'encodeParams' => false
        //         ],
        //         [
        //             'class' => 'yii\web\UrlRule',
        //             'pattern' => $this->id . '/<controller:\w+>/<action:\w+>/<id:[\w_\/-]+>',
        //             'route' => $this->id . '/<controller>/<action>',
        //             'encodeParams' => false
        //         ],
        //         [
        //             'class' => 'yii\web\UrlRule',
        //             'pattern' => $this->id . '/<controller:\w+>/<action:\w+>',
        //             'route' => $this->id . '/<controller>/<action>',
        //         ],
        //     ], false);
        // }

        \Yii::setAlias('@pages', $this->pages_directory);

        $dir = \Yii::getAlias($this->pages_directory);
        if(!file_exists($dir)) {
            \yii\helpers\FileHelper::createDirectory($dir);
        }
    }
}
