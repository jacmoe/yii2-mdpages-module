<?php
namespace jacmoe\mdpages\components\inline;
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
use jacmoe\mdpages\components\snippets\Snippets;

/**
* Adds snippet inline elements
*/
trait SnippetTrait
{
    /**
    * Parses the snippet feature.
    * @marker (function: arg=val arg=val)
    */
    protected function parseSnippet($markdown)
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

    protected function renderSnippet($block)
    {
        $snip = $this->renderAbsy($block[1]);
        return $snip;
    }

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

}
