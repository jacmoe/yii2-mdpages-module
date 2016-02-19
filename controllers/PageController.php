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

    /**
     * Renders a page
     * @param string $page_id id of page (url)
     * @return string
     */
    public function actionView($page_id)
    {
        $page_parts = explode('/', $page_id);

        $dir = \Yii::getAlias('@pages');
        $file = $dir . '/' . $page_id . '.md';
        $metatags = array();
        if(file_exists($file)) {
            $metaParser = new \jacmoe\mdpages\components\Meta;
            $metatags = $metaParser->parse(file_get_contents($file));
        }

        return $this->render('view', array('metatags' => $metatags, 'parts' => $page_parts));
    }

}
