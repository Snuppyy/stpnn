<?php
use app\components\AppFunctions as AP;
use \yii\helpers\Url;
use \app\models\App\UserSalary as US;
use \app\widgets\Components\User\TimeLine as TL;
use \app\models\App\UserPremiums as UP;
use \yii\widgets\ActiveForm;
use \yii\helpers\Html;
use \yii\helpers\VarDumper;
use \app\models\App\Request\Requests;
/* @var $this yii\web\View */
/* @var $start integer */
/* @var $end integer */
/* @var $month integer */
/* @var $month_note integer */
/* @var $year integer */
/* @var $requests array */
/* @var $received array */
/* @var $managers array */
/* @var $seccessed array */
/* @var $premium \app\models\App\UserPremiums */
$sale = $buy = 0;
$rate_premium = 0.25;
$rate_bonus = 0.124;
$f_date_start = date('Ymd', $start);
if($f_date_start > '20200500'){
    $rate_premium = 0.30;
}
if(!empty($requests)){
    foreach ($requests as $request) {
        if($request['status_work'] == Requests::STATUS_WORK_SUCCESS && $request['date_end'] == 0)
            continue;
        if(!empty($request['calcs'])){
            foreach ($request['calcs'] as $calcs) {
                if(!empty($calcs['items'])){
                    foreach ($calcs['items'] as $item) {
                        $sale += $item['sale'];
                        $buy += $item['buy'];
                    }
                }
            }
        }
    }
}
$salary = 0;
if(!empty($managers)){
    $manager_ids = array_column($managers,'ID');
    $salary_managers = US::getUsersSalary($manager_ids,$start);
    $salary = array_sum(array_column($salary_managers,'value'));
}
$bonuses = 0;
var_dump($totals);
if(!empty($seccessed)){
    $totals = array_column($seccessed,'totals');
    $total_buys = array_sum(array_column($totals,'buy'));
    $total_sale = array_sum(array_column($totals,'sale'));
    if($f_date_start > '20230300'){
        $bonuses = ($total_sale-$total_buys)*0.1;
    } else {
        $bonuses = AP::getBonus($total_buys,$total_sale);
    }
}
$this->title = 'Расчет зарплат менеджерам';
?>
    <style>@media print {
        /*@page {
            size: landscape;
        }
        body {
            writing-mode: tb-rl;
        }*/
        /*.main-panel {
            !*-webkit-transform: rotate(-90deg);
            -moz-transform:rotate(-90deg);*!
            filter:progid:DXImageTransform.Microsoft.BasicImage(Rotation=1);
            writing-mode: tb-rl;
        }
        .custom-select {
            -webkit-transform: rotate(90deg);
            -moz-transform:rotate(90deg);
        }*/
    }</style>
<h1 class="title-box mt-3 mb-1">Расчет зарплат</h1>
<form class="form" action="<?= Url::to(['/admin/salary']); ?>" method="get">
    <div class="row">
        <div class="col-3">
            <?= AP::getListMonth($month); ?>
        </div>
        <div class="col-2">
            <?= AP::getListYear($year); ?>
        </div>
        <div class="col-2">
            <button class="btn btn-flat btn-primary btnChange mb-0 font-medium-3 px-0" style="display: none;">
                <i class="ft-arrow-right"></i>
            </button>
        </div>
    </div>
</form>
<div class="row">
    <div class="col-12 col-md-4">
        <div class="card"
             data-toggle="tooltip"
             data-placement="top"
             data-original-title="Сумма сделок созданных за этот месяц">
            <div class="card-body">
                <div class="px-3 py-3">
                    <div class="row no-gutters">
                        <div class="col-auto align-self-center">
                            <div class="row no-gutters">
                                <div class="col-auto align-self-center">
                                    <i class="icon-diamond info font-large-1"></i>
                                </div>
                                <div class="col-7 align-self-center">
                                    <div class="font-medium-1 black text-bold-600 ml-1 line-height-1">
                                        Сумма сделок
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col text-right align-self-center">
                            <span class="my-0 text-bold-600 font-medium-1 line-height-1">
                                <?= number_format($sale,2,'.',' '); ?> &#8381
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="px-3 py-3">
                    <div class="row align-self-center">
                        <div class="col-7">
                            <div class="row no-gutters align-self-center">
                                <div class="col-auto">
                                    <i class="icon-badge danger font-large-1"></i>
                                </div>
                                <div class="col-8">
                                    <div class="font-medium-1 black text-bold-600 ml-1 line-height-1">
                                        Сколько оплачено
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-5 text-right align-self-center">
                            <span class="my-0 text-bold-600 font-medium-1 line-height-1">
                                <?= number_format($received,2,'.',' '); ?> &#8381
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card"
             data-toggle="tooltip"
             data-placement="top"
             data-original-title="Закупочная цена">
            <div class="card-body">
                <div class="px-3 py-3">
                    <div class="row no-gutters">
                        <div class="col-auto align-self-center">
                            <div class="row no-gutters">
                                <div class="col-auto align-self-center">
                                    <i class="icon-wallet success font-large-1"></i>
                                </div>
                                <div class="col-8 align-self-center">
                                    <div class="font-medium-1 black text-bold-600 ml-1 line-height-1">
                                        Общая прибыль
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-5 text-right align-self-center">
                            <span class="my-0 text-bold-600 font-medium-1 line-height-1">
                                <?= number_format($total_sale-$total_buys,2,'.',' '); ?> &#8381
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12 col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="media align-items-stretch">
                    <div class="p-2 text-center bg-info rounded-left pt-3">
                        <i class="icon-diamond font-large-2 text-white"></i>
                    </div>
                    <div class="p-2 media-body">
                        <h6>Зарплата менеджеров</h6>
                        <h5 class="text-bold-400 mb-0">
                            <?= number_format($salary,0,'.',' '); ?> &#8381
                        </h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="media align-items-stretch">
                    <div class="p-2 text-center bg-warning rounded-left pt-3">
                        <i class="icon-diamond font-large-2 text-white"></i>
                    </div>
                    <div class="p-2 media-body">
                        <h6>Процетный доход менеджеров</h6>
                        <h5 class="text-bold-400 mb-0">
                            <?= number_format($bonuses,0,'.',' '); ?> &#8381
                        </h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if (!empty($managers)) {
    $user_success_ids = [];
    if(!empty($seccessed)){
        $req_user_ids = array_column($seccessed,'user_id');
    }
    foreach ($managers as $key => $manager) {
        //print_r($manager);
        $total_user = $bonus_manager = $total_buys = $total_sale = 0;

        //Получаем зарпалату менеджера
        $_salary = US::getUsersSalary([$manager['ID']],$start);
        if(!empty($_salary[0]['value'])){
            $total_user = $_salary[0]['value'];
        }
        //Получаем бонус менеждера
        $request_keys = !empty($req_user_ids)
            ? array_keys($req_user_ids, $manager['ID'])
            : [];
        $seccessed_user = [];
        if(!empty($request_keys)){
            $seccessed_user = AP::array_slice_assoc($seccessed,$request_keys);
            if(!empty($seccessed_user)){
                $totals = array_column($seccessed_user,'totals');
                $total_buys = array_sum(array_column($totals,'buy'));
                $total_sale = array_sum(array_column($totals,'sale'));
                if($f_date_start > '20230300'){
                    $bonus_manager = ($total_sale-$total_buys)*0.1;
                } else {
                    $bonus_manager = AP::getBonus($total_buys,$total_sale);
                }
            }
        }

        //Получаем штрафы/премии
        $premiums = UP::getPremiumsOfUserDate($manager['ID'],$start,$end);
        $_premium_total = 0;
        if(!empty($premiums)){
            foreach ($premiums as $_premium) {
                if($_premium->color == UP::COLOR_DEFAULT){
                    $_premium_total -= $_premium->value;
                    $total_user -= $_premium->value;
                } elseif($_premium->color == UP::COLOR_SUCCESS){
                    $_premium_total += $_premium->value;
                    $total_user += $_premium->value;
                }
            }
        }

        $total_user += $bonus_manager;
        $_tmp_total = ($total_sale-$total_buys);
        ?>
        <div class="card">
            <div id="headingManager<?= $key; ?>" class="card-header">
                <a data-toggle="collapse" href="#collapse<?= $key; ?>" aria-expanded="true" aria-controls="collapse<?= $key; ?>" class="card-title lead collapsed text-bold-600 font-medium-1">
                    <?php if(!empty($manager['avatar'])){ ?>
                        <span class="avatar mr-2">
                            <img src="<?= $manager['avatar']; ?>" alt="">
                        </span>
                    <?php } ?>
                    <span><?= $manager['full_name']; ?></span>
                </a>
                <a
                    class="btn btn-flat float-right btn-warning mb-0"
                    target="_blank"
                    href="<?= Url::to(['/admin/user/view','id' => $manager['ID']]) ?>">
                    <i class="ft-edit-3"></i>
                </a>
            </div>
            <div id="collapse<?= $key; ?>" role="tabpanel" aria-labelledby="headingManager<?= $key; ?>" class="collapse bg-white show" aria-expanded="false">
                <div class="card-body pb-3">
                    <div class="row">
                        <div class="col-12 col-md-4">
                            <div class="card my-0">
                                <div class="card-header pt-0">
                                    <div class="card-title-wrap bar-primary">
                                        Зарплата менеджера
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="card-block px-0 pt-2 pb-0">
                                        <div class="row no-gutters">
                                            <div class="col-7 text-right align-self-center">
                                                <div class="mr-2">Оклад на карту</div>
                                            </div>
                                            <div class="col-5">
                                                <span class="font-medium-4 black align-self-center">
                                                    <?= !empty($_salary[0]['value']) ?
                                                        number_format($_salary[0]['value'],0,',',' '): 0; ?> &#8381
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-block px-0 pt-2 pb-0">
                                        <div class="row no-gutters">
                                            <div class="col-7 text-right align-self-center">
                                                <div class="mr-2">Зарплата от сделок</div>
                                            </div>
                                            <div class="col-5">
                                                <span class="font-medium-4 black align-self-center">
                                                    <?= number_format($bonus_manager,0,',',' '); ?> &#8381
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-block px-0 pt-2 pb-0">
                                        <div class="row no-gutters">
                                            <div class="col-7 text-right align-self-center">
                                                <div class="mr-2">Премии/Штрафы</div>
                                            </div>
                                            <div class="col-5">
                                                <span class="font-medium-4 black align-self-center">
                                                    <?= number_format($_premium_total,0,',',' '); ?> &#8381
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-block px-0 pt-2 pb-0">
                                        <div class="row no-gutters">
                                            <div class="col-7 text-right align-self-center">
                                                <div class="mr-2">Итого</div>
                                            </div>
                                            <div class="col-5">
                                                <span class="font-medium-4 black align-self-center">
                                                    <?= number_format($total_user,0,',',' '); ?> &#8381
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-block px-0 pt-2 pb-0">
                                        <div class="row no-gutters">
                                            <div class="col-7 text-right align-self-center">
                                                <div class="mr-2">Общая прибль от сделок</div>
                                            </div>
                                            <div class="col-5">
                                                <span class="font-medium-4 black align-self-center">
                                                    <?= number_format($_tmp_total,0,',',' '); ?> &#8381
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-block px-0 pt-2 pb-0">
                                        <div class="row no-gutters">
                                            <div class="col-7 text-right align-self-center">
                                                <div class="mr-2">Общая прибль от сделок-30%</div>
                                            </div>
                                            <div class="col-5">
                                                <span class="font-medium-4 black align-self-center">
                                                    <?= number_format($_tmp_total-$_tmp_total*$rate_premium,0,',',' '); ?> &#8381
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-block px-0 pt-3 pb-0">
                                        <div class="row no-gutters">
                                            <div class="col-12 text-center align-self-center">
                                                <?php if($f_date_start > '20200500'){ ?>
                                                <div class="mr-2">
                                                    План больше 500тыс + 10 000р.
                                                    <i class="<?= $_tmp_total-$_tmp_total*$rate_premium >= 500000 ? 'ft-check success'
                                                        : 'danger ft-x';
                                                    ?>"></i>
                                                </div>
                                                <div class="mr-2">
                                                    План больше 700тыс + 20 000р.
                                                    <i class="<?= $_tmp_total-$_tmp_total*$rate_premium >= 700000 ? 'ft-check success'
                                                        : 'danger ft-x';
                                                    ?>"></i>
                                                </div>
                                                <div class="mr-2">
                                                    План больше 1млн. + 30 000р.
                                                    <i class="<?= $_tmp_total-$_tmp_total*$rate_premium >= 1000000 ? 'ft-check success'
                                                        : 'danger ft-x';
                                                    ?>"></i>
                                                </div>
                                                <div class="mr-2">
                                                    План больше 1,5млн. + 40 000р.
                                                    <i class="<?= $_tmp_total-$_tmp_total*$rate_premium >= 1500000 ? 'ft-check success'
                                                        : 'danger ft-x';
                                                    ?>"></i>
                                                </div>
                                                <?php }else{ ?>
                                                <div class="mr-2">
                                                    План больше 400тыс + 10 000р.
                                                    <i class="<?= $_tmp_total-$_tmp_total*$rate_premium >= 400000 ? 'ft-check success'
                                                        : 'danger ft-x';
                                                    ?>"></i>
                                                </div>
                                                <div class="mr-2">
                                                    План больше 600тыс + 20 000р.
                                                    <i class="<?= $_tmp_total-$_tmp_total*$rate_premium >= 600000 ? 'ft-check success'
                                                        : 'danger ft-x';
                                                    ?>"></i>
                                                </div>
                                                <div class="mr-2">
                                                    План больше 800тыс + 30 000р.
                                                    <i class="<?= $_tmp_total-$_tmp_total*$rate_premium >= 800000 ? 'ft-check success'
                                                        : 'danger ft-x';
                                                    ?>"></i>
                                                </div>
                                                <div class="mr-2">
                                                    План больше 1млн. + 40 000р.
                                                    <i class="<?= $_tmp_total-$_tmp_total*$rate_premium >= 1000000 ? 'ft-check success'
                                                        : 'danger ft-x';
                                                    ?>"></i>
                                                </div>
                                                <div class="mr-2">
                                                    План больше 1,2млн. + 50 000р.
                                                    <i class="<?= $_tmp_total-$_tmp_total*$rate_premium >= 1200000 ? 'ft-check success'
                                                        : 'danger ft-x';
                                                    ?>"></i>
                                                </div>
                                                <div class="mr-2">
                                                    План больше 1,5млн. + 60 000р.
                                                    <i class="<?= $_tmp_total-$_tmp_total*$rate_premium >= 1500000 ? 'ft-check success'
                                                        : 'danger ft-x';
                                                    ?>"></i>
                                                </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="card my-0">
                                <div class="card-header pt-0">
                                    <div class="card-title-wrap bar-danger">
                                        Премии и штрафы
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="card-block px-0 pt-2 pb-0">
                                        <?= TL::widget([
                                            'items' => $premiums,
                                            'button' => [
                                                'name' => 'Добавить',
                                                'items' => UP::getButton()
                                            ],
                                            'edit' => true,
                                            'delete' => true,
                                            'user_id' => $manager['ID']
                                        ]); ?>
                                    </div>
                                </div>
                                <div class="card-header pb-0">
                                    <div class="card-title-wrap bar-danger">
                                        Примечания
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="card-block px-0 pt-2 pb-0">
                                        <div class="form-group">
                                            <textarea name="" class="form-control" rows="5"><?= !empty($user_notes[$manager['ID']])
                                                    ? $user_notes[$manager['ID']]
                                                    : ''; ?></textarea>
                                            <button data-period="<?= $year.$month_note; ?>"
                                                    data-user="<?= $manager['ID']; ?>"
                                                    type="button"
                                                    class="userNotesSave">
                                                <i class="ft-save"></i>
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php if(!empty($seccessed_user)) { ?>
                        <div class="col-12 col-md-4">
                            <div class="card my-0">
                                <div class="card-header pt-0">
                                    <div class="card-title-wrap bar-success">
                                        Список закрытых заявок
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($seccessed_user as $s_user) {
                                        $client = \app\models\App\Client\Client::findOne([
                                            'id' => $s_user['client_id']]);
                                        $org = $client->organization;?>
                                    <div class="card-block px-0 pt-2 pb-0">
                                        <a href="<?= Url::to(['/admin/request/view','id' => $s_user['id']]); ?>"
                                           target="_blank"
                                           class="d-block">
                                            <?= !empty($org) ? $org->title : ''; ?>
                                            <?php $str = '';
                                            $upd = \app\models\App\Request\RequestsUpd::findOne(['requests_id' =>
                                            $s_user['id']]);
                                            if(!empty($upd)){
                                                echo !empty($org) ? ' ' : '';
                                                echo '№'.$upd->number.', '. date('d.m.Y', $upd->date);
                                            } ?>
                                        </a>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    <?php }
} ?>
<?php ob_start(); ?>
    <div class="modal fade text-left" id="modaladdPremium" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content card">
                <div class="modal-header card-header">
                    <div class="card-title-wrap bar-primary">
                        <span class="title-box">Премия/Штраф</span>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body">
                    <?php $form = ActiveForm::begin([
                        'options' => [
                            'autocomplete' => "off",
                            'id' => 'formAddPremium'
                        ],
                        'action' => \yii\helpers\Url::to(['/admin/premium/add'])
                    ]); ?>
                        <?= $form->field($premium,'user_id',[
                            'options' => [
                                'tag' => false
                            ],
                            'template' => '{input}'
                        ])->input('hidden',[
                            'value' => '',
                            'class' => 'setUserID',
                        ]);?>
                        <?= $form->field($premium,'color',[
                            'options' => [
                                'tag' => false
                            ],
                            'template' => '{input}'
                        ])->input('hidden',[
                            'value' => '',
                            'class' => 'setColor',
                        ]);?>
                        <?= $form->field($premium,'date',[
                            'template' => '{label}<div class="input-group">{input}<div class="input-group-append"><span class="input-group-text"><span class="fa fa-calendar-o"></span></span></div></div>'
                        ])->textInput([
                            'class' => 'form-control pickadate',
                            'data-max' => "true",
                        ])->label('Дата'); ?>
                    <?= $form->field($premium,'value')->input('number',[
                        'autocomplete' => 'off',
                        'value' => '',
                        'step' => 0.01,
                        'required' => true])->label('Сумма'); ?>
                    <?= $form->field($premium,'text')->textarea([
                        'autocomplete' => 'off',
                        'value' => '',
                        'required' => true])->label('Комментарий'); ?>

                    <div class="form-group text-right">
                        <?= Html::submitButton(Yii::t('app', 'Save'), [
                            'class' => 'btn btn-success btn-round pl-4 pr-4'
                        ]) ?>
                    </div>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
<?php $this->params['modals'] .= ob_get_clean(); ?>
<?php $this->registerCssFile(
    '/app-assets/vendors/css/pickadate/pickadate.css',
    ['depends' => [\yii\web\JqueryAsset::className()]]); ?>
<?php $this->registerJsFile(
    '/app-assets/vendors/js/pickadate/picker.js',
    ['depends' => [\yii\web\JqueryAsset::className()]]); ?>
<?php $this->registerJsFile(
    '/app-assets/vendors/js/pickadate/picker.date.js',
    ['depends' => [\yii\web\JqueryAsset::className()]]); ?>
<?php $this->registerJsFile(
    '/crm/js/salary/scripts.js',
    ['depends' => [\app\assets\AppAsset::className()]]); ?>