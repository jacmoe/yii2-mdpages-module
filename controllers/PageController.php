<?php

namespace jacmoe\mdpages\controllers;

use yii\web\Controller;

/**
 * Default controller for the `mdpages` module
 */
class PageController extends Controller
{
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }
}
