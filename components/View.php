<?php
namespace jacmoe\mdpages\components;
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

/**
 * This View class overrides render and findViewFile
 * to be able to use views in the theme pathmap
 * that does not exist in the overridden module
 */
class View extends \yii\web\View {

    /**
     * Override to always pass the current context to render
     * https://github.com/yiisoft/yii2/issues/4382
	 * @inheritDoc
	 */
    public function render($view, $params = array(), $context = null)
    {
        if ($context === null) {
            $context = $this->context;
        }

        return parent::render($view, $params, $context);
    }

    /**
     * If the view is not found by normal means
     * then use the theme pathmap to find it.
	 * @inheritDoc
	 */
    protected function findViewFile($view, $context = null)
    {
        $path = $view . '.' . $this->defaultExtension;
        if ($this->theme !== null) {
            $path = $this->theme->applyTo($path);
        }
        $viewfile = parent::findViewFile($path, $context);

        return $viewfile;
    }

}
