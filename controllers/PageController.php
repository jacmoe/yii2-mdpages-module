<?php

namespace jacmoe\mdpages\controllers;

use yii\web\Controller;

/**
 * Default controller for the `mdpages` module
 */
class PageController extends Controller
{
    /**
     * @var \jacmoe\mdpages\Module
     */
    public $module;

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
        $dir = \Yii::getAlias('@pages');
        if(!file_exists($dir)) {
            return $this->render('empty');
        }
        return $this->render('index');
    }

    /**
     * Renders a page
     * @param string $page_id id of page (url)
     * @return string
     */
    public function actionView($id)
    {
        $dir = \Yii::getAlias('@pages');
        if(!file_exists($dir)) {
            return $this->render('empty');
        }

        $page_parts = explode('/', $id);

        $file = $dir . '/' . $id . '.md';
        $metatags = array();
        if(file_exists($file)) {
            $metaParser = new \jacmoe\mdpages\components\Meta;
            $metatags = $metaParser->parse(file_get_contents($file));
        }

        return $this->render('view', array('metatags' => $metatags, 'parts' => $page_parts));
    }

}
