<?php

namespace jacmoe\mdpages;

/**
 * mdpages module definition class
 */
class Module extends \yii\base\Module
{
    public $repository_url = '';

    public $page_extension = '.md';

    public $pages_directory = 'content';

    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'jacmoe\mdpages\controllers';

    /**
     * @inheritdoc
     */
    public $defaultRoute = 'default/index';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (\Yii::$app instanceof \yii\console\Application) {
            $this->controllerNamespace = 'jacmoe\mdpages\commands';
        }

        \Yii::setAlias('@pages', __DIR__ . '/' $this->$pages_directory);
    }
}
