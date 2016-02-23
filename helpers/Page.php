<?php
namespace jacmoe\mdpages\helpers;

use yii\helpers\Url;

class Page {

    /**
     * Returns a link to a page
     * @param  $id                  the page id to generate a link for
     * @param  string $module_id    if not passed the function will
     *                              try to get the module id by itself
     * @return string               the generated link
     */
    public static function link($id, $module_id = '') {
        if($module_id == '') {
            $module = \jacmoe\mdpages\Module::getInstance();
            if(!is_null($module)) {
                $module_id = $module->id;
            }
        }
        if($module_id != '') {
            return Url::to(array('/' . $module_id . '/page/view', 'id' => $id));
        }
    }
}
