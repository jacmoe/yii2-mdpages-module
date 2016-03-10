<?php
namespace jacmoe\mdpages;
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

/**
* mdpages module definition class
*/
class Module extends \yii\base\Module
{
    /**
     * Link to the Github repository where the content is stored
     * @var string  http://github.com/owner/repo.git
     */
    public $repository_url = '';

    /**
     * Github token used when calling the Github API
     * @var string
     */
    public $github_token = '';

    /**
     * Repository owner
     * @var string  The 'owner' part of http://github.com/owner/repo
     */
    public $github_owner = '';

    /**
    * Repository name
    * @var string  The 'repo' part of http://github.com/owner/repo
     */
    public $github_repo = '';

    /**
     * Repository branch
     * @var string  What branch the content repository is using
     */
    public $github_branch = 'master';

    /**
     * The directory in which the wiki content is stored
     * @var string
     */
    public $root_directory = '@runtime';

    /**
     * The extension used to indicate markdown files
     * @var string
     */
    public $page_extension = '.md';

    /**
     * The directory where the wiki content is stored
     * @var string
     */
    public $pages_directory = '@runtime/content';

    /**
     * The directory where the Flywheel database is written
     * @var string
     */
    public $flywheel_config = '@runtime/flywheel';

    /**
     * The name of the Flywheel database
     * @var string
     */
    public $flywheel_repo = 'pages';

    /**
     * Use absolute links for wiki links
     * @var string
     */
    public $absolute_wikilinks = false;

    /**
     * The amount of time in seconds to cache content
     * @var integer
     */
    public $caching_time = 60;

    /**
     * Should the Markdown page parser generate page toc?
     * @var bool
     */
    public $generate_page_toc = true;

    /**
     * Snippets for the SnippetParser
     * @var array
     */
    public $snippets = null;

    /**
     * [$feed_title description]
     * @var string
     */
    public $feed_title = 'Pages';

    /**
     * [$feed_description description]
     * @var string
     */
    public $feed_description = 'The pages feed';

    /**
     * [$feed_author description]
     * @var string
     */
    public $feed_author_email = 'noreply@yoursite.com';

    /**
     * [$feed_author_name description]
     * @var string
     */
    public $feed_author_name = 'Pages Feed';

    /**
     * [$feed_ordering description]
     * @var string
     */
    public $feed_ordering = 'updated DESC';

    /**
     * [$feed_filtering description]
     * @var [type]
     */
    public $feed_filtering = false;

    /**
     * [$feed_filter description]
     * @var array
     */
    public $feed_filter = array('published', '==', true);

    /**
     * Generate contributor data like avatars, Github URL, etc.
     * @var [type]
     */
    public $generate_contributor_data = true;

    /**
    * @inheritdoc
    */
    public $controllerNamespace = 'jacmoe\mdpages\controllers';

    /**
    * @inheritdoc
    */
    public $defaultRoute = 'page/index';

    /**
    * @inheritdoc
    */
    public function init()
    {
        parent::init();

        \Yii::configure($this, require(__DIR__ . '/config.php'));

        if (\Yii::$app instanceof \yii\console\Application) {
            $this->controllerNamespace = 'jacmoe\mdpages\commands';
        }

        $important = array(
            $this->repository_url,
            $this->github_token,
            $this->github_owner,
            $this->github_repo
        );
        $results = array_filter($important, function($v){return empty($v);});
        if(count($results) > 0) {
            throw new \yii\base\InvalidConfigException("Important configuration values have not been set.\nOne or more of the following configuration values are empty:\nrepository_url\ngithub_token\ngithub_owner\ngithub_repo\n\nPlease check your module configuration.");
        }

        \Yii::setAlias('@pages', $this->pages_directory);

    }
}
