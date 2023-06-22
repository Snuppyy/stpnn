<?php
namespace app\widgets\Components\Request;

use app\models\App\Request\RequestsProgress;
use Yii;
use yii\helpers\Html;
use \app\models\App\Request\Requests as ReqModel;
use yii\helpers\Url;
use app\models\App\Client\ClientAccess as CA;

class RequestManager extends \yii\bootstrap\Widget
{
    public $themeHead = [
        'success' => 'bar-success success',
        'danger' => 'bar-danger danger',
        'warning' => 'bar-warning warning',
        'primary' => 'bar-primary primary',
        'info' => 'bar-info info',
    ];

    public $items;
    public $title;
    public $link_add;
    public $link_add_text;
    public $in;
    public $success;
    public $purpose;
    public $action;
    public $wrap;
    public $group;

    /**
     * Initializes the widget.
     */
    public function init()
    {
        parent::init();

        $this->initOptions();
        echo Html::beginTag('div', $this->options['class_col']) . "\n";
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        echo "\n" . $this->renderBody();
        echo "\n" . Html::endTag('div');
        //$this->registerPlugin('requests');
    }

    /**
     * Initializes the widget options.
     * This method sets the default values for various options.
     */
    protected function initOptions()
    {
        Html::addCssClass($this->options['class_head'], ['card-title-wrap font-medium-3 text-bold-700']);

        if( !empty($this->options['theme']) && !empty($this->themeHead[$this->options['theme']]) )
        {
            Html::addCssClass($this->options['class_head'],
                [$this->themeHead[$this->options['theme']]]
            );
        }

        if(empty($this->title))
        {
            $this->title = 'Заявки';
        }
        if(empty($this->link_add_text))
        {
            $this->link_add_text = 'Текст ссылки';
        }

        if(empty($this->purpose))
        {
            $this->purpose = false;
        }
        if(empty($this->in))
        {
            $this->in = false;
        }
        if(empty($this->success))
        {
            $this->success = false;
        }

        if(!isset($this->action) || !empty($this->action)){
            $this->action = true;
        }else{
            $this->action = false;
        }
        if(!isset($this->wrap) || !empty($this->wrap)){
            $this->wrap = true;
            if( empty($this->options['class_col']) )
            {
                Html::addCssClass($this->options['class_col'], ['col-12']);
            }
        }else{
            $this->wrap = false;
        }
    }

    protected function renderBody()
    {
        if(!empty($this->items))
        { ?>
            <?php if($this->wrap) { ?>
            <div class="card">
                <div class="card-header">
                    <div
                        class="<?= implode(' ',$this->options['class_head']['class']); ?>">
                        <?= $this->title; ?>
                        <?php if($this->link_add) { ?>
                            <a href="<?= $this->link_add; ?>" class="btn-link link-green ml-5 addNewRequest">
                                <?= $this->link_add_text; ?>
                            </a>
                        <?php } ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-block">
            <?php } ?>
                        <div class="table-responsive">
                            <?php if($this->purpose){ ?>
                                <table class="table table-hover table-xl mb-0 text-bold-600">
                                    <thead>
                                    <tr>
                                        <th class="border-top-0 pl-1">Статус</th>
                                        <th class="border-top-0 pl-1">Название</th>
                                        <th class="border-top-0 pl-1">Контактные данные</th>
                                        <th class="border-top-0 pl-2">Сообщение</th>
                                        <th class="border-top-0 pl-1">URL</th>
                                        <th class="border-top-0 pl-1" style="width: 120px">Действие</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($this->items as $item) {
                                        $data = [];
                                        if(!empty($item['data'])){
                                            $data =  json_decode($item['data'], true);
                                        } ?>
                                        <tr data-parent="">
                                            <td class="p-1">
                                                <?= ReqModel::getStatusIcon($item['status']); ?>
                                                <a target="_blank" href="<?= Url::to(['/admin/request/view', 'id' =>
                                                    $item['id']]); ?>"
                                                   class="text-info">
                                                    <i class="ft-eye"></i>
                                                </a>
                                            </td>
                                            <td class="p-1">
                                                <?= !empty($item['org']['title']) ? $item['org']['title'] : (!empty($data['your-company']) ? stripcslashes($data['your-company'])
                                                    : 'Частник')
                                                ; ?>
                                            </td>
                                            <td class="p-1">
                                                <div>
                                                    <?= !empty($item['lastname']) ? $item['lastname'].' ' : ''; ?>
                                                    <?= !empty($item['firstname']) ? $item['firstname'].' ' : ''; ?>
                                                    <?= !empty($item['fathername']) ? $item['fathername'].' ' : ''; ?>
                                                </div>
                                                <?php if(!empty($item['connection'])){ ?>
                                                    <?php foreach ($item['connection'] as $info) {
                                                        switch ($info['field']){
                                                            case 'phone':
                                                                echo "<span class='block maskPhone'>
                                                            {$info['value']}
                                                        </span>";
                                                                break;
                                                            case 'email':
                                                                echo "<a href='mailto:{$info['value']}'>
                                                            {$info['value']}
                                                        </a>\n\r";
                                                                break;
                                                        }
                                                    } ?>
                                                <?php } ?>
                                            </td>
                                            <td class="pt-1">
                                                <?php if(!empty($data['car'])){ ?>
                                                    <span class="block">
                                                        <b class="success">Авто: </b>
                                                        <?= $data['car']; ?>
                                                    </span>
                                                <?php } ?>
                                                <?php if(!empty($data['text-399'])){ ?>
                                                    <span class="block">
                                                        <?= $data['text-399']; ?>
                                                    </span>
                                                <?php } ?>
                                                <?php if(!empty($data['your-city'])){ ?>
                                                    <span class="block">
                                                        <b class="success">Город: </b>
                                                        <?= $data['your-city']; ?>
                                                    </span>
                                                <?php } ?>

                                            </td>
                                            <td class="">
                                                <?php if(!empty($data['url'])){ ?>
                                                    <a
                                                            data-toggle="tooltip"
                                                            data-original-title="Страница заявки"
                                                            href="<?= $data['url'];?>"
                                                            target="_blank">
                                                        <i class="ft-link"></i>
                                                    </a>
                                                <?php } ?>
                                                <?php if(!empty($data['tz'])){ ?>
                                                    <a href="<?= $data['tz'];?>" target="_blank">
                                                        <i class="ft-file"></i>
                                                    </a>
                                                <?php } ?>
                                            </td>
                                            <td class="pt-1">
                                                <div class="btn-group">
                                                    <button
                                                        class="btn btn-danger btn-flat btnPurposeCancelManager"
                                                        data-toggle="tooltip"
                                                        data-original-title="Отказаться"
                                                        data-user_id="<?= $item['user_id']; ?>"
                                                        data-req_id="<?= $item['id']; ?>">
                                                        <i class="fa fa-times font-medium-2"></i>
                                                    </button>
                                                </div>
                                                <div class="btn-group">
                                                    <button class="btn btn-flat btn-success btnPurposeAccept"
                                                            data-toggle="tooltip"
                                                            data-original-title="Принять"
                                                            data-user_id="<?= $item['user_id']; ?>"
                                                            data-req_id="<?= $item['id']; ?>">
                                                        <i class="fa fa-check font-medium-2"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            <?php }elseif($this->in){
                                if( $this->group ) {
                                    $progress = array_column($this->items, 'progress');
                                    $statuses_r = array_column($progress, 'status');
                                    $progress_s = array_values(array_unique($statuses_r));
                                    $progress_l = array_values(array_unique(array_column($progress, 'label')));
                                    $trends = array_column($this->items, 'trend');
                                    $tenders = array_column($this->items, 'tender');
                                    $long_time = array_column($this->items, 'long_time');
                                    if( in_array(1, $tenders) ){
                                        $progress_s[] = 'tender';
                                        $progress_l[] = 'Тендер';
                                    }
                                    if( in_array(1, $trends) ){
                                        $progress_s[] = 'trend';
                                        $progress_l[] = 'Избранные';
                                    }
                                    if( in_array(1, $long_time) ){
                                        $progress_s[] = 'long_time';
                                        $progress_l[] = 'Долгосрочные';
                                    }

                                ?>
                                <div class="nav-work-stat">
                                    <?php
                                    $progress_counts = [];
                                    $profit_buys = [];
                                    $progress_s = array_map('strval', $progress_s);
                                    ob_start(); ?>
                                    <div class="tab-content px-1 pt-1"> <?php
                                    foreach ($progress_l as $k_l => $label) { $reqests = []; ?>
                                        <div role="tabpanel" class="tab-pane<?= $k_l == 0 ? ' active show' : ''; ?>" id="work_stat<?= $progress_s[$k_l]; ?>" aria-labelledby="home-tab3" aria-expanded="<?= $k_l == 0 ? 'true' : 'false'; ?>">
                                            <?php
                                            switch ($progress_s[$k_l]){
                                                case 'trend':
                                                    $k_reqests = array_keys($trends, 1);
                                                    foreach ($k_reqests as $k_reqest) {
                                                        $reqests[] = $this->items[$k_reqest];
                                                    }
                                                    break;
                                                case 'tender':
                                                    $k_reqests = array_keys($tenders, 1);
                                                    foreach ($k_reqests as $k_reqest) {
                                                        $reqests[] = $this->items[$k_reqest];
                                                    }
                                                    break;
                                                case 'long_time':
                                                    $k_reqests = array_keys($long_time, 1);
                                                    foreach ($k_reqests as $k_reqest) {
                                                        $reqests[] = $this->items[$k_reqest];
                                                    }
                                                    break;
                                                case '5':
                                                    $k_reqests = array_keys($statuses_r, $progress_s[$k_l]);
                                                    foreach ($k_reqests as $k_reqest) {
                                                        $reqests[] = $this->items[$k_reqest];
                                                    }
                                                    break;
                                                case '0':
                                                default:
                                                    $k_reqests = array_keys($statuses_r, $progress_s[$k_l]);
                                                    foreach ($k_reqests as $k_reqest) {
                                                        if( empty($this->items[$k_reqest]['tender']) && empty($this->items[$k_reqest]['trend']) && empty($this->items[$k_reqest]['long_time']) )
                                                            $reqests[] = $this->items[$k_reqest];
                                                    }
                                                    break;
                                            }
                                            $profit_buys[$k_l] = array_sum(array_column($reqests, 'profit_buy'));
                                            ?>
                                            <table class="table table-hover table-xl mb-0 mt-0">
                                                <thead>
                                                <tr>
                                                    <th colspan="7" class="totalIncome success text-right">Прибыль: <?= number_format($profit_buys[$k_l], 2, '.', ' '); ?>руб.</th>
                                                </tr>
                                                <tr>
                                                    <th class="border-top-0 pl-1">Статус</th>
                                                    <th class="border-top-0 pl-1" style="width: 15%">Название</th>
                                                    <th class="border-top-0 pl-1" style="width:180px">Контактные данные</th>
                                                    <th class="border-top-0 pl-2">Событие</th>
                                                    <th class="border-top-0 pl-1">Сум.контракта</th>
                                                    <th class="border-top-0 pl-1">Дополнительно</th>
                                                    <th class="border-top-0 pl-1">Действие</th>
                                                </tr>

                                                </thead>
                                                <tbody>
                                                <?php $colors = RequestsProgress::getStatusesColor(); ?>
                                                <?php $sort_ids = array_column($reqests, 'id');
                                                array_multisort($sort_ids, SORT_NUMERIC,SORT_DESC, $reqests);
                                                $progress_counts[] = count($reqests);
                                                foreach ($reqests as $item) { ?>
                                                    <?php if( !empty($item['data']) ) {
                                                        $data = json_decode($item['data'], true);
                                                    } ?>
                                                    <tr data-parent=""<?= !empty($item['trend']) ? ' data-trend="yes"' : ''; ?> class="<?= isset($item['calls']) && $item['calls'] < 2 ? 'bg-warning' : ''; ?>">
                                                        <td class="p-1">
                                                            ID-<?=$item['id']; ?><br>
                                                            <?= ReqModel::getStatusIcon($item['status']); ?>
                                                            <a target="_blank" href="<?= Url::to(['/admin/request/view', 'id' =>
                                                                $item['id']]); ?>"
                                                               class="text-info">
                                                                <i class="ft-eye"></i>
                                                            </a>
                                                            <span class="d-block font-small-2 black my-2 form-group">
                                                                <input data-id="<?= $item['id']; ?>"<?= !empty($item['long_time']) ? ' checked' : '' ; ?> type="checkbox" class="switchery switcheryLongReq"
                                                                       data-size="sm"/>
                                                                <label class="ml-1">Долго.кл.</label>
                                                            </span>
                                                        </td>
                                                        <td class="p-1">
                                                            <?= !empty($item['org']['title']) ? $item['org']['title'] : (!empty($data['your-company']) ? stripcslashes($data['your-company'])
                                                                : 'Частник')
                                                            ; ?>
                                                            <?= !empty($item['lizing']) ? '<br/>Лизинг: '.$item['lizing'] : ''; ?>
                                                        </td>
                                                        <td class="p-1">
                                                            <div>
                                                                <?= !empty($item['lastname']) ? $item['lastname'].' ' : ''; ?>
                                                                <?= !empty($item['firstname']) ? $item['firstname'].' ' : ''; ?>
                                                                <?= !empty($item['fathername']) ? $item['fathername'].' ' : ''; ?>
                                                            </div>
                                                            <?php if(!empty($item['connection'])){ ?>
                                                                <?php foreach ($item['connection'] as $info) {
                                                                    switch ($info['field']){
                                                                        case 'phone':
                                                                            echo "<span class='block maskPhone'>
                                                        {$info['value']}
                                                    </span>";
                                                                            break;
                                                                        case 'email':
                                                                            echo "<a href='mailto:{$info['value']}'>
                                                        {$info['value']}
                                                    </a>\n\r";
                                                                            break;
                                                                    }
                                                                } ?>
                                                            <?php } ?>
                                                        </td>
                                                        <td class="pt-2">
                                                            <div class="progress mb-0">
                                                                <div
                                                                        class="progress-bar progress-bar-striped progress-bar-animated <?= $colors[$item['progress']['status']]; ?>"
                                                                        role="progressbar"
                                                                        aria-valuenow="<?= $item['progress']['percent'] ?>"
                                                                        aria-valuemin="<?= $item['progress']['percent'] ?>"
                                                                        aria-valuemax="100" style="width:<?= $item['progress']['percent']?>%"></div>
                                                            </div>
                                                            <span><?= $item['progress']['label'] ?></span>
                                                        </td>
                                                        <td class="pt-1">
                                                            <div class="success">
                                                                Сумма:
                                                            </div>
                                                            <?= number_format($item['profit'],2,'.',' '); ?> &#8381;
                                                            <div class="mt-1 warning">
                                                                Оплачено:
                                                            </div>
                                                            <?= number_format($item['amount'],2,'.',' '); ?> &#8381;
                                                            <?php if($item['profit'] != $item['amount']){ ?>
                                                                <div class="mt-1 danger">
                                                                    Долг:
                                                                </div>
                                                                <?= number_format($item['profit']-$item['amount'],2,'.',' '); ?> &#8381;
                                                            <?php } ?>
                                                        </td>
                                                        <td class="pt-1">
                                                            от <?= date('d.m.Y', $item['create_at']); ?>
                                                            <?= !empty($item['title']) ? "<div>".$item['title']."</div>" : ''; ?>
                                                            <?php if(!empty($item['upd'])){ $_colors = ['default', 'danger', 'orange', 'danger'];?>
                                                                <div class="d-block">
                                                    <span class="<?=
                                                    $_colors[\app\models\App\Request\Requests::getTypeRequest($item['id'])];
                                                    ?>">УПД: </span>
                                                                    <?= $item['upd']['number']; ?> от
                                                                    <?= date('d.m.Y', $item['upd']['date']); ?>
                                                                </div>
                                                            <?php } ?>
                                                            <?php if(!empty($item['progress']['pays'])){ ?>
                                                                <div class="d-block">
                                                                    <span class="danger">Даты оплат: </span>
                                                                    <?php foreach ($item['progress']['pays']['dates'] as $key => $date) { ?>
                                                                        <?= $date; ?>
                                                                        <?= $key+1 != count($item['progress']['pays']['dates']) ? '<br/>'
                                                                            : ''; ?>
                                                                    <?php } ?>
                                                                </div>
                                                            <?php } ?>
                                                            <?php if(!empty($data['your-city'])){ ?>
                                                                <span class="block">
                                                                    <b class="success">Город: </b>
                                                                    <?= $data['your-city']; ?>
                                                                </span>
                                                            <?php } ?>
                                                            <?php if(!empty($data['tz'])){ ?>
                                                                <a href="<?= $data['tz'];?>" target="_blank">
                                                                    <i class="ft-file"></i>
                                                                </a>
                                                            <?php } ?>
                                                        </td>
                                                        <td class="pt-1" style="width: 200px;">
                                                            <div class="btn-group">

                                                                <a
                                                                        href="<?= Url::to(['/admin/request/view','id' => $item['id']]); ?>"
                                                                        class="btn btn-info px-2 btn-round"
                                                                        target="_blank"
                                                                        title="">
                                                                    Открыть
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <th colspan="7" class="totalIncome success text-right">Прибыль: <?= number_format($profit_buys[$k_l], 2, '.', ' '); ?>руб.</th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    <?php }
                                    ?>
                                    </div><?php
                                    $out = ob_get_clean();
                                    ?>
                                    <ul class="nav nav-tabs  nav-justified reqNav">
                                        <?php foreach ($progress_l as $k_l => $label) { ?>
                                            <li class="nav-item">
                                                <a class="nav-link <?= $k_l == 0 ? ' active' : ''; ?>" data-toggle="tab" aria-controls="tabVerticalLeft1" href="#work_stat<?= $progress_s[$k_l]; ?>" aria-expanded="<?= $k_l == 0 ? 'true' : 'false'; ?>"><?= $label; ?>&nbsp;(<?= $progress_counts[$k_l]; ?>)</a>
                                            </li>
                                            <?php $progress_s[$k_l] = strval($progress_s[$k_l]); ?>
                                        <?php } ?>
                                    </ul>
                                    <?= $out; ?>
                                </div>
                                <?php } else { ?>
                                <a class="btn showTrend btn-success" href="#">Показать избранные</a>
                                <table class="table table-hover table-xl mb-0 mt-0">
                                    <thead>
                                    <tr>
                                        <th class="border-top-0 pl-1">Статус</th>
                                        <th class="border-top-0 pl-1" style="width: 15%">Название</th>
                                        <th class="border-top-0 pl-1" style="width:180px">Контактные данные</th>
                                        <th class="border-top-0 pl-2">Событие</th>
                                        <th class="border-top-0 pl-1">Сум.контракта</th>
                                        <th class="border-top-0 pl-1">Дополнительно</th>
                                        <th class="border-top-0 pl-1">Действие</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php $colors = RequestsProgress::getStatusesColor(); ?>
                                    <?php foreach ($this->items as $item) {  ?>
                                        <?php if( !empty($item['data']) ) {
                                            $data = json_decode($item['data'], true);
                                        } ?>
                                        <tr data-parent=""<?= !empty($item['trend']) ? ' data-trend="yes"' : ''; ?>>
                                            <td class="p-1">
                                                ID-<?=$item['id']; ?><br>
                                                <?= ReqModel::getStatusIcon($item['status']); ?>
                                                <a target="_blank" href="<?= Url::to(['/admin/request/view', 'id' =>
                                                    $item['id']]); ?>"
                                                   class="text-info">
                                                    <i class="ft-eye"></i>
                                                </a>
                                            </td>
                                            <td class="p-1">
                                                <?= !empty($item['org']['title']) ? $item['org']['title'] : (!empty($data['your-company']) ? stripcslashes($data['your-company'])
                                                    : 'Частник')
                                                ; ?>
                                                <?= !empty($item['lizing']) ? '<br/>Лизинг: '.$item['lizing'] : ''; ?>
                                            </td>
                                            <td class="p-1">
                                                <div>
                                                    <?= !empty($item['lastname']) ? $item['lastname'].' ' : ''; ?>
                                                    <?= !empty($item['firstname']) ? $item['firstname'].' ' : ''; ?>
                                                    <?= !empty($item['fathername']) ? $item['fathername'].' ' : ''; ?>
                                                </div>
                                                <?php if(!empty($item['connection'])){ ?>
                                                    <?php foreach ($item['connection'] as $info) {
                                                        switch ($info['field']){
                                                            case 'phone':
                                                                echo "<span class='block maskPhone'>
                                                            {$info['value']}
                                                        </span>";
                                                                break;
                                                            case 'email':
                                                                echo "<a href='mailto:{$info['value']}'>
                                                            {$info['value']}
                                                        </a>\n\r";
                                                                break;
                                                        }
                                                    } ?>
                                                <?php } ?>
                                            </td>
                                            <td class="pt-2">
                                                <div class="progress mb-0">
                                                    <div
                                                            class="progress-bar progress-bar-striped progress-bar-animated <?= $colors[$item['progress']['status']]; ?>"
                                                            role="progressbar"
                                                            aria-valuenow="<?= $item['progress']['percent'] ?>"
                                                            aria-valuemin="<?= $item['progress']['percent'] ?>"
                                                            aria-valuemax="100" style="width:<?= $item['progress']['percent']?>%"></div>
                                                </div>
                                                <span><?= $item['progress']['label'] ?></span>
                                            </td>
                                            <td class="pt-1">
                                                <div class="success">
                                                    Сумма:
                                                </div>
                                                <?= number_format($item['profit'],2,'.',' '); ?> &#8381;
                                                <div class="mt-1 warning">
                                                    Оплачено:
                                                </div>
                                                <?= number_format($item['amount'],2,'.',' '); ?> &#8381;
                                                <?php if($item['profit'] != $item['amount']){ ?>
                                                    <div class="mt-1 danger">
                                                        Долг:
                                                    </div>
                                                    <?= number_format($item['profit']-$item['amount'],2,'.',' '); ?> &#8381;
                                                <?php } ?>
                                            </td>
                                            <td class="pt-1">
                                                от <?= date('d.m.Y', $item['create_at']); ?>
                                                <?= !empty($item['title']) ? "<div>".$item['title']."</div>" : ''; ?>
                                                <?php if(!empty($item['upd'])){ $_colors = ['default', 'danger', 'orange', 'danger'];?>
                                                    <div class="d-block">
                                                        <span class="<?=
                                                        $_colors[\app\models\App\Request\Requests::getTypeRequest($item['id'])];
                                                        ?>">УПД: </span>
                                                        <?= $item['upd']['number']; ?> от
                                                        <?= date('d.m.Y', $item['upd']['date']); ?>
                                                    </div>
                                                <?php } ?>
                                                <?php if(!empty($item['progress']['pays'])){ ?>
                                                    <div class="d-block">
                                                        <span class="danger">Даты оплат: </span>
                                                        <?php foreach ($item['progress']['pays']['dates'] as $key => $date) { ?>
                                                            <?= $date; ?>
                                                            <?= $key+1 != count($item['progress']['pays']['dates']) ? '<br/>'
                                                                : ''; ?>
                                                        <?php } ?>
                                                    </div>
                                                <?php } ?>
                                                <?php if(!empty($data['your-city'])){ ?>
                                                    <span class="block">
                                                        <b class="success">Город: </b>
                                                        <?= $data['your-city']; ?>
                                                    </span>
                                                <?php } ?>
                                                <?php if(!empty($data['tz'])){ ?>
                                                    <a href="<?= $data['tz'];?>" target="_blank">
                                                        <i class="ft-file"></i>
                                                    </a>
                                                <?php } ?>
                                            </td>
                                            <td class="pt-1" style="width: 200px;">
                                                <div class="btn-group">

                                                    <a
                                                        href="<?= Url::to(['/admin/request/view','id' => $item['id']]); ?>"
                                                        class="btn btn-info px-2 btn-round"
                                                        target="_blank"
                                                        title="">
                                                        Открыть
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
                                <?php } ?>
                            <?php }elseif($this->success){ ?>
                                <table class="table table-hover table-xl mb-0 mt-0">
                                    <thead>
                                    <tr>
                                        <th class="border-top-0 pl-1">Статус</th>
                                        <th class="border-top-0 pl-1" style="width: 15%">Название</th>
                                        <th class="border-top-0 pl-1" style="width:180px">Контактные данные</th>
                                        <!--<th class="border-top-0 pl-2">Событие</th>-->
                                        <?php if($this->action){ ?>
                                        <th class="border-top-0 pl-1">Сум.контракта</th>
                                        <th class="border-top-0 pl-1">Дополнительно</th>
                                        <th class="border-top-0 pl-1">Действие</th>
                                        <?php }else{ ?>
                                        <th class="border-top-0 pl-1">Дата создания</th>
                                        <th class="border-top-0 pl-1">Сум.контракта</th>
                                        <th class="border-top-0 pl-1">Дополнительно</th>
                                        <?php } ?>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php $colors = RequestsProgress::getStatusesColor(); ?>
                                    <?php foreach ($this->items as $item) { $data = json_decode($item['data'], true); ?>
                                        <tr data-parent="">
                                            <td class="p-1">
                                                <?= ReqModel::getStatusIcon($item['status']); ?>
                                                <a target="_blank" href="<?= Url::to(['/admin/request/view', 'id' =>
                                                    $item['id']]); ?>"
                                                   class="text-info">
                                                    <i class="ft-eye"></i>
                                                </a>
                                            </td>
                                            <td class="p-1">
                                                <?= !empty($item['org']['title']) ? $item['org']['title'] : (!empty($data['your-company']) ? stripcslashes($data['your-company'])
                                                    : 'Частник')
                                                ; ?>
                                                <?= !empty($item['lizing']) ? '<br/>Лизинг: '.$item['lizing'] : ''; ?>
                                            </td>
                                            <td class="p-1">
                                                <div>
                                                    <?= !empty($item['lastname']) ? $item['lastname'].' ' : ''; ?>
                                                    <?= !empty($item['firstname']) ? $item['firstname'].' ' : ''; ?>
                                                    <?= !empty($item['fathername']) ? $item['fathername'].' ' : ''; ?>
                                                </div>
                                                <?php if(!empty($item['connection'])){ ?>
                                                    <?php foreach ($item['connection'] as $info) {
                                                        switch ($info['field']){
                                                            case 'phone':
                                                                echo "<span class='block maskPhone'>
                                                            {$info['value']}
                                                        </span>";
                                                                break;
                                                            case 'email':
                                                                echo "<a href='mailto:{$info['value']}'>
                                                            {$info['value']}
                                                        </a>\n\r";
                                                                break;
                                                        }
                                                    } ?>
                                                <?php } ?>
                                            </td>
                                            <? /*<td class="pt-2">
                                                <div class="progress mb-0">
                                                    <div
                                                            class="progress-bar progress-bar-striped progress-bar-animated <?= $colors[$item['progress']['status']]; ?>"
                                                            role="progressbar"
                                                            aria-valuenow="<?= $item['progress']['percent'] ?>"
                                                            aria-valuemin="<?= $item['progress']['percent'] ?>"
                                                            aria-valuemax="100" style="width:<?= $item['progress']['percent']?>%"></div>
                                                </div>
                                                <span><?= $item['progress']['label'] ?></span>
                                            </td> */ ?>
                                            <?php if (!$this->action) { ?>
                                            <td class="pt-1">
                                                <?= date('d.m.Y', $item['create_at']); ?>
                                            </td>
                                            <?php } ?>
                                            <td class="pt-1">
                                                <div class="success">
                                                    Сумма:
                                                </div>
                                                <?= number_format($item['profit'],2,'.',' '); ?> &#8381;
                                                <?php if(!empty($item['date_end'])) { /* ?>
                                                    <div class="mt-1 warning">
                                                        Оплачено:
                                                    </div>
                                                    <?= number_format($item['amount'],2,'.',' '); ?> &#8381;
                                                <?php */} ?>
                                            </td>
                                            <td class="pt-1">
                                                <?= !empty($item['title']) ? "<div>".$item['title']."</div>" : ''; ?>
                                                <?php if(!empty($item['upd'])){ $_colors = ['default', 'danger', 'orange'];?>
                                                    <div class="d-block">
                                                        <span class="<?=
                                                        $_colors[\app\models\App\Request\Requests::getTypeRequest($item['id'])];
                                                        ?>">УПД: </span>
                                                        <?= $item['upd']['number']; ?> от
                                                        <?= date('d.m.Y', $item['upd']['date']); ?>
                                                    </div>
                                                <?php } ?>
                                                <?php if(!empty($item['vin'])){ ?>
                                                    <div class="d-block">
                                                        <span class="info">VIN: <?= $item['vin']; ?></span>
                                                    </div>
                                                <?php } ?>
                                                <?php if(!empty($data['your-city'])){ ?>
                                                    <span class="block">
                                                        <b class="success">Город: </b>
                                                        <?= $data['your-city']; ?>
                                                    </span>
                                                <?php } ?>
                                                <?php if(!empty($data['tz'])){ ?>
                                                    <a href="<?= $data['tz'];?>" target="_blank">
                                                        <i class="ft-file"></i>
                                                    </a>
                                                <?php } ?>
                                            </td>
                                            <?php if($this->action){ ?>
                                            <td class="pt-1" style="width: 200px;">
                                                <div class="btn-group">

                                                    <a
                                                        href="<?= Url::to(['/admin/request/view','id' => $item['id']]); ?>"
                                                        class="btn btn-warning pl-4 pr-4 btn-round"
                                                        title="">
                                                        Открыть
                                                    </a>
                                                </div>
                                            </td>
                                            <?php } ?>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            <?php }else{ ?>

                            <?php } ?>
                        </div>
            <?php if($this->wrap) { ?>
                    </div>
                </div>
            </div>
            <?php } ?>
        <?php }elseif($this->in){ ?>
            <div class="card">
                <div class="card-header">
                    <div
                            class="<?= implode(' ',$this->options['class_head']['class']); ?>">
                        <?= $this->title; ?>
                        <?php if($this->link_add) { ?>
                            <a href="<?= $this->link_add; ?>" class="btn-link link-green ml-5 addNewRequest">
                                <?= $this->link_add_text; ?>
                            </a>
                        <?php } ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-block">

                    </div>
                </div>
            </div>
        <?php }
    }

}