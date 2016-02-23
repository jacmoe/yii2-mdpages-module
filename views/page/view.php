<?php
use jacmoe\mdpages\helpers\Page;
/* @var $this yii\web\View */
$this->title = isset($page->title) ? $page->title : 'Untitled';
if($parts[0] != 'index')
    $this->params['breadcrumbs'][] = ['label' => 'Pages', 'url' => Page::link('index')];
if(count($parts) > 1) {
    for($i = 0; $i < count($parts)-1; $i++) {
        $this->params['breadcrumbs'][] = ['label' => $parts[$i], 'url' => ['index']];
    }
}
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="content">
    <?= $content; ?>
</div>
