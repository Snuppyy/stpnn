<?php
/* @var $this yii\web\View */
/* @var $start string */
/* @var $end string */
/* @var $items array */
/* @var $users array */

use app\components\AppFunctions as AP;
use yii\helpers\Url;
use \app\models\App\Client\Client;

$this->title = 'Магафон';

$m_users = array_column($users, 'megafone_id');
$statuses = array_column($items, 'status');
$types = array_column($items, 'type');
$Success = count(array_keys($statuses, 'Success'));
$missed = count(array_keys($statuses, 'missed'));
$in = count(array_intersect(array_keys($types, 'in'),array_keys($statuses, 'Success')));
$out = count(array_intersect(array_keys($types, 'out'),array_keys($statuses, 'Success')));
?>

<h1 class="title-box mt-3 mb-1"><?= $this->title; ?> статистика</h1>
<form class="form" action="<?= Url::to(['/admin/megafon/index']); ?>" method="get">
    <div class="row">
        <div class="col-2">
            <div class="form-group">
                <div class="input-group">
                    <div class="input-group-prepend">
                    <span class="input-group-text">
                        <span class="fa fa-calendar-o"></span>
                    </span>
                    </div>
                    <input id="picker_from" class="form-control datepicker picker__input" type="text" readonly="" name="picker_from" value="<?= $start; ?>">
                </div>
            </div>
        </div>
        <div class="col-2">
            <div class="form-group">
                <div class="input-group">
                    <div class="input-group-prepend">
                    <span class="input-group-text">
                        <span class="fa fa-calendar-o"></span>
                    </span>
                    </div>
                    <input id="picker_to" class="form-control datepicker picker__input" type="text" readonly="" name="picker_to" value="<?= $end; ?>">
                </div>
            </div>
        </div>
        <div class="col-2">
            <button class="btn btn-flat btn-primary btnChange mb-0 font-medium-3 px-0" style="display: none;">
                <i class="ft-arrow-right"></i>
            </button>
        </div>
    </div>
</form>

<div class="row">
    <div class="col-xl-3 col-lg-5 col-12 order-1 order-xl-1">
        <div class="card gradient-light-blue-cyan">
            <div class="card-body">
                <div class="px-3 py-3">
                    <div class="media">
                        <div class="align-center">
                            <i class="ft-phone-call text-white font-large-2 float-left"></i>
                        </div>
                        <div class="media-body text-white text-right">
                            <h3 class="text-white"><?= count($items); ?></h3>
                            <span data-toggle="tooltip" data-placement="top" title="" data-original-title="Все звонки за выбранный период">Звонки</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card gradient-mint">
            <div class="card-body">
                <div class="px-3 py-3">
                    <div class="media">
                        <div class="align-center">
                            <i class="ft-phone-call text-white font-large-2 float-left"></i>
                        </div>
                        <div class="media-body text-white text-right">
                            <h3 class="text-white"><?= $Success; ?></h3>
                            <span data-toggle="tooltip" data-placement="top" title="" data-original-title="Все успешные звонки">Успешные</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card gradient-strawberry filter-phone" data-type="missed">
            <div class="card-body">
                <div class="px-3 py-3">
                    <div class="media">
                        <div class="align-center">
                            <i class="ft-phone-missed text-white font-large-2 float-left"></i>
                        </div>
                        <div class="media-body text-white text-right">
                            <h3 class="text-white"><?= $missed; ?></h3>
                            <span data-toggle="tooltip" data-placement="top" title="" data-original-title="Пропущенные менеджерами">Пропущенные</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card gradient-sublime-vivid filter-phone" data-type="incoming">
            <div class="card-body">
                <div class="px-3 py-3">
                    <div class="media">
                        <div class="align-center">
                            <i class="ft-phone-incoming text-white font-large-2 float-left"></i>
                        </div>
                        <div class="media-body text-white text-right">
                            <h3 class="text-white"><?= $in; ?></h3>
                            <span data-toggle="tooltip" data-placement="top" title="" data-original-title="Все успешные входящие">Входящие</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card gradient-summer filter-phone" data-type="outgoing">
            <div class="card-body">
                <div class="px-3 py-3">
                    <div class="media">
                        <div class="align-center">
                            <i class="ft-phone-outgoing text-white font-large-2 float-left"></i>
                        </div>
                        <div class="media-body text-white text-right">
                            <h3 class="text-white"><?= $out; ?></h3>
                            <span data-toggle="tooltip" data-placement="top" title="" data-original-title="Все успешные исходящие">Исходящие</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-5 col-lg-12 col-12 order-3 order-xl-2">
        <div class="card">
            <div class="card-body">
                <div class="card-block">
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link active show" data-toggle="tab" aria-controls="history" href="#history" aria-expanded="true">История</a>
                        </li>
                        <? /*<li class="nav-item">
                            <a class="nav-link" data-toggle="tab" aria-controls="stat" href="#stat" aria-expanded="false">Статистика</a>
                        </li> */ ?>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" aria-controls="worker" href="#worker" aria-expanded="false">Сотрудники</a>
                        </li>
                    </ul>
                    <div class="tab-content px-1 pt-1 black">
                        <div role="tabpanel" class="tab-pane active show" id="history" aria-expanded="true">
                            <?php
                            $history = [];
                            if( !empty($items) ){
                                $history = array_reverse($items);
                            }
                            if( !empty($history) ) {
                                $day = '';
                                foreach ($history as $_h) {
                                    $_day = date('j', $_h['start'])." ".
                                        AP::getRussianMonthNameSclon(date('n', $_h['start'])-1).
                                        (date("Y") == date("Y",$_h['start']) ? '' : ' '.date("Y",$_h['start']));
                                    if( $_day !=  $day) {
                                        $day = $_day;
                                        echo '<p class="text-right text-bold-700 font-large-1">'.$day.'</p>';
                                    }
                                    ?>
                                    <?php
                                    $icon = 'ft-phone-outgoing';
                                    $color = 'text-success';
                                    $filter = 'outgoing';
                                    if( $_h['type'] == 'in' ){
                                        $icon = 'ft-phone-incoming';
                                        $filter = 'incoming';
                                    }
                                    if( $_h['status'] == 'missed' ){
                                        $color = 'text-danger';
                                        $filter = 'missed';
                                    }
                                    elseif( $_h['status'] == 'Busy' ){
                                        $color = 'text-warning';
                                    }
                                    ?>
                                    <div class="row align-items-center border-bottom border-base pb-2 mb-2" data-filter="<?= $filter; ?>">
                                        <div class="col-auto">

                                            <i class="<?= $icon; ?> <?= $color;?>"></i>
                                        </div>
                                        <div class="col">
                                            <p class="mb-1 font-medium-2">
                                                <a href="#" class="black loadedClient" data-id="<?= $_h['id']; ?>">
                                                    <?php if( empty($_h['user_id']) ) { ?>
                                                        +<?= $_h['phone']; ?>
                                                    <?php }
                                                    else { ?>
                                                        <span class="font-medium-1 d-block"><?= Client::FullName($_h['user_id']); ?></span>
                                                        <span class="font-small-2">+<?= $_h['phone']; ?></span>
                                                    <?php } ?>
                                                    <i class="ft-chevron-right mr-3 d-inline-block valign-middle"></i>
                                                </a>
                                            </p>
                                            <p class="mb-0 font-small-3 text-black-50">
                                                <?php $find_user = array_search($_h['user'], $m_users);
                                                if( $find_user !== false ) {
                                                    echo $users[$find_user]['full_name'];
                                                }
                                                if( !empty($_h['link']) ) { //В запрос добавить нужно
                                                    echo '<a href="'.$_h['link'].'" target="_blank"><i class="ft-volume-2"></i></a>';
                                                } ?>
                                            </p>
                                        </div>
                                        <div class="col-auto">
                                            <p class="mb-1"><?= date('H:i:s', $_h['start']); ?></p>
                                            <p class="font-small-2 mb-0 text-right"><?= gmdate('H', $_h['duration']) == '00' ? '' : gmdate('H', $_h['duration']).":"; ?><?= gmdate('i:s', $_h['duration']); ?></p>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                        <? /*<div role="tabpanel" class="tab-pane" id="stat" aria-expanded="false">

                        </div> */ ?>
                        <div role="tabpanel" class="tab-pane" id="worker" aria-expanded="false">
                            <?php
                            $success_out_calls = [];
                            $success_calls = [];
                            $times_in = [];
                            $times_out = [];
                            $users_calls = array_column($items, 'user');
                            foreach ($users as $i => $user) {
                                $user_calls = array_keys($users_calls, $user['megafone_id']);
                                if( !empty($user_calls) ) {
                                    foreach ($user_calls as $key) {
                                        if( $items[$key]['status'] == 'Success' ) {
                                            if( !empty($success_calls[$i]) ) {
                                                $success_calls[$i]++;
                                            }
                                            else {
                                                $success_calls[$i] = 1;
                                            }

                                            if( $items[$key]['type'] == 'out' ) {
                                                if( !empty($times_out[$user['megafone_id']]) ) {
                                                    $times_out[$user['megafone_id']] += $items[$key]['duration'];
                                                }
                                                else {
                                                    $times_out[$user['megafone_id']] = $items[$key]['duration'];
                                                }
                                                if( !empty($success_out_calls[$i]) ) {
                                                    $success_out_calls[$i]++;
                                                }
                                                else {
                                                    $success_out_calls[$i] = 1;
                                                }
                                            }
                                            else {
                                                if( !empty($times_out[$user['megafone_id']]) ) {
                                                    $times_in[$user['megafone_id']] += $items[$key]['duration'];
                                                }
                                                else {
                                                    $times_in[$user['megafone_id']] = $items[$key]['duration'];
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            ?>
                            <div class="row justify-content-between">
                                <div class="col-auto"><h5>Все успешные звонки</h5></div>
                                <div class="col-auto"><b><?= array_sum($success_calls); ?></b></div>
                            </div>
                            <?php $middle_success = round(array_sum($success_calls)/count($success_calls), 0); ?>
                            <div class="row justify-content-between">
                                <div class="col-auto"><h6>Средний сотрудник</h6></div>
                                <div class="col-auto"><?= $middle_success; ?></div>
                            </div>
                            <?php
                            $success_calls_keys = array_keys($success_calls);
                            array_multisort($success_calls, SORT_DESC, SORT_NUMERIC, $success_calls_keys);
                            $last = 0;

                            foreach ($success_calls as $i => $success_call) { ?>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="media align-items-stretch">
                                            <div class="p-2 text-center <?= $success_call < $middle_success ? 'bg-danger' : 'bg-success'; ?> rounded-left pt-3">
                                                <span class="font-large-2 text-white"><?= $i+1; ?></span>
                                            </div>
                                            <div class="p-2 media-body border border-left-0">
                                                <h6><?= $users[$success_calls_keys[$i]]['full_name'] ; ?></h6>
                                                <h5 class="text-bold-400 mb-0"><?= $success_call; ?></h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php $last = $i+2; ?>
                            <?php } ?>
                            <?php
                            if( count($success_calls) != count($users) ) {
                                foreach ($users as $u_k => $user) {
                                    if( !in_array($u_k, $success_calls_keys) ) { ?>
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="media align-items-stretch">
                                                    <div class="p-2 text-center bg-danger rounded-left pt-3">
                                                        <span class="font-large-2 text-white"><?= $last; ?></span>
                                                    </div>
                                                    <div class="p-2 media-body border border-left-0">
                                                        <h6><?= $user['full_name'] ; ?></h6>
                                                        <h5 class="text-bold-400 mb-0">0</h5>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php }
                                }
                            }
                            ?>
                            <hr>
                            <div class="row justify-content-between">
                                <div class="col-auto"><h5>Исходящие успешные звонки</h5></div>
                                <div class="col-auto"><b><?= array_sum($success_out_calls); ?></b></div>
                            </div>
                            <?php $middle_success = round(array_sum($success_out_calls)/count($success_out_calls), 0); ?>
                            <div class="row justify-content-between">
                                <div class="col-auto"><h6>Средний сотрудник</h6></div>
                                <div class="col-auto"><?= $middle_success; ?></div>
                            </div>
                            <?php
                            $success_out_calls_keys = array_keys($success_out_calls);
                            array_multisort($success_out_calls, SORT_DESC, SORT_NUMERIC, $success_out_calls_keys);
                            foreach ($success_out_calls as $i => $success_call) { ?>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="media align-items-stretch">
                                            <div class="p-2 text-center <?= $success_call < $middle_success ? 'bg-danger' : 'bg-success'; ?> rounded-left pt-3">
                                                <span class="font-large-2 text-white"><?= $i+1; ?></span>
                                            </div>
                                            <div class="p-2 media-body border border-left-0">
                                                <h6><?= $users[$success_out_calls_keys[$i]]['full_name'] ; ?></h6>
                                                <h5 class="text-bold-400 mb-0"><?= $success_call; ?></h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php
                            if( count($success_out_calls) != count($users) ) {
                                foreach ($users as $u_k => $user) {
                                    if( !in_array($u_k, $success_out_calls_keys) ) { ?>
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="media align-items-stretch">
                                                    <div class="p-2 text-center bg-danger rounded-left pt-3">
                                                        <span class="font-large-2 text-white"><?= $last; ?></span>
                                                    </div>
                                                    <div class="p-2 media-body border border-left-0">
                                                        <h6><?= $user['full_name'] ; ?></h6>
                                                        <h5 class="text-bold-400 mb-0">0</h5>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php }
                                }
                            }
                            ?>
                            <hr>
                            <?php
                            foreach ($users as $i => $user) {
                                $alls = $times_in[$user['megafone_id']] + $times_out[$user['megafone_id']];
                                ?>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="media align-items-stretch">
                                            <div class="p-2 text-center bg-success rounded-left pt-3">
                                                <span class="font-large-1 text-white"><i class="ft-phone-call text-white font-large-2 float-left"></i></span>
                                            </div>
                                            <div class="p-2 media-body border border-left-0">
                                                <h6><?= $user['full_name']; ?></h6>
                                                <h5 class="text-bold-400 mb-0"><small>Вх.</small> <b><?= empty($times_in[$user['megafone_id']]) == '00' ? '' : gmdate('H', $times_in[$user['megafone_id']]).":"; ?><?= gmdate('i:s', $times_in[$user['megafone_id']]); ?></b></h5>
                                                <h5 class="text-bold-400 mb-0"><small>Исх.</small> <b><?= empty($times_out[$user['megafone_id']]) == '00' ? '' : gmdate('H', $times_out[$user['megafone_id']]).":"; ?><?= gmdate('i:s', $times_out[$user['megafone_id']]); ?></b></h5>
                                                <h5 class="text-bold-400 mb-0"><small>Общ.</small> <b><?= empty($alls) == '00' ? '' : gmdate('H', $alls).":"; ?><?= gmdate('i:s', $alls); ?></b></h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-7 col-12 order-2 order-xl-3">
        <div class="card bg-white">
            <div class="card-body">
                <div class="card-block pt-2 pb-0">
                    <div class="media">
                        <div class="media-body text-left">
                            <h4 class="font-medium-5 card-title mb-0"><span></span></h4>

                        </div>
                        <div class="media-right text-right">
                            <i class="ft-phone-call font-large-1 red"></i>
                        </div>
                    </div>
                </div>
                <?php
                $format = 'd.m';
                $startDate = new \DateTime($start);
                $endDate = new \DateTime($end);

                $interval = new \DateInterval('P1D');
                $period = new \DatePeriod ($startDate, $interval, $endDate);
                $dates = [];
                $call_days = [];
                foreach ($period as $key => $date) {
                    $call_days[$date->format('Ymd')] = 0;
                    $dates[] = $date->format($format);
                }

                foreach ($items as $key => $item) {
                    $call_days[date('Ymd', $item['start'])]++;
                }
                if( count($call_days) <= 10 ) { ?>
                <div
                    id="line-chart2"
                    class="height-300 lineChart2 lineChart2Shadow mb-4"
                    data-x='<?= json_encode($dates); ?>'
                    data-values='<?= json_encode(array_values($call_days)); ?>'
                >
                </div>
                <?php } ?>
                <div
                    id="ct-chart"
                    class="height-200 mb-2"
                    data-data='<?= json_encode([
                        [
                            'value' => $out,
                            'className' => 'colorPieOut',
                        ],
                        [
                            'value' => $in,
                            'className' => 'colorPieIn',
                        ],
                        [
                            'value' => $missed,
                            'className' => 'colorPieMissed',
                        ],
                    ]); ?>'
                    data-summ='<?= array_sum([$out,$in,$missed]); ?>'
                >
                </div>
            </div>
        </div>
    </div>
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