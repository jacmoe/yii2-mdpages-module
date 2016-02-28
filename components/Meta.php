<?php
namespace jacmoe\mdpages\components;
/*
* This file is part of
*     the yii2   _
*  _ __ ___   __| |_ __   __ _  __ _  ___  ___
* | '_ ` _ \ / _` | '_ \ / _` |/ _` |/ _ \/ __|
* | | | | | | (_| | |_) | (_| | (_| |  __/\__ \
* |_| |_| |_|\__,_| .__/ \__,_|\__, |\___||___/
*                 |_|          |___/
*                 module
*
*	Copyright (c) 2016 Jacob Moen
*	Licensed under the MIT license
*/

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class Meta {
    /**
    * parse the content and extract meta information
    *
    * @param string $rawData raw page data
    * @return array with key/value store
    */
    public function parse($rawData) {
        $rawData = trim($rawData);

        $start = substr($rawData, 0, 4);
        if ($start === '<!--') {
            $stop = '-->';
        } elseif (substr($start, 0, 2) === '/*') {
            $start = '/*';
            $stop = '*/';
        } else {
            return [];
        }

        $meta = trim(substr($rawData, strlen($start), strpos($rawData, $stop) - (strlen($stop) + 1)));
        try {
            $meta = Yaml::parse($meta);//$this->parsePhileFormat($meta);
            $meta = ($meta === null) ? [] : $this->convertKeys($meta);
        } catch (ParseException $e) {
            printf("Unable to parse the YAML string: %s<br>", $e->getMessage());
        }
        return $meta;
    }

    /**
    * convert meta data keys
    *
    * Creates "compatible" keys allowing easy access e.g. as template var.
    *
    * Conversions applied:
    *
    * - lowercase all chars
    * - replace special chars and whitespace with underscore
    *
    * @param array $meta meta-data
    * @return array
    */
    protected function convertKeys(array $meta) {
        $return = [];
        foreach ($meta as $key => $value) {
            if (is_array($value)) {
                $value = $this->convertKeys($value);
            }
            $newKey = strtolower($key);
            $newKey = preg_replace('/[^\w+]/', '_', $newKey);
            $return[$newKey] = $value;
        }
        return $return;
    }

    /**
    * Phile meta format parser.
    *
    * @param string $string unparsed meta-data
    * @return array|null array with meta-tags; null: on meta-data found
    *
    * @deprecated since 1.6.0 Phile is going to switch to YAML
    */
    protected function parsePhileFormat($string) {
        if (empty($string)) {
            return null;
        }
        $meta = [];
        $lines = explode("\n", $string);
        foreach ($lines as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $parts = array_map('trim', $parts);
            $meta[$parts[0]] = $parts[1];
        }
        return $meta;
    }

}
