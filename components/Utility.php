<?php

namespace jacmoe\mdpages\components;

class Utility {

    public static function page_link($page, $module_id = '') {
        if($module_id == '') {
            $module = \jacmoe\mdpages\Module::getInstance();
            if(!is_null($module)) {
                $module_id = $module->id;
            }
        }
        if($module_id != '') {
            return \yii\helpers\Url::to(array('/' . $module_id . '/page/view', 'id' => $page));
        }
    }

    public static function exec_enabled() {
        $disabled = explode(', ', ini_get('disable_functions'));
        return !in_array('exec', $disabled);
    }

    /**
    * static method to get files by directory and file filter
    *
    * @param        $directory
    * @param string $filter
    *
    * @return array
    */
    public static function getFiles($directory, $filter = '\jacmoe\mdpages\components\GeneralFileFilterIterator') {
        if(!file_exists($directory)) {
            return null;
        }
        $files = new $filter(
        new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator(
        $directory,
        \RecursiveDirectoryIterator::FOLLOW_SYMLINKS
        )
        )
    );
    $result = array();
    foreach ($files as $file) {
        /** @var \SplFileInfo $file */
        $result[] = $file->getPathname();
    }

    return $result;
}
}
