<?php

namespace jacmoe\mdpages\components;

class Utility {

    public static function execEnabled() {
        $disabled = explode(', ', ini_get('disable_functions'));
        return !in_array('exec', $disabled);
    }

}
