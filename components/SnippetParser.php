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
*	Copyright (c) 2016 - 2017 Jacob Moen
*   Copyright (c) 2014 Edward Akerboom
*	Licensed under the MIT license
*/

use jacmoe\mdpages\components\snippets\Snippets;
use jacmoe\mdpages\components\snippets\DefaultSnippets;
use jacmoe\mdpages\components\snippets\SnippetValue;

/**
* Extends standard Markdown syntax with pre-defined snippets
*
* @author Jacob Moen <jacmoe.dk@gmail.com>
* Originally part of infostreams/snippets = https://github.com/infostreams/snippets
* @author Edward Akerboom <github@infostreams.net>
*/
class SnippetParser
{
    protected $snippets = array();

    /**
    * Adds one snippet or a class of Snippets.
    *
    * @param string|Snippets $snippet
    * @param Callable $callable
    */
    public function add($snippet, Callable $callable=null) {
        if (is_object($snippet) && $snippet instanceof Snippets) {
            $this->addSnippetClass($snippet);
        }
        if (is_string($snippet) && is_callable($callable)) {
            // '$snippet' is a tag name: register $callable
            $this->snippets[$snippet] = $callable;
        }
    }

    /**
    * Adds snippets so that the SnippetParser has snippets to parse and render.
    *
    * @param array $settings    an array of snippets
    */
    public function addSnippets(array $snippets = null) {

        // register default snippets
        $this->add(new DefaultSnippets());

        if(!is_null($snippets)) {
            foreach ($snippets as $snippet=>$definition) {
                if ($definition instanceof Snippets) {
                    $this->add($definition);
                } else {
                    $this->add($snippet, $definition);
                }
            }
        }
    }

    /**
    * @param null $snippet
    * @return array|Callable|bool
    */
    public function get($snippet=null) {
        if (!is_null($snippet)) {
            if (array_key_exists($snippet, $this->snippets)) {
                return $this->snippets[$snippet];
            }
            return false;
        }
        return $this->snippets;
    }

    public function parse($content) {
        $snippets = $this->get();
        if (count($snippets)>0) {
            $tags = array_keys($snippets);

            // If there are tags that contain an underscore (for example: youtube_popup),
            // then add a synonym so they can be accessed with a dash instead of an underscore
            // as well (so: youtube-popup). Purely for aesthetic reasons.
            foreach ($tags as $t) {
                if (strpos($t, "_")!==false) {
                    $tags[] = str_replace('_', '-', $t);
                }
            }

            // Loop through content and check for fenced code blocks
            // and simply replace parentheses with a special marker
            // to prevent them from being processed as snippets
            $fixed_strips = array();
            $strip_matches = array();
            $strip_regexp = '/```(.*?)```/is';
            $i = 0;
            do { // try to pick a unique marker
                $marker = "_snippet_marker_" . time() . mt_rand(1000, 9999) . "_";
            } while ($i++<100 && strpos($content, $marker)!==false);
            if($strip_count = preg_match_all($strip_regexp, $content, $strip_matches) > 0) {
                $strips = $strip_matches[0];
                foreach ($strips as $strip) {
                    $fixed_strip = str_replace('(', "[{$marker}[", $strip);
                    $fixed_strip = str_replace(')', "]{$marker}]", $fixed_strip);
                    $content = str_replace($strip, $fixed_strip, $content);
                    $fixed_strips[] = $fixed_strip;
                }
            }

            $matches = array();
            $regexp = '#\((' . implode($tags, '|') . ')\s*?\:#ims'; // options: case independent, multi-line, and '/s' includes newline

            // Go and look for the closing ')'. I don't know how to do this with regexp because that
            // last ')' needs to be unquoted (i.e. NOT preceded by a '\') AND it needs to NOT be
            // part of a string or other value
            if ($count = preg_match_all($regexp, $content, $matches) > 0) {
                $tags = $matches[0];
                foreach ($tags as $i=>$slice) {
                    $tag = $matches[1][$i];
                    $max = strlen($content); // don't move this outside of 'foreach' loop
                    $start = $index = strpos($content, $slice);

                    // if the tag is no longer there, then continue
                    if ($start === false) {
                        // (this can happen if the exact same tag appears twice, and it has already
                        // been replaced earlier in the process)
                        continue;
                    }
                    $is_escaped = $between_quotes = $found_end = false;

                    while ($index++ < $max && !$found_end) {
                        $char = $content[$index];

                        if ($char == '\\' && !$is_escaped) {
                            $is_escaped = true;
                            continue;
                        }

                        if ($char == '"' && !$is_escaped) {
                            $between_quotes = !$between_quotes;
                            continue;
                        }

                        if ($char == ')' && !$is_escaped && !$between_quotes) {
                            $found_end = true;
                            continue;
                        }

                        $is_escaped = false;
                    }

                    $end = $index;

                    $full_snippet = substr($content, $start, ($end - $start));
                    $attributes = rtrim(trim(str_replace($slice, "", $full_snippet)), ')');

                    if (!array_key_exists($tag, $snippets)) {
                        // make sure synonyms map back to their original names
                        // (i.e. 'youtube-popup' => 'youtube_popup', see above)
                        $tag = str_replace("-", "_", $tag);
                    }

                    $output = $this->render($snippets[$tag], $attributes);

                    $content = str_replace($full_snippet, $output, $content);
                }
            }

            // Put the escaped fenced code blocks back to 'normal'
            foreach ($fixed_strips as $strip) {
                $fixed_strip = str_replace("[{$marker}[", '(', $strip);
                $fixed_strip = str_replace("]{$marker}]", ')', $fixed_strip);
                $content = str_replace($strip, $fixed_strip, $content);
            }

        }

        return $content;
    }

    protected function render($callable, $attributes) {
        // extract parameter names from the provided function
        if (is_array($callable)) {
            $f = new \ReflectionMethod($callable[0], $callable[1]);
        } elseif (is_a($callable, 'Closure')) {
            $f = new \ReflectionFunction($callable);
        } else {
            // file?
            return "";
        }

        $function_params = $f->getParameters();
        $parameter_names = array_map(function($f) { return $f->name; }, $function_params);

        // remove any newlines from the attributes to maintain sanity
        $attributes = strtr($attributes, "\r\n", '  ');

        // first parameter is the one directly after the tag name,
        // which means we don't have to look for it
        $look_for = array_slice($parameter_names, 1);

        // extract parameter values from the provided attributes
        if (count($look_for)>0) {
            // only match parameter values that are *NOT* part of an array
            $negative_lookahead = '(?![^{]*})(?![^\[]*\])'; // http://stackoverflow.com/a/19415051/426224
            $regexp = '#(' . implode($look_for, '|') . ')' . $negative_lookahead . ':#ims';
            $matches = preg_split($regexp, $attributes, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        } else {
            // no additional parameters (except for the required first parameter) were provided
            // -> '$matches' is everything that comes after the initial tag name
            $matches = array($attributes);
        }

        // collect the matched parameters in a 'parameter name': 'parameter value' array
        array_unshift($matches, $parameter_names[0]);
        $params_unsorted = array();
        for ($i=0; $i<count($matches); $i+=2) {
            $params_unsorted[$matches[$i]] = SnippetValue::parse($matches[$i+1]);
        }

        // put them in the order as they are used in the function,
        // and provide sane values for any missing parameters
        $params_sorted = array();
        foreach ($function_params as $i=>$p) {
            if (array_key_exists($p->name, $params_unsorted)) {
                $value = $params_unsorted[$p->name];
                if (is_string($value)) {
                    $value = trim($value);
                }
                $params_sorted[$i] = $value;
            } else {
                // php doesn't allow 'skipping' function parameters,
                // so if a value is not provided, try to get its default value
                if ($p->isDefaultValueAvailable()) {
                    $params_sorted[$i] = $p->getDefaultValue();
                } else {
                    // if no default value is available, we use 'NULL'
                    $params_sorted[$i] = NULL;
                }
            }
        }

        // finally call the function with these parameters
        return call_user_func_array($callable, $params_sorted);
    }

    private function addSnippetClass(Snippets $snippet) {
        // '$snippet' is a class that implements the 'Snippets' interface
        // -> register all public, non-magic methods as a snippet
        $methods = get_class_methods($snippet);
        foreach ($methods as $m) {
            if (substr($m,0,2)!=='__') {
                $this->snippets[$m] = array($snippet, $m);
            }
        }
    }
}
