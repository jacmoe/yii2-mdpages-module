<?php

namespace jacmoe\mdpages\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\helpers\FileHelper;
use cebe\markdown\GithubMarkdown;
use JamesMoss\Flywheel\Config;
use JamesMoss\Flywheel\Repository;

/**
 * Default controller for the `mdpages` module
 */
class PageController extends Controller
{
    /**
     * @var \jacmoe\mdpages\Module
     */
    public $module;

    /**
     * Flywheel Config instance
     * @var \JamesMoss\Flywheel\Config
     */
    protected $flywheel_config = null;

    /**
     * Flywheel Repository instance
     * @var \JamesMoss\Flywheel\Repository
     */
    protected $flywheel_repo = null;

    public $defaultAction = 'view';

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Renders a page
     * @param string $id id of page (url)
     * @return string
     */
    public function actionView($id = 'index')
    {
        $dir = Yii::getAlias('@pages');
        if(!file_exists($dir)) {
            return $this->render('empty');
        }

        $page_parts = explode('/', $id);

        $repo = $this->getFlywheelRepo();
        $page = $repo->query()->where('url', '==', $id)->execute();
        $result = $page->first();

        if($result != null) {

            $view_params = array_slice((array)$result, 2);
            foreach($view_params as $key => $value) {
                Yii::$app->view->params[$key] = $value;
            }
            if(isset($result->description)) {
                Yii::$app->view->registerMetaTag([
                    'name' => 'description',
                    'content' => $result->description
                ]);
            }
            if(isset($result->keywords)) {
                Yii::$app->view->registerMetaTag([
                    'name' => 'keywords',
                    'content' => $result->keywords
                ]);
            }

            $parser = new GithubMarkdown();

            $page_content = Yii::getAlias('@pages') . $result->file;
            if(!file_exists($page_content)) {
                throw new NotFoundHttpException("Cound not find the page to render.");
            }
            $content = $parser->parse(file_get_contents($page_content));

            return $this->render('view', array('content' => $content, 'page' => $result, 'parts' => $page_parts));

        } else {
            throw new NotFoundHttpException("Cound not find the page to render.");
        }
    }

    /**
     *
     * @return \JamesMoss\Flywheel\Repository
     */
    public function getFlywheelRepo()
    {
        if(!isset($this->flywheel_config)) {
            $config_dir = Yii::getAlias($this->module->flywheel_config);
            if(!file_exists($config_dir)) {
                FileHelper::createDirectory($config_dir);
            }
            $this->flywheel_config = new Config($config_dir);
        }
        if(!isset($this->flywheel_repo)) {
            $this->flywheel_repo = new Repository($this->module->flywheel_repo, $this->flywheel_config);
        }
        return $this->flywheel_repo;
    }

}
