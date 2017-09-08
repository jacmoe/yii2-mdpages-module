<?php

use jacmoe\mdpages\commands;

class CommandTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    private $module;

    protected function _before()
    {
        $this->module = Yii::$app->getModule('mdpages');
        $this->assertNotNull($this->module, "Whoops!\nThe Pages module was not loaded...");
    }

    protected function _after()
    {
    }

    // tests
    public function testSomeFeature()
    {
        $config_dir = \jacmoe\mdpages\Module::getInstance()->flywheel_config;
        $this->assertEquals('@runtime/flywheel', $config_dir, "Flywheel configuration directory was not as expected.");
    }
}