<?php

use jacmoe\mdpages\commands;

class CommandTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    private $module;

    private $pages_controller;

    protected function _before()
    {
        $this->module = Yii::$app->getModule('mdpages');
        $this->assertNotNull($this->module, "Failed to get the Pages module...");
        $this->pages_controller = new commands\PagesController('pages', require('tests/_app/config/test.php'));
        $this->assertNotNull($this->pages_controller, "Unable to construct a PagesController");
    }

    protected function _after()
    {
    }

    // tests
    public function testSomeFeature()
    {
        $config_dir = \jacmoe\mdpages\Module::getInstance()->flywheel_config;
        $this->assertEquals('@runtime/flywheel', $config_dir, "Flywheel configuration directory was not as expected.");

        $output = $this->pages_controller->actionUpdate();
        $this->assertEquals(1, $output, "Whoops");
        $output = $this->pages_controller->actionInit();
        $this->assertEquals(0, $output, "Whoops");

    }
}