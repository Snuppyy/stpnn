<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\App\Reminder\Reminder */

$this->title = Yii::t('app', 'Create Reminder');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Reminders'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="reminder-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
