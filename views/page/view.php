<?php
use jacmoe\mdpages\helpers\Page;
use yii\helpers\Html;
/* @var $this yii\web\View */
$this->title = isset($page->title) ? $page->title : 'Untitled';
?>
<div class="content">
    <?= $content; ?>
</div>
<div class="content">
    <pre>
    <?php
        print_r(Page::paginate('created DESC'));
    ?>
    </pre>
</div>
<div class="content">
    <hr>
    <h3>Contributors to this page</h3>
    <?php
        foreach($page->contributors as $contributor) {
            echo Html::a(Html::img($contributor->avatar_url, array('width' => '32px', 'title' => $contributor->name)), $contributor->html_url);
        }
    ?>
</div>
