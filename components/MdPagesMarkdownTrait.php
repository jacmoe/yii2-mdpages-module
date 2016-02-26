<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace jacmoe\mdpages\components;

/**
 * Class MdPagesMarkdownTrait
 *
 * @property TypeDoc $renderingContext
 */
trait MdPagesMarkdownTrait
{
    /**
     * @marker [[
     */
    protected function parseApiLinks($text)
    {
        return '';
    }

    /**
     * Renders API link
     * @param array $block
     * @return string
     */
    protected function renderApiLink($block)
    {
        return $block[1];
    }

    /**
     * Renders API link that is broken i.e. points nowhere
     * @param array $block
     * @return string
     */
    protected function renderBrokenApiLink($block)
    {
        return $block[1];
    }

    /**
     * Consume lines for a blockquote element
     */
    protected function consumeQuote($lines, $current)
    {
        $block = parent::consumeQuote($lines, $current);

        $blockTypes = [
            'warning',
            'note',
            'info',
            'tip',
        ];

        // check whether this is a special Info, Note, Warning, Tip block
        $content = $block[0]['content'];
        $first = reset($content);
        if (isset($first[0]) && $first[0] === 'paragraph') {
            $parfirst = reset($first['content']);
            if (isset($parfirst[0]) && $parfirst[0] === 'text') {
                foreach ($blockTypes as $type) {
                    if (strncasecmp("$type: ", $parfirst[1], $len = strlen($type) + 2) === 0) {
                        // remove block indicator
                        $block[0]['content'][0]['content'][0][1] = substr($parfirst[1], $len);
                        // add translated block indicator as bold text
                        array_unshift($block[0]['content'][0]['content'], [
                            'strong',
                            [
                                ['text', $this->translateBlockType($type)],
                            ],
                        ]);
                        $block[0]['blocktype'] = $type;
                        break;
                    }
                }
            }
        }
        return $block;
    }

    protected abstract function translateBlockType($type);

    /**
     * Renders a blockquote
     */
    protected function renderQuote($block)
    {
        $class = '';
        if (isset($block['blocktype'])) {
            $class = ' class="' . $block['blocktype'] . '"';
        }
        return "<blockquote{$class}>" . $this->renderAbsy($block['content']) . "</blockquote>\n";
    }
}
