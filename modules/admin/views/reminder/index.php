<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\modules\admin\models\Search\ReminderSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Reminders');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="reminder-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a(Yii::t('app', 'Create Reminder'), ['create'], ['class' => 'btn btn-success']) ?>

    </p>
    <?php
    $user_id = Yii::$app->user->id;
    $role = \app\modules\admin\Module::getRoleUser($user_id);
    if($role == 'admin') { ?>
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                'title',
                [
                    'attribute' => 'status',
                    'value' => function($data){
                        return $data::getStatusText($data->status);
                    },
                    'filter' => Html::activeDropDownList(
                        $searchModel,
                        'status',
                        \app\models\App\Reminder\Reminder::getStatuses(),
                        ['class' => 'form-control', 'prompt' => 'Все']
                    )
                ],

                //'text:ntext',
                //'user_id',
                [
                    'attribute' => 'reminder',
                    'value'=>function($data){
                        return date('d.m.Y H:i', $data->reminder);
                    },
                    'label' => 'Дата напоминания',
                    'filter' => '<div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <span class="fa fa-calendar-o"></span>
                                        </span>
                                    </div>
                                    <input name="ReminderSearch[reminder]" id="picker_from" class="form-control datepicker picker__input" type="text" readonly name="picker_from" value="'.$_GET["ReminderSearch"]["reminder"].'">
                                </div>'
                ],
                [
                    'attribute' => 'user_id',
                    'value'=>function($data){
                        return \app\models\App\UserProfile::getFullName($data->user_id);
                    },
                    'label' => 'Менеджер',
                    'filter' => Html::activeDropDownList(
                        $searchModel,
                        'user_id',
                        \app\models\User::getNameAllManagers(),
                        ['class' => 'form-control', 'prompt' => 'Все']
                    )
                ],
                //'create_at',
                //'update_at',
                //'trash',

                ['class' => 'yii\grid\ActionColumn'],
            ],
        ]); ?>
    <?php } else { ?>
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                'title',
                [
                    'attribute' => 'status',
                    'value' => function($data){
                        return $data::getStatusText($data->status);
                    },
                    'filter' => Html::activeDropDownList(
                        $searchModel,
                        'status',
                        \app\models\App\Reminder\Reminder::getStatuses(),
                        ['class' => 'form-control', 'prompt' => 'Все']
                    )
                ],

                //'text:ntext',
                //'user_id',
                [
                    'attribute' => 'reminder',
                    'value'=>function($data){
                        return date('d.m.Y H:i', $data->reminder);
                    },
                    'label' => 'Дата напоминания',
                    'filter' => '<div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <span class="fa fa-calendar-o"></span>
                                        </span>
                                    </div>
                                    <input name="ReminderSearch[reminder]" id="picker_from" class="form-control datepicker picker__input" type="text" readonly name="picker_from" value="'.$_GET["ReminderSearch"]["reminder"].'">
                                </div>'
                ],
                //'create_at',
                //'update_at',
                //'trash',

                ['class' => 'yii\grid\ActionColumn'],
            ],
        ]); ?>
    <?php } ?>

</div>
<?php $this->registerCssFile(
    '/app-assets/vendors/css/pickadate/pickadate.css',
    ['depends' => [\app\assets\AppAsset::className()]]); ?>
<?php $this->registerCssFile(
    '/app-assets/vendors/css/chartist.min.css',
    ['depends' => [\app\assets\AppAsset::className()]]); ?>


<?php $this->registerJsFile(
    '/app-assets/vendors/js/chartist.min.js',
    ['depends' => [\app\assets\AppAsset::className()]]); ?>

<?php $this->registerJsFile(
    '/app-assets/vendors/js/pickadate/picker.js',
    ['depends' => [\app\assets\AppAsset::className()]]); ?>

<?php $this->registerJsFile(
    '/app-assets/vendors/js/pickadate/picker.date.js',
    ['depends' => [\app\assets\AppAsset::className()]]); ?>

<?php $this->registerJsFile(
    '/app-assets/vendors/js/pickadate/legacy.js',
    ['depends' => [\app\assets\AppAsset::className()]]); ?>
<?php $this->registerJsFile(
    '/crm/js/megafon/scripts.js',
    ['depends' => [\app\assets\AppAsset::className()]]); ?>
