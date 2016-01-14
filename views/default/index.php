<?php
/* @var $this yii\web\View */
$this->title = 'Pages';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="mdpages-default-index">
    <h1><?= $this->context->action->uniqueId ?></h1>
    <p>
        This is the view content for action "<?= $this->context->action->id ?>".
        The action belongs to the controller "<?= get_class($this->context) ?>"
        in the "<?= $this->context->module->id ?>" module.
    </p>
    <p>
        You may customize this page by editing the following file:<br>
        <code><?= __FILE__ ?></code>
    </p>
</div>
<?php
$metaParser = new jacmoe\mdpages\components\Meta;
$files = jacmoe\mdpages\components\Utility::getFiles(\Yii::getAlias('@pages'));
if(!is_null($files)) {
  foreach($files as $file) {
      echo $file . "<br>";
      $metatags = $metaParser->parse(file_get_contents($file));
      echo '<pre>';
      print_r($metatags);
      echo '</pre>';
  }
}
?>
