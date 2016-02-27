<?php
/**
* @copyright Copyright (c) 2014 Carsten Brandt
* @license https://github.com/cebe/markdown/blob/master/LICENSE
* @link https://github.com/cebe/markdown#readme
*/

namespace jacmoe\mdpages\components\inline;

use yii\helpers\Url;
use yii\helpers\Html;
use JamesMoss\Flywheel\Config;
use JamesMoss\Flywheel\Repository;
use jacmoe\mdpages\helpers\Page;

/**
* Adds strikeout inline elements
*/
trait WikilinkTrait
{
    /**
     * Flywheel Config instance
     * @var \JamesMoss\Flywheel\Config
     */
    private $flywheel_config = null;

    /**
     * Flywheel Repository instance
     * @var \JamesMoss\Flywheel\Repository
     */
    private $flywheel_repo = null;

    /**
    * Parses the wikilink feature.
    * @marker [[
    */
    protected function parseWikilink($markdown)
    {
        if (preg_match('/^\[\[(.+?)\]\]/', $markdown, $matches)) {
            return [
                [
                    'wikilink',
                    $this->parseInline($matches[1])
                ],
                strlen($matches[0])
            ];
        }
        return [['text', $markdown[0] . $markdown[1]], 2];
    }

    protected function renderWikilink($block)
    {
        $link = $this->renderAbsy($block[1]);
        $has_title = strpos($link, '|');

        $url = '';
        $title = '';
        if ($has_title === false) {
            $url = $link;
        } else {
            $parts = explode('|', $link);
            $url = $parts[0];
            $title = $parts[1];
        }

        $module = \jacmoe\mdpages\Module::getInstance();

        $repo = $this->getFlywheelRepo($module);
        $page = $repo->query()->where('url', '==', $url)->execute();
        $result = $page->first();

        if($result != null) {
            return Html::a(empty($title) ? $result->title : $title, Url::to(['/' . $module->id . '/page/view', 'id' => $result->url], $module->absolute_wikilinks));
        } else {
            return '[[' . $link . ']]';
        }
    }

    /**
     *
     * @return \JamesMoss\Flywheel\Repository
     */
    private function getFlywheelRepo($module)
    {
        if(!isset($this->flywheel_config)) {
            $config_dir = \Yii::getAlias($module->flywheel_config);
            if(!file_exists($config_dir)) {
                FileHelper::createDirectory($config_dir);
            }
            $this->flywheel_config = new Config($config_dir);
        }
        if(!isset($this->flywheel_repo)) {
            $this->flywheel_repo = new Repository($module->flywheel_repo, $this->flywheel_config);
        }
        return $this->flywheel_repo;
    }

}
