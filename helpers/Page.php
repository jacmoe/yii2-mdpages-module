<?php
namespace jacmoe\mdpages\helpers;

use yii\helpers\Url;
use yii\base\NotSupportedException;

class Page {

    /**
     * Returns a url to a page
     * @param  string $id           the page id to generate a link for
     * @param  string $module_id    if not passed the function will
     *                              try to get the module id by itself
     * @return string               the generated url
     */
    public static function url($id, $module_id = '') {
        $absolute_urls = false;
        if($module_id == '') {
            $module = \jacmoe\mdpages\Module::getInstance();
            if(!is_null($module)) {
                $module_id = $module->id;
                $absolute_urls = $module->absolute_wikilinks;
            }
        }
        if($module_id != '') {
            return Url::to(array('/' . $module_id . '/page/view', 'id' => $id), $absolute_urls);
        }
    }

    /**
     * Returns the title of a page
     * @param  string $id the id of the page to get a title for
     * @return string     page title
     */
    public static function title($id) {
        $module = \jacmoe\mdpages\Module::getInstance();
        if(!is_null($module)) {
            $controller = \Yii::$app->controller;
            if(!is_null($controller)) {
                if($controller->id == 'page') {
                    $repo = $controller->getFlywheelRepo();
                    $page = $repo->query()->where('url', '==', $id)->execute();
                    $result = $page->first();

                    if($result != null) {
                        if(isset($result->title)) {
                            return $result->title;
                        }
                    }
                    return 'page not found';
                }
                throw new NotSupportedException("Can only be used when active controller is 'page'.");
            }
        }
        throw new NotSupportedException("Can't be called outside of jacmoe\mdpages module.");
    }

    /**
     * Returns an array of pages
     * @return array an array of pages
     */
    public static function pages($order_by = '', $where = null, $limit = 50, $offset = 0) {
        $module = \jacmoe\mdpages\Module::getInstance();
        if(!is_null($module)) {
            $controller = \Yii::$app->controller;
            if(!is_null($controller)) {
                if($controller->id == 'page') {
                    $repo = $controller->getFlywheelRepo();
                    if(empty($order_by)) {
                        if(isset($where)) {
                            list($field, $operator, $value) = $where;
                            return $repo->query()->limit($limit, $offset)->where($field, $operator, $value)->execute();
                        } else {
                            return $repo->query()->limit($limit, $offset)->execute();
                        }
                    } else {
                        if(isset($where)) {
                            list($field, $operator, $value) = $where;
                            return $repo->query()->limit($limit, $offset)->where($field, $operator, $value)->orderBy($order_by)->execute();
                        } else {
                            return $repo->query()->limit($limit, $offset)->orderBy($order_by)->execute();
                        }
                    }
                }
                throw new NotSupportedException("Can only be used when active controller is 'page'.");
            }
        }
        throw new NotSupportedException("Can't be called outside of jacmoe\mdpages module.");
    }

    /**
     * [paginate description]
     * @return [type] [description]
     */
    public static function paginate($order_by = '', $where = null) {
        $offset = \Yii::$app->getRequest()->getQueryParam('page');
        if(isset($offset)) {
            return Page::pages($order_by, $where, 2, $offset - 1);
        } else {
            return Page::pages($order_by, $where, 2);
        }
    }

}
