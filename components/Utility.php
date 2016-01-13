<?php

namespace jacmoe\mdpages\components;

/**
* static method to get files by directory and file filter
*
* @param        $directory
* @param string $filter
*
* @return array
*/
class Utility {

    public static function getFiles($directory, $filter = '\jacmoe\mdpages\components\GeneralFileFilterIterator') {
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
