<?php

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

use yii\helpers\Html;

$this->title = 'No Pages';
?>
<div class="site-error">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="alert alert-danger">
        There are no pages to render.
    </div>

    <p>
        Please initialize the page repository using the console command:
        <pre>
            <code>
                ./yii mdpages/pages/init
            </code>
        </pre>
    </p>

</div>
