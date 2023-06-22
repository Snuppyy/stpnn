<?php

use yii\helpers\Html;
use \yii\widgets\ActiveForm;
use app\models\App\Client\ClientConnection as CC;
use app\widgets\Components\Request\Requests;
use app\models\App\Client\ClientOtherInfo;
use app\widgets\Components\Request\RequestManager;
/* @var $this yii\web\View */
/* @var $model app\models\App\Client\Client */
/* @var $organization app\models\App\Client\ClientOrganization */
/* @var $otherinfo app\models\App\Client\ClientOtherInfo */
/* @var $manager app\models\App\UserProfile */

$name = !empty($model->lastname) ? $model->lastname.' ' : '';
$name .= !empty($model->firstname) ? $model->firstname.' ' : '';
$name .= !empty($model->fathername) ? $model->fathername.' ' : '';
$this->title = $name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Clients'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="client-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row align-items-stretch">
        <div class="col-12 col-md-4 colRow">
            <div class="card">
                <div class="card-header">
                    <div class="card-title-wrap bar-info">
                        <div class="font-weight-bold font-medium-2">
                            Данные менеджеров
                        </div>
                    </div>
                </div>
                <div class="card-body card-scroll">
                    <?php if(!empty($manager)){
                        $_manager = $manager;
                        foreach ($_manager as $manager) { ?>
                            <div class="card-block">
                                <div class="form-group border-bottom">
                                    <div class="row">
                                        <div class="col-<?= !empty($manager->avatar) ? '8' : '12';?>">
                                            <label class="control-label">Имя:</label>
                                            <div class="pb-2">
                                                <b>
                                                    <?= $manager->full_name; ?>
                                                </b>
                                            </div>
                                        </div>
                                        <? if(!empty($manager->avatar)){ ?>
                                            <div class="col-4 text-right">
                                                <div class="avatar lg-prev">
                                                    <img src="<?= $manager->avatar; ?>" alt="">
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                                <div class="form-group border-bottom">
                                    <label class="control-label">Телефон:</label>
                                    <div class="pb-2">
                                        <?php $phones = json_decode($manager->phones,true);
                                        foreach ($phones as $phone) { ?>
                                            <b class="block"><?= $phone; ?></b>
                                        <?php }?>
                                    </div>
                                </div>
                                <div class="form-group border-bottom">
                                    <label class="control-label">E-mail:</label>
                                    <div class="pb-2">
                                        <b><?= $manager->emails; ?></b>
                                    </div>
                                </div>
                                <?php if(!empty($manager->skypes)) { ?>
                                    <div class="form-group border-bottom">
                                        <label class="control-label">Skype:</label>
                                        <div class="pb-2">
                                            <b>
                                                <?= $manager->skypes; ?>
                                            </b>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php }?>
                    <?php }else{ ?>
                        <div class="card-block">
                            <strong>Менеджер не назначен</strong>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4 colRow">
            <div class="card">
                <div class="card-header">
                    <div class="card-title-wrap bar-danger">
                        <div class="font-weight-bold font-medium-2">
                            Контактная информация
                            <a class="float-right" data-toggle="modal" data-target="#modalEditProfile"><i class="warning icon-pencil"></i></a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-block">
                        <div class="form-group border-bottom">
                            <label class="control-label">Контактное лицо</label>
                            <div class="info pb-2">
                                <?= !empty($model->lastname) ? $model->lastname.' ' : ''; ?>
                                <?= !empty($model->firstname) ? $model->firstname : ''; ?>
                                <?= !empty($model->fathername) ? ' '.$model->fathername : ''; ?>
                            </div>
                        </div>
                        <div class="form-group border-bottom">
                            <label class="control-label">Телефон</label>
                            <div class="pb-2">
                                <?php $phones = $model->getConnection('phone')->all();
                                if(!empty($phones)) {
                                    foreach ($phones as $phone) { ?>
                                        <div class="font-weight-bold">
                                            <?= $phone->value; ?>
                                        </div>
                                    <?php }
                                }else{ ?>
                                    <b class="warning">У пользователя не указан телефон</b>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="form-group border-bottom">
                            <label class="control-label">E-mail</label>
                            <div class="pb-2">
                                <?php $emails = $model->getConnection('email')->all();
                                if(!empty($emails)) {
                                    foreach ($emails as $email) { ?>
                                        <div class="font-weight-bold">
                                            <?= $email->value; ?>
                                        </div>
                                    <?php }
                                }else{ ?>
                                    <b class="warning">У пользователя не указана почта</b>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php //if($model->role == $model::ROLE_UR){ ?>
            <div class="col-12 col-md-4 colRow">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title-wrap bar-warning">
                            <div class="font-weight-bold font-medium-2">
                                Реквизиты
                                <a class="float-right baseInfo" data-toggle="modal" data-target="#modalEditOrg">
                                    <i class="warning icon-pencil"></i>
                                </a>
                                <a class="float-right clearInfo" data-toggle="modal" data-target="#modalEditOrg"
                                   data-title="Сменить компанию">
                                    <i class="danger icon-energy"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">

                        <div class="card-block card-scroll">
                            <?php
                            $other = $otherinfo;
                            if(!empty($organization)){ ?>
                                <div class="form-group border-bottom">
                                    <label class="control-label grey">Наименование:</label>
                                    <div class="black pb-2">
                                        <?= $organization->title; ?>
                                    </div>
                                </div>
                                <div class="form-group border-bottom row">
                                    <div class="col-md-6">
                                        <label class="control-label grey">Адрес:</label>
                                        <div class="black pb-2">
                                            <?= $organization->address; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="control-label grey">Юр. адрес:</label>
                                        <div class="black pb-2">
                                            <?= $organization->ur_address; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group border-bottom row">
                                    <div class="col-md-6">
                                        <label class="control-label grey">ИНН:</label>
                                        <div class="black pb-2">
                                            <?= $organization->inn; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="control-label grey">КПП:</label>
                                        <div class="black pb-2">
                                            <?= $organization->kpp; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group border-bottom row">
                                    <div class="col-md-6">
                                        <label class="control-label grey">ОГРНИП:</label>
                                        <div class="black pb-2">
                                            <?= $organization->ogrn; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="control-label grey">БИК:</label>
                                        <div class="black pb-2">
                                            <?= $organization->bik; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group border-bottom">
                                    <label class="control-label grey">Р/С №:</label>
                                    <div class="black pb-2">
                                        <?= $organization->rs; ?>
                                    </div>
                                </div>
                                <div class="form-group border-bottom">
                                    <label class="control-label grey">К/С №:</label>
                                    <div class="black pb-2">
                                        <?= $organization->ks; ?>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if(!empty($other)) {
                                foreach ($other as $_other) { ?>
                                    <div class="form-group border-bottom">
                                        <label class="control-label grey">
                                            <?= $_other->title; ?>:
                                            <a class="float-right setOtherInfo showHoverParent" data-toggle="modal" data-target="#modalEditOrgOther" data-id="<?= $_other->id; ?>" data-title="<?= $_other->title; ?>" data-value="<?= $_other->value; ?>">
                                                <i class="warning icon-pencil"></i>
                                            </a>
                                        </label>
                                        <div class="black pb-2">
                                            <?= $_other->value; ?>
                                        </div>
                                    </div>
                                <?php }
                            } ?>
                            <div class="btn-group dropup">
                                <button
                                        class="btn btn-info btn-round"
                                        data-toggle="modal" data-target="#modalOtheInfo">
                                    <i class="icon-plus" data-toggle="tooltip" data-placement="right" data-original-title="Добавить не основную информацию об организации"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php //} ?>
    </div>

    <? if(!empty($request_new)){
        echo Requests::widget([
            'title' => 'Новые',
            'new' => true,
            'options' => [
                'theme' => 'danger',
            ],
            'items' => $request_new,
        ]);
    } ?>
    <? if(!empty($request_purpose)){
        echo Requests::widget([
            'title' => 'Ожидает',
            'purpose' => true,
            'options' => [
                'theme' => 'info',
            ],
            'items' => $request_purpose,
        ]);
    } ?>
    <? if(!empty($request_in) && $role == 'admin'){
        echo Requests::widget([
            'title' => 'В работе',
            'in' => true,
            'options' => [
                'theme' => 'warning',
            ],
            'items' => $request_in,
        ]);
    }elseif(!empty($request_in)){
        echo RequestManager::widget([
            'title' => 'В работе',
            'in' => true,
            'options' => [
                'theme' => 'warning',
            ],
            'items' => $request_in,
        ]);
    } ?>
    <? if(!empty($request_success)){
        echo Requests::widget([
            'title' => 'Завершенные',
            'success' => true,
            'options' => [
                'theme' => 'success',
            ],
            'items' => $request_success,
        ]);
    } ?>
</div>
<?php ob_start(); ?>
    <div class="modal fade" data-target="#modalEditProfile" id="modalEditProfile">
        <div class="modal-dialog" role="document">
            <div class="modal-content card">
                <div class="modal-header card-header">
                    <div class="card-title-wrap bar-success">
                        <span class="title-box">Редактирование профиля</span>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body">
                    <?php $form = ActiveForm::begin([
                        'options' => [
                            'id' => 'editClientForm',
                        ]
                    ]); ?>
                    <?= $form->field($model, 'id',['template' => '{input}','options' => ['tag' => false]])
                        ->input('hidden') ?>
                    <?= $form->field($model, 'lastname')
                        ->textInput(['maxlength' => true]) ?>
                    <?= $form->field($model, 'firstname')
                        ->textInput(['maxlength' => true]) ?>
                    <?= $form->field($model, 'fathername')
                        ->textInput(['maxlength' => true]) ?>
                    <?= $form->field($model,'date_birthday',[
                        'template' => '{label}<div class="input-group">{input}<div class="input-group-append"><span class="input-group-text"><span class="fa fa-calendar-o"></span></span></div></div>'
                    ])->textInput([
                        'class' => 'form-control pickadate',
                        'data-max' => "true",
                        'value' => !empty($model->date_birthday) ? date('d.m.Y',$model->date_birthday) : ''
                    ]); ?>

                    <?= $form->field($model, 'role')
                        ->dropDownList($model::getRoles()) ?>

                    <?php $phones = $model->getConnection('phone')->all(); ?>
                    <div class="form-group field-clientconnection-phone">
                        <label class="control-label" for="clientconnection-phone">
                            Телефоны
                        </label>
                        <div class="wrap-add">
                            <div data-items>
                                <?php if(!empty($phones)){ ?>
                                    <? foreach ($phones as $_key => $phone) { ?>
                                        <div data-item>
                                            <?= $form->field($phone, 'phone[]',[
                                                'template' => '{input}'
                                            ])
                                                ->textInput(['value' => $phone->value, 'required' => true]);
                                            ?>
                                            <a class="btn btn-flat btn-danger position-absolute trashDataItem">
                                                <i class="icon-trash"></i>
                                            </a>
                                        </div>

                                    <? } ?>
                                <?php } ?>
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-round pr-2 pl-2 addItem">
                                <i class="ft-plus"></i>
                                Добавить
                            </button>
                            <div hidden data-item>
                                <?= $form->field(new CC, 'phone[]',[
                                    'template' => '{input}'
                                ])
                                    ->textInput(['value' => '', 'required' => true,'disabled' => true]) ?>
                                <a class="btn btn-flat btn-danger position-absolute trashDataItem">
                                    <i class="icon-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <?php $emails = $model->getConnection('email')->all(); ?>
                    <div class="form-group field-clientconnection-email">
                        <label class="control-label" for="clientconnection-email">
                            E-mail
                        </label>
                        <div class="wrap-add">
                            <div data-items>
                                <?php if(!empty($emails)){ ?>
                                    <? foreach ($emails as $email) { ?>
                                        <div data-item>
                                            <?= $form->field($email, 'email[]',[
                                                'template' => '{input}'
                                            ])
                                                ->textInput(['value' => $email->value, 'required' => true]); ?>
                                            <a class="btn btn-flat btn-danger position-absolute trashDataItem">
                                                <i class="icon-trash"></i>
                                            </a>
                                        </div>
                                    <?php } ?>
                                <?php } ?>
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-round pr-2 pl-2 addItem">
                                <i class="ft-plus"></i>
                                Добавить
                            </button>
                            <div hidden data-item>
                                <?= $form->field(new CC, 'email[]',[
                                    'template' => '{input}'
                                ])
                                    ->textInput(['value' => '', 'required' => true,'disabled' => true]) ?>
                                <a class="btn btn-flat btn-danger position-absolute trashDataItem">
                                    <i class="icon-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalEditOrg">
        <div class="modal-dialog" role="document">
            <div class="modal-content card">
                <div class="modal-header card-header">
                    <div class="card-title-wrap bar-success">
                        <span class="title-box">Редактирование реквизитов</span>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body">
                    <?php
                    if(empty($organization)){
                        $organization = new \app\models\App\Client\ClientOrganization();
                    }
                    ?>
                    <?php $form = ActiveForm::begin([
                        'options' => [
                            'id' => 'editClientOrg',
                        ]
                    ]); ?>
                    <?= $form->field($organization, 'id',['template' => '{input}','options' => ['tag' => false]])
                        ->input('hidden',['data-value' => $organization->id]) ?>
                    <?= $form->field($organization, 'client_id',['template' => '{input}','options' => ['tag' => false]])
                        ->input('hidden',['value' => $model->id]) ?>
                    <?= $form->field($organization, 'inn',[
                        'template' => '{label}<div class="position-relative">{input}
                        <a class="btn btn-flat btn-danger position-absolute searchOrg">
                            <i class="icon-magnifier"></i>
                        </a></div>'
                    ])
                        ->input('number',[
                            'class' => 'form-control pr-4',
                            'data-toggle' => 'tooltip',
                            'data-placement' => 'bottom',
                            'data-original-title' => 'Сначала сделайте поиск по ИНН'
                        ]) ?>

                    <?= $form->field($organization, 'title')
                        ->input('text',[
                            'maxlength' => true
                        ])->label('Наименование') ?>
                    <?= $form->field($organization, 'address')
                        ->input('text')->label('Фактический адрес') ?>

                    <?= $form->field($organization, 'ur_address')
                        ->input('text')->label('Юридический адрес') ?>

                    <?= $form->field($organization, 'ogrn')
                        ->input('number') ?>

                    <?= $form->field($organization, 'rs')
                        ->input('number') ?>

                    <?= $form->field($organization, 'ks')
                        ->input('number') ?>

                    <?= $form->field($organization, 'bik')
                        ->input('number') ?>
                    <?= $form->field($organization, 'kpp')
                        ->input('number') ?>
                    <div class="form-group">
                        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
    <?php if($role == 'admin'){ ?>
        <div class="modal fade" data-target="#modalPurpose" id="modalPurpose">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content card">
                    <div class="modal-header card-header">
                        <div class="card-title-wrap bar-success">
                            <span class="title-box">Выбрать исполнителя</span>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    </div>
                    <div class="modal-body" data-id="">
                        <div class="row">
                            <?php if(!empty($list_users)){
                                foreach ($list_users as $_user) { ?>
                                    <div class="col-12">
                                        <div class="itemUser mb-3">
                                            <div class="row align-items-center">
                                                <div class="col-7">
                                                    <div class="text-center">
                                                        <div class="avatar">
                                                            <?= !empty($_user['avatar']) ? '<img src="'.$_user['avatar'].'">' : ''; ?>
                                                        </div>

                                                        <b>
                                                            <?= $_user['full_name']; ?>
                                                        </b>
                                                    </div>
                                                </div>
                                                <div class="col-2">
                                                    <div class="row align-items-stretch" >
                                                        <div class="col-2"
                                                             style="background-color: #FF9149;"
                                                             data-toggle="tooltip" data-original-title="В работе"></div>
                                                        <div class="col-9">
                                                            <div class="alert-white"><?= $_user['count']['in']; ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="row align-items-stretch" >
                                                        <div class="col-2"
                                                             style="background-color: #666EE8;"
                                                             data-toggle="tooltip" data-original-title="Ожидание принятия"></div>
                                                        <div class="col-9">
                                                            <div class="alert-white"><?= $_user['count']['purpose']; ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="row align-items-stretch" >
                                                        <div class="col-2"
                                                             style="background-color: #28D094;"
                                                             data-toggle="tooltip" data-original-title="Завершенные"></div>
                                                        <div class="col-9">
                                                            <div class="alert-white"><?= $_user['count']['success']; ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-2">
                                                    <button
                                                            type="button"
                                                            data-user_id="<?= $_user['ID']; ?>"
                                                            class="btn btnPurposeModal btn-round shadow-z-2 btn-secondary">
                                                        Выбрать
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php }
                            }else{
                                echo '<div class="col-12"><p class="danger">Нет активных менеджеров</p></div>';
                            } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" data-target="#modalPurposeChange" id="modalPurposeChange">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content card">
                    <div class="modal-header card-header">
                        <div class="card-title-wrap bar-success">
                            <span class="title-box">Сменить исполнителя</span>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    </div>
                    <div class="modal-body" data-id="">
                        <div class="row">
                            <?php if(!empty($list_users)){
                                foreach ($list_users as $_user) { ?>
                                    <div class="col-12">
                                        <div class="itemUser mb-3">
                                            <div class="row align-items-center">
                                                <div class="col-7">
                                                    <div class="text-center">
                                                        <div class="avatar">
                                                            <?= !empty($_user['avatar']) ? '<img src="'.$_user['avatar'].'">' : ''; ?>
                                                        </div>

                                                        <b>
                                                            <?= $_user['full_name']; ?>
                                                        </b>
                                                    </div>
                                                </div>
                                                <div class="col-2">
                                                    <div class="row align-items-stretch" >
                                                        <div class="col-2"
                                                             style="background-color: #FF9149;"
                                                             data-toggle="tooltip" data-original-title="В работе"></div>
                                                        <div class="col-9">
                                                            <div class="alert-white"><?= $_user['count']['in']; ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="row align-items-stretch" >
                                                        <div class="col-2"
                                                             style="background-color: #666EE8;"
                                                             data-toggle="tooltip" data-original-title="Ожидание принятия"></div>
                                                        <div class="col-9">
                                                            <div class="alert-white"><?= $_user['count']['purpose']; ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="row align-items-stretch" >
                                                        <div class="col-2"
                                                             style="background-color: #28D094;"
                                                             data-toggle="tooltip" data-original-title="Завершенные"></div>
                                                        <div class="col-9">
                                                            <div class="alert-white"><?= $_user['count']['success']; ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-2">
                                                    <button
                                                            type="button"
                                                            data-user_id="<?= $_user['ID']; ?>"
                                                            class="btn btnPurposeModalChange btn-round shadow-z-2 btn-secondary">
                                                        Выбрать
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php }
                            }else{
                                echo '<div class="col-12"><p class="danger">Нет активных менеджеров</p></div>';
                            } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" data-target="#modalPurposeAssistant" id="modalPurposeAssistant">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content card">
                    <div class="modal-header card-header">
                        <div class="card-title-wrap bar-success">
                            <span class="title-box">Назначить пощника</span>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    </div>
                    <div class="modal-body" data-id="">
                        <div class="row">
                            <?php if(!empty($list_users)){
                                foreach ($list_users as $_user) { ?>
                                    <div class="col-12">
                                        <div class="itemUser mb-3">
                                            <div class="row align-items-center">
                                                <div class="col-7">
                                                    <div class="text-center">
                                                        <div class="avatar">
                                                            <?= !empty($_user['avatar']) ? '<img src="'.$_user['avatar'].'">' : ''; ?>
                                                        </div>

                                                        <b>
                                                            <?= $_user['full_name']; ?>
                                                        </b>
                                                    </div>
                                                </div>
                                                <div class="col-2">
                                                    <div class="row align-items-stretch" >
                                                        <div class="col-2"
                                                             style="background-color: #FF9149;"
                                                             data-toggle="tooltip" data-original-title="В работе"></div>
                                                        <div class="col-9">
                                                            <div class="alert-white"><?= $_user['count']['in']; ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="row align-items-stretch" >
                                                        <div class="col-2"
                                                             style="background-color: #666EE8;"
                                                             data-toggle="tooltip" data-original-title="Ожидание принятия"></div>
                                                        <div class="col-9">
                                                            <div class="alert-white"><?= $_user['count']['purpose']; ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="row align-items-stretch" >
                                                        <div class="col-2"
                                                             style="background-color: #28D094;"
                                                             data-toggle="tooltip" data-original-title="Завершенные"></div>
                                                        <div class="col-9">
                                                            <div class="alert-white"><?= $_user['count']['success']; ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-2">
                                                    <button
                                                            type="button"
                                                            data-user_id="<?= $_user['ID']; ?>"
                                                            class="btn btnPurposeModal btn-round shadow-z-2 btn-secondary">
                                                        Выбрать
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php }
                            }else{
                                echo '<div class="col-12"><p class="danger">Нет активных менеджеров</p></div>';
                            } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
    <?php $newOher = new ClientOtherInfo(); ?>
    <div class="modal fade" data-target="#modalOtheInfo" id="modalOtheInfo">
        <div class="modal-dialog" role="document">
            <div class="modal-content card">
                <div class="modal-header card-header">
                    <div class="card-title-wrap bar-success">
                        <span class="title-box">Новая информация</span>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body">
                    <?php $form = ActiveForm::begin([
                        'options' => [
                            'id' => 'addOtherInfo',
                        ],
                        'action' => \yii\helpers\Url::to(['/admin/client/add-other-info'])
                    ]); ?>
                    <?= $form->field($newOher, 'client_id',['template' => '{input}','options' => ['tag' => false]])
                        ->input('hidden',['value' => $model->id]) ?>
                    <?= $form->field($newOher, 'title')
                        ->textInput(['maxlength' => true]) ?>
                    <?= $form->field($newOher, 'value')
                        ->textInput() ?>
                    <div class="form-group">
                        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" data-target="#modalEditOrgOther" id="modalEditOrgOther">
    <div class="modal-dialog" role="document">
        <div class="modal-content card">
            <div class="modal-header card-header">
                <div class="card-title-wrap bar-success">
                    <span class="title-box">Обновить информация</span>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body">
                <?php $form = ActiveForm::begin([
                    'options' => [
                        'id' => 'updateOtherInfo',
                    ],
                    'action' => \yii\helpers\Url::to(['/admin/client/update-other-info'])
                ]); ?>
                <?= $form->field($newOher, 'id',['template' => '{input}','options' => ['tag' => false]])
                    ->input('hidden',['value' => '','class' => 'setIdOtherInfo']) ?>
                <?= $form->field($newOher, 'client_id',['template' => '{input}','options' => ['tag' => false]])
                    ->input('hidden',['value' => $model->id]) ?>
                <?= $form->field($newOher, 'title')
                    ->textInput(['maxlength' => true,'class' => 'form-control setTitleOtherInfo']) ?>
                <?= $form->field($newOher, 'value')
                    ->textInput(['class' => 'form-control setValueOtherInfo']) ?>
                <div class="form-group">
                    <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
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
    ['depends' => [\app\assets\AppAsset::className()]]); ?>
<?php $this->registerJsFile(
    '/app-assets/vendors/js/pickadate/picker.date.js',
    ['depends' => [\app\assets\AppAsset::className()]]); ?>
<?php $this->registerJsFile(
    '/crm/js/client/view.js',
    ['depends' => [\app\assets\AppAsset::className()]]); ?>
