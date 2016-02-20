<?php

namespace jacmoe\mdpages\components;

class Utility {

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
