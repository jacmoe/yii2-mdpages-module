<?php

namespace jacmoe\mdpages\controllers;

use yii\web\Controller;
use cebe\markdown\GithubMarkdown;

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
        $result = $page->first();

        if($result != null) {

            $parser = new GithubMarkdown();

            $page_content = \Yii::getAlias('@pages') . $result->file;
            if(!file_exists($page_content)) {
                throw new \yii\web\NotFoundHttpException("Cound not find the page to render.");
            }
            $content = $parser->parse(file_get_contents($page_content));

            return $this->render('view', array('content' => $content, 'page' => $result, 'parts' => $page_parts));

        } else {
            throw new \yii\web\NotFoundHttpException("Cound not find the page to render.");
        }
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
