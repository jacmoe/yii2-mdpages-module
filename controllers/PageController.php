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

    protected $flywheel_config = null;
    protected $flywheel_repo = null;

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

        $repo = $this->getFlywheelRepo();
        $pages = $repo->findAll();
        return $this->render('index', array('pages' => $pages));
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

        $repo = $this->getFlywheelRepo();

        $page = $repo->query()->where('url', '==', $id)->execute();

        return $this->render('view', array('page' => $page->first(), 'parts' => $page_parts));
    }

    protected function getFlywheelRepo()
    {
        if(!isset($this->flywheel_config)) {
            $config_dir = \Yii::getAlias($this->module->flywheel_config);
            if(!file_exists($config_dir)) {
                \yii\helpers\FileHelper::createDirectory($config_dir);
            }
            $this->flywheel_config = new \JamesMoss\Flywheel\Config($config_dir);
        }
        if(!isset($this->flywheel_repo)) {
            $this->flywheel_repo = new \JamesMoss\Flywheel\Repository($this->module->flywheel_repo, $this->flywheel_config);
        }
        return $this->flywheel_repo;
    }

}
