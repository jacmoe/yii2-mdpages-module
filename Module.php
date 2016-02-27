<?php

namespace jacmoe\mdpages;

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

    public $caching_time = 60;
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

        // if (\Yii::$app instanceof \yii\web\Application) {
        //     \Yii::$app->getUrlManager()->addRules([
        //         [
        //             'class' => 'yii\web\UrlRule',
        //             'pattern' => $this->id . '/<controller:\w+>/<id:[\w_\/-]+>',
        //             'route' => $this->id . '/<controller>/view',
        //             'encodeParams' => false
        //         ],
        //         [
        //             'class' => 'yii\web\UrlRule',
        //             'pattern' => $this->id . '/<controller:\w+>/<action:\w+>/<id:[\w_\/-]+>',
        //             'route' => $this->id . '/<controller>/<action>',
        //             'encodeParams' => false
        //         ],
        //         [
        //             'class' => 'yii\web\UrlRule',
        //             'pattern' => $this->id . '/<controller:\w+>/<action:\w+>',
        //             'route' => $this->id . '/<controller>/<action>',
        //         ],
        //     ], false);
        // }

        \Yii::setAlias('@pages', $this->pages_directory);

    }
}
