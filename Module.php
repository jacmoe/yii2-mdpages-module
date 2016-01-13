<?php

namespace jacmoe\mdpages;

/**
 * mdpages module definition class
 */
class Module extends \yii\base\Module
{
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

        \Yii::setAlias('@pages', __DIR__);
    }
}
