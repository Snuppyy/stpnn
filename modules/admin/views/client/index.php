<?php

use yii\helpers\Html;
use app\widgets\CrmGrid as GridView;
use \app\models\User;

/* @var $this yii\web\View */
/* @var $searchModel app\modules\admin\models\Search\ClientSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $users array */
/* @var $role string */

$this->title = Yii::t('app', 'Clients');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="client-index card">
    <div class="card-header">
        <div class="card-title-wrap bar-danger">
            <div class="text-bold-600 black font-medium-2 ">
                <?= Html::encode($this->title) ?>
                <?php if( $role ==  'admin') { ?>
                    <span class="dropdown mr-2">
                        <button class="mb-0 btn btn-outline-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-cloud-download"></i>
                        </button>
                        <span class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a href="#" class="dropdown-item copyAllEmails" title="Скопировать все E-mail" data-user="-1">Все</a>
                            <?php foreach ($users as $user) { ?>
                                <a href="#" class="dropdown-item copyAllEmails" data-user="<?= $user['ID']; ?>"><?= $user['full_name']; ?></a>
                            <? } ?>
                        </span>
                    </span>
                    <span class="dropdown mr-2">
                        <button class="mb-0 btn btn-outline-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="ft-phone-outgoing"></i>
                        </button>
                        <span class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a href="#" class="dropdown-item copyAllPhones" title="Скопировать все телефоны" data-user="-1">Все</a>
                            <?php foreach ($users as $user) { ?>
                                <a href="#" class="dropdown-item copyAllPhones" data-user="<?= $user['ID']; ?>"><?= $user['full_name']; ?></a>
                            <? } ?>
                        </span>
                    </span>
                <?php } ?>
                <span class="d-block font-small-2 black my-2 form-group">
                    <input type="checkbox" class="switchery switcheryLongClientFilter" name="ClientSearch[long]" value="1"<?= $_GET['ClientSearch']['long'] == 1 ? ' checked' : ''; ?>/>
                    <label class="ml-1">Долго.кл.</label>
                </span>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="card-block">
            <div class="table-responsive">
                <?php if($role == User::STATUS_ACT){ ?>
                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'filterModel' => $searchModel,
                        'columns' => [
                            [
                                'attribute' => 'create_at',
                                'headerOptions' => ['width' => '10%'],
                                'filter' => '',
                                'format' => 'raw',
                                'value'=>function($data){
                                    /** @var $data \app\models\App\Client\Client */
                                    ob_start(); ?>
                                    <span class="d-block font-small-2 black my-2 form-group">
                                        <input data-id="<?= $data->id; ?>" type="checkbox" class="switchery switcheryLongClient"
                                               data-size="sm"<?= !empty($data->long) ? ' checked' : '' ?>/>
                                        <label class="ml-1">Долго.кл.</label>
                                    </span>
                                    <? $h = ob_get_clean();
                                    return "ID-".$data->id."\n".date('d.m.Y',$data->create_at)."\n".$h;
                                },
                            ],
                            [
                                'attribute' => 'name_firm',
                                'format' => 'html',
                                'value'=>function($data){
                                    return $data->getNameOrg();
                                },
                                //'headerOptions' => ['width' => '20%'],
                            ],
                            [
                                'attribute' => 'contact_info',
                                'format' => 'html',
                                'content' => function($data){
                                    return $data->getContactInfo();
                                },
                                'class' => 'app\components\CrmColumn',
                                //'headerOptions' => ['width' => '30%'],
                            ],
                            [
                                'attribute' => 'count_request',
                                'value' => function($data){
                                    return $data->getCountRequest();
                                },
                                'filter' => '',
                                //'headerOptions' => ['width' => '20%'],
                            ],
                            [
                                'attribute' => 'income',
                                'format' => 'html',
                                'content' => function($data){
                                    return number_format($data->getIncome(),2,'.',' ').' &#8381;';
                                },
                                'filter' => '',
                                'contentOptions' => ['class' => 'nowrap'],
                            ],
                            //'date_birthday',
                            //'user_id',
                            //'create_at',
                            //'update_at',
                            //'trash',

                            ['class' => 'yii\grid\ActionColumn'],
                        ],
                        'pager' => [
                            'class' => 'app\widgets\LinkPager',
                        ],
                        'tableOptions' => ['class' => 'table table-hover table-xl mb-2']
                    ]); ?>
                <?php }else{ ?>
                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'filterModel' => $searchModel,
                        'columns' => [
                            [
                                'attribute' => 'create_at',
                                'headerOptions' => ['width' => '10%'],
                                'filter' => '',
                                'format' => 'raw',
                                'value'=>function($data){
                                    /** @var $data \app\models\App\Client\Client */
                                    ob_start(); ?>
                                    <span class="d-block font-small-2 black my-2 form-group">
                                        <input data-id="<?= $data->id; ?>" type="checkbox" class="switchery switcheryLongClient"
                                               data-size="sm"<?= !empty($data->long) ? ' checked' : '' ?>/>
                                        <label class="ml-1">Долго.кл.</label>
                                    </span>
                                    <? $h = ob_get_clean();
                                    return "ID-".$data->id."\n".date('d.m.Y',$data->create_at)."\n".$h;
                                },
                            ],
                            [
                                'attribute' => 'name_firm',
                                'format' => 'html',
                                'value'=>function($data){
                                    return $data->getNameOrg();
                                },
                                //'headerOptions' => ['width' => '20%'],
                            ],
                            [
                                'attribute' => 'contact_info',
                                'format' => 'html',
                                'content' => function($data){
                                    return $data->getContactInfo();
                                },
                                'class' => 'app\components\CrmColumn',
                                //'headerOptions' => ['width' => '30%'],
                            ],
                            [
                                'attribute' => 'count_request',
                                'value' => function($data){
                                    return $data->getCountRequest();
                                },
                                'filter' => '',
                                //'headerOptions' => ['width' => '20%'],
                            ],
                            [
                                'attribute' => 'managers',
                                'format' => 'html',
                                'content' => function($data){
                                    return $data->getManagers();
                                },
                                'filter' => '',
                                //'headerOptions' => ['width' => '20%'],
                            ],
                            [
                                'attribute' => 'income',
                                'format' => 'html',
                                'content' => function($data){
                                    return number_format($data->getIncome(),2,'.',' ').' &#8381;';
                                },
                                'filter' => '',
                                'contentOptions' => ['class' => 'nowrap'],
                            ],
                            //'date_birthday',
                            //'user_id',
                            //'create_at',
                            //'update_at',
                            //'trash',

                            [
                                'class' => 'yii\grid\ActionColumn',
                                'buttons' => [
                                    'del' => function($url,$model){
                                        //print_r($model);
                                        return \yii\helpers\Html::a( '<i class="ft-trash-2"></i>', \yii\helpers\Url::to(['/admin/client/delete']),
                                            [
                                                'title' => Yii::t('app', 'Удалить'),
                                                'data-pjax' => '0',
                                                'data-id' => $model->id,
                                                'data-income' => number_format($model->getIncome(),2,'.',' '),
                                                'class' => 'deleteClient'
                                            ]);
                                    }
                                ],
                                'template' => '{del}'
                            ],
                        ],
                        'pager' => [
                            'class' => 'app\widgets\LinkPager',
                        ],
                        'tableOptions' => ['class' => 'table table-hover table-xl mb-2']
                    ]); ?>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <!--<p>
        <?/*= Html::a(Yii::t('app', 'Create Client'), ['create'], ['class' => 'btn btn-success']) */?>
    </p>-->
</div>
    <textarea id="copyWrapEmails" style="width: 0;height: 0"></textarea>

<?php $this->registerCssFile(
    '/app-assets/vendors/css/switchery.min.css',
    ['depends' => [\yii\web\JqueryAsset::className()]]); ?>
<?php $this->registerJsFile(
    '/app-assets/vendors/js/switchery.min.js',
    ['depends' => [\yii\web\JqueryAsset::className()]]); ?>
<?php $this->registerJsFile(
    '/app-assets/js/switch.min.js',
    ['depends' => [\yii\web\JqueryAsset::className()]]); ?>

<?php $this->registerJsFile(
    '/crm/js/client/index.js',
    ['depends' => [\app\assets\AppAsset::className()]]); ?>

