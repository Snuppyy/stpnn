<?php

namespace app\models\App\Request;

use app\components\AppFunctions;
use app\components\SendEmails;
use app\components\SmsDev;
use app\models\App\Client\Client;
use app\models\App\Client\ClientAccess;
use app\models\App\Client\ClientConnection;
use app\models\App\Client\ClientLizing;
use app\models\App\Client\ClientLizingRel;
use app\models\App\Client\ClientOrganization;
use app\models\App\Client\ClientOrganizationRel;
use app\models\App\Client\ClientTrend;
use app\models\App\RequestsPost;
use app\models\App\UserNote;
use app\models\App\UserProfile;
use app\models\Basic\Megafon\MegafonHistory;
use app\models\User;
use app\modules\admin\Module;
use Codeception\Module\Cli;
use GuzzleHttp\Psr7\Request;
use Yii;
use yii\base\Model;

/**
 * This is the model class for table "{{%requests}}".
 *
 * @property int $id
 * @property int $status
 * @property int $title
 * @property int $client_id
 * @property int $user_id
 * @property int $create_at
 * @property int $update_at
 * @property int $trash
 * @property int $tender
 * @property int $qtender
 * @property int $status_work
 * @property int $date_end
 * @property string $file
 * @property RequestsUpd $upd
 */
class Requests extends \yii\db\ActiveRecord
{
    //---Статусы от куда
    const STATUS_SITE = 0;
    const STATUS_SITE_TEXT = 'Сайт';
    const STATUS_SITE_ICONS = '<i class="ft-at-sign success font-medium-1"></i>';

    const STATUS_MOBILE = 1;
    const STATUS_MOBILE_TEXT = 'Мобильный';
    const STATUS_MOBILE_ICONS = '<i class="icon-screen-smartphone success font-medium-1"></i>';

    const STATUS_PHONE = 2;
    const STATUS_PHONE_TEXT = 'Городской';
    const STATUS_PHONE_ICONS = '<i class="ft-phone success font-medium-1"></i>';

    //Статусы работы
    const STATUS_WORK_NEW = 0;
    const STATUS_WORK_NEW_TEXT = 'Новая';

    const STATUS_WORK_IN = 1;
    const STATUS_WORK_IN_TEXT = 'В работе';

    const STATUS_WORK_PURPOSE = 2;
    const STATUS_WORK_PURPOSE_TEXT = 'Назначенная';

    const STATUS_WORK_NOT_PURPOSE = 3;
    const STATUS_WORK_NOT_PURPOSE_TEXT = 'Не прниятая';

    const STATUS_WORK_REMOVE = 5;
    const STATUS_WORK_REMOVE_TEXT = 'Снятые';

    const STATUS_WORK_SUCCESS = 4;
    const STATUS_WORK_SUCCESS_TEXT = 'Завершенная';

    const MESSAGE_NOT_TEXT = 'Не определено';
    const MESSAGE_NOT_ICONS = '<i class="icon-cursor success font-medium-1"></i>';

    public $_trash = 0;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%requests}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'client_id', 'user_id', 'create_at', 'update_at', 'date_end', 'trash', 'tender', 'qtender', 'long_time'], 'integer'],
            [['client_id'], 'required'],
            [['title','vin'], 'string', 'max' => 255],
            [['file'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'status' => Yii::t('app', 'Status'),
            'title' => Yii::t('app', 'Title'),
            'client_id' => Yii::t('app', 'Client ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'create_at' => Yii::t('app', 'Create At'),
            'update_at' => Yii::t('app', 'Update At'),
            'trash' => Yii::t('app', 'Trash'),
            'date_end' => Yii::t('app', 'Date End'),
            'vin' => Yii::t('app', 'VIN'),
            'tender' => 'Тендер',
            'qtender' => 'Опрос о тендере',
        ];
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if($insert == 1)
        {
            $this->create_at = strtotime("now");
        }
        $this->update_at = strtotime("now");

        return parent::beforeSave($insert);
    }

    /**
     * @return array
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_SITE => self::STATUS_SITE_TEXT,
            self::STATUS_MOBILE => self::STATUS_MOBILE_TEXT,
            self::STATUS_PHONE => self::STATUS_PHONE_TEXT,
        ];
    }

    /**
     * @return array
     */
    public static function getStatusesIcons()
    {
        return [
            self::STATUS_SITE => self::STATUS_SITE_ICONS,
            self::STATUS_MOBILE => self::STATUS_MOBILE_ICONS,
            self::STATUS_PHONE => self::STATUS_PHONE_ICONS,
        ];
    }

    /**
     * @return string
     */
    public static function getStatusText($status = 0)
    {
        $statuses = self::getStatuses();
        return $statuses[$status] ?? self::MESSAGE_NOT_TEXT;
    }
    /**
     * @return string
     */
    public static function getStatusIcon($status = 0)
    {
        $statuses = self::getStatusesIcons();
        return $statuses[$status] ?? self::MESSAGE_NOT_ICONS;
    }

    /**
     * @return array
     */
    public static function getStatusesWork()
    {
        return [
            self::STATUS_WORK_NEW => self::STATUS_WORK_NEW_TEXT,
            self::STATUS_WORK_IN => self::STATUS_WORK_IN_TEXT,
            self::STATUS_WORK_PURPOSE => self::STATUS_WORK_PURPOSE_TEXT,
            self::STATUS_WORK_NOT_PURPOSE => self::STATUS_WORK_NOT_PURPOSE_TEXT,
            self::STATUS_WORK_SUCCESS => self::STATUS_WORK_SUCCESS_TEXT,
            self::STATUS_WORK_REMOVE => self::STATUS_WORK_REMOVE_TEXT,
        ];
    }

    /**
     * @return string
     */
    public function getStatusesWorkText()
    {
        $statuses = self::getStatusesWork();
        return $statuses[$this->status_work] ?? self::MESSAGE_NOT_TEXT;
    }

    /**
     * @return string
     */
    public static function getStStatusesWorkText($status)
    {
        $statuses = self::getStatusesWork();
        return $statuses[$status] ?? self::MESSAGE_NOT_TEXT;
    }

    public static function getOrgInfoForClient($client_id)
    {
        $result = [];
        $rel = ClientOrganizationRel::find()->where(['client_id' => $client_id, 'trash' => 0])
            ->asArray()->orderBy(['id' => SORT_DESC])->one();
        if(!empty($rel)){
            $result = ClientOrganization::find()->where(['id' => $rel['element_id'],'trash' => 0])
                ->asArray()->one();
        }
        return $result;
    }

    /**
     * @param bool $client_id
     * @return $this|array|\yii\db\ActiveRecord[]
     */
    public static function getListNew($client_id = FALSE)
    {
        $rows = self::find()
            ->from(self::tableName().' as r')
            ->select('r.*,rp.data,c.firstname,c.fathername,c.lastname')
            ->innerJoin(
                RequestsPost::tableName().' as rp',
                "rp.requests_id=r.id AND rp.trash=0")
            ->innerJoin(
                Client::tableName().' as c',
                "r.client_id=c.id")
            ->where(['status_work' => self::STATUS_WORK_NEW, 'r.trash' => 0])
            ->orderBy(['r.id' => SORT_DESC])
            ->asArray();
        if($client_id !== FALSE){
            $rows = $rows->andWhere(['r.client_id' => $client_id]);
        }
        $rows = $rows->all();
        if(!empty($rows))
        {
            foreach ($rows as $key => $row)
            {
                $contacts_info = ClientConnection::find()
                    ->select('value,field')
                    ->where(['client_id' => $row['client_id'], 'trash' => 0])
                    ->orderBy(['field' => SORT_DESC])
                    ->asArray()->all();
                if(!empty($contacts_info))
                {
                    $rows[$key]['connection'] = $contacts_info;
                }
                $rows[$key]['org'] = self::getOrgInfoForClient($row['client_id']);
                if(!empty($rows[$key]['org'])){
                    $lizing = ClientLizing::find()
                        ->from(ClientLizing::tableName()." as cl")
                        ->select('cl.title')
                        ->innerJoin(ClientLizingRel::tableName()." as clr", "clr.request_id = {$row['id']} AND clr.client_id={$row['client_id']} AND cl.id=clr.element_id")
                        ->asArray()
                        ->one();
                    if(!empty($lizing)){
                        $rows[$key]['lizing'] = $lizing['title'];
                    }
                }
                $last_manager = UserProfile::find()
                    ->from(UserProfile::tableName()." as up")
                    ->select('up.full_name')
                    ->andWhere('up.trash=0')
                    ->innerJoin(ClientAccess::tableName()." as ca", "up.user_id=ca.user_id AND ca.trash=0")
                    ->asArray()
                    ->orderBy(["ca.id" => SORT_DESC])
                    ->andWhere("ca.client_id={$row['client_id']}")
                    ->one();
                if( !empty($last_manager) )
                {
                    $rows[$key]['last_manager'] = $last_manager['full_name'];
                }

            }
        }
        return $rows;
    }
    public static function getListRemove($client_id = FALSE)
    {
        $rows = self::find()
            ->from(self::tableName().' as r')
            ->select('r.*,rp.data,c.firstname,c.fathername,c.lastname,p.user_id')
            ->innerJoin(
                RequestsPost::tableName().' as rp',
                "rp.requests_id=r.id AND rp.trash=0")
            ->innerJoin(
                Client::tableName().' as c',
                "r.client_id=c.id")
            ->innerJoin(
                RequestsPurpose::tableName().' as p',
                "r.id=p.requests_id and p.status=0")
            ->where(['status_work' => self::STATUS_WORK_REMOVE, 'r.trash' => 0])
            ->orderBy(['r.id' => SORT_DESC, 'p.id' => SORT_DESC])
            ->asArray();
        if($client_id !== FALSE){
            $rows = $rows->andWhere(['r.client_id' => $client_id]);
        }
        $rows = $rows->all();
        if(!empty($rows))
        {
            foreach ($rows as $key => $row)
            {
                $contacts_info = ClientConnection::find()
                    ->select('value,field')
                    ->where(['client_id' => $row['client_id'], 'trash' => 0])
                    ->orderBy(['field' => SORT_DESC])
                    ->asArray()->all();
                if(!empty($contacts_info))
                {
                    $rows[$key]['connection'] = $contacts_info;
                }
                $rows[$key]['manager'] = UserProfile::getFullName($row['user_id']);
                $rows[$key]['org'] = self::getOrgInfoForClient($row['client_id']);
                if(!empty($rows[$key]['org'])){
                    $lizing = ClientLizing::find()
                        ->from(ClientLizing::tableName()." as cl")
                        ->select('cl.title')
                        ->innerJoin(ClientLizingRel::tableName()." as clr", "clr.request_id = {$row['id']} AND clr.client_id={$row['client_id']} AND cl.id=clr.element_id")
                        ->asArray()
                        ->one();
                    if(!empty($lizing)){
                        $rows[$key]['lizing'] = $lizing['title'];
                    }
                }
            }
        }
        return $rows;
    }

    /**
     * @param bool $client_id
     * @return $this|array|\yii\db\ActiveRecord[]
     */
    public static function getListIn($client_id = FALSE)
    {
        $page = Yii::$app->request->get('page');
        $user_id = Yii::$app->request->get('user_id');
        $offset = 0;
        $count = Yii::$app->params['per_page_requests'];
        if( !empty($page) && $page != 1 )
        {
            $offset = $count*($page-1);
        }
        if( empty($user_id) )
        {
            $rows = self::find()
                ->from(self::tableName().' as r')
                ->select('r.*,c.firstname,c.fathername,c.lastname,p.user_id, rp.data')
                ->innerJoin(
                    Client::tableName().' as c',
                    "r.client_id=c.id")
                ->innerJoin(
                    RequestsPost::tableName().' as rp',
                    "rp.requests_id=r.id AND rp.trash=0")
                ->innerJoin(
                    RequestsPurpose::tableName().' as p',
                    "r.id=p.requests_id and p.status=0")
                ->where(['status_work' => self::STATUS_WORK_IN, 'p.trash' => 0, 'r.trash' => 0])
                ->orderBy(['r.id' => SORT_DESC])
                ->asArray();
        }
        else
        {
            $rows = self::find()
                ->from(self::tableName().' as r')
                ->select('r.*,rp.data,c.firstname,c.fathername,c.lastname, p.user_id')
                ->innerJoin(
                    RequestsPost::tableName().' as rp',
                    "rp.requests_id=r.id AND rp.trash=0")
                ->innerJoin(
                    Client::tableName().' as c',
                    "r.client_id=c.id")
                ->innerJoin(
                    RequestsPurpose::tableName().' as p',
                    "r.id=p.requests_id and p.user_id={$user_id}")
                ->where(['status_work' => self::STATUS_WORK_IN, 'p.trash' => 0, 'r.trash' => 0])
                ->asArray()
                ->orderBy(['r.id' => SORT_DESC]);
        }
        if($client_id !== FALSE){
            $rows = $rows->andWhere(['r.client_id' => $client_id]);
        }

        $rows = $rows->groupBy(['r.id'])->offset($offset)->limit($count)->all();
        if(!empty($rows))
        {
            foreach ($rows as $key => $row)
            {
                $contacts_info = ClientConnection::find()
                    ->select('value,field')
                    ->where(['client_id' => $row['client_id'], 'trash' => 0])
                    ->orderBy(['field' => SORT_DESC])
                    ->asArray()->all();
                if(!empty($contacts_info))
                {
                    $rows[$key]['connection'] = $contacts_info;
                }

                $rows[$key]['manager'] = UserProfile::getFullName($row['user_id']);

                $calc = RequestsCalculator::find()
                    ->from(RequestsCalculator::tableName()." as rc")
                    ->select('rci.sale, rci.buy')
                    ->innerJoin(RequestsCalculatorItem::tableName(). " as rci",
                        "rci.calc_id=rc.id")
                    ->where([
                        'rc.requests_id' => $row['id'],
                        'rci.trash' => 0,
                        'rc.trash' => 0
                    ])
                    ->asArray()->all();

                if(!empty($calc)){
                    $_calc = array_column($calc,'sale');
                    $rows[$key]['profit'] = array_sum($_calc);
                    $_calc = array_column($calc,'buy');
                    $rows[$key]['buy'] = array_sum($_calc);
                    $rows[$key]['profit_buy'] = $rows[$key]['profit']-$rows[$key]['buy'];
                }else{
                    $rows[$key]['profit'] = 0;
                }
                $rows[$key]['amount'] = RequestsPayment::getTotalAmount($row['id']);
                $rows[$key]['upd'] = RequestsUpd::get($row['id']);
                $rows[$key]['manager'] = UserProfile::getFullName($row['user_id']);
                $rows[$key]['progress'] = RequestsProgress::getStatus($row['id']);
                $rows[$key]['org'] = self::getOrgInfoForClient($row['client_id']);
                if(!empty($rows[$key]['org'])){
                    $lizing = ClientLizing::find()
                        ->from(ClientLizing::tableName()." as cl")
                        ->select('cl.title')
                        ->innerJoin(ClientLizingRel::tableName()." as clr", "clr.request_id = {$row['id']} AND clr.client_id={$row['client_id']} AND cl.id=clr.element_id")
                        ->asArray()
                        ->one();
                    if(!empty($lizing)){
                        $rows[$key]['lizing'] = $lizing['title'];
                    }
                }
            }
        }
        return $rows;
    }

    /**
     * @param bool $client_id
     * @return $this|array|\yii\db\ActiveRecord[]
     */
    public static function getListInReq($client_id = FALSE)
    {
        $rows = self::find()
            ->from(self::tableName().' as r')
            ->select('r.*,c.firstname,c.fathername,c.lastname,p.user_id,rp.data')
            ->innerJoin(
                Client::tableName().' as c',
                "r.client_id=c.id")
            ->innerJoin(
                RequestsPost::tableName().' as rp',
                "rp.requests_id=r.id AND rp.trash=0")
            ->innerJoin(
                RequestsPurpose::tableName().' as p',
                "r.id=p.requests_id and p.status=0")
            ->where(['status_work' => self::STATUS_WORK_IN, 'p.trash' => 0, 'r.trash' => 0])
            ->orderBy(['r.id' => SORT_DESC])
            ->asArray();
        if($client_id !== FALSE){
            $rows = $rows->andWhere(['r.client_id' => $client_id]);
        }

        $rows = $rows->all();

        if(!empty($rows))
        {
            foreach ($rows as $key => $row)
            {
                $calc = RequestsCalculator::find()
                    ->from(RequestsCalculator::tableName()." as rc")
                    ->select('rci.sale')
                    ->innerJoin(RequestsCalculatorItem::tableName(). " as rci",
                        "rci.calc_id=rc.id")
                    ->where([
                        'rc.requests_id' => $row['id'],
                        'rci.trash' => 0,
                        'rc.trash' => 0
                    ])
                    ->asArray()->all();

                if(!empty($calc)){
                    $calc = array_column($calc,'sale');
                    $rows[$key]['profit'] = array_sum($calc);
                }else{
                    $rows[$key]['profit'] = 0;
                }
                $rows[$key]['amount'] = RequestsPayment::getTotalAmount($row['id']);

                if(self::getLastStatus($row['id']) != RequestsProgress::STATUS_REQSUCCESS){
                    $rows[$key] = null;
                    continue;
                }

                $contacts_info = ClientConnection::find()
                    ->select('value,field')
                    ->where(['client_id' => $row['client_id'], 'trash' => 0])
                    ->orderBy(['field' => SORT_DESC])
                    ->asArray()->all();

                if(!empty($contacts_info))
                {
                    $rows[$key]['connection'] = $contacts_info;
                }
                $rows[$key]['manager'] = UserProfile::getFullName($row['user_id']);
                $rows[$key]['upd'] = RequestsUpd::get($row['id']);
                $rows[$key]['manager'] = UserProfile::getFullName($row['user_id']);
                $rows[$key]['progress'] = RequestsProgress::getStatus($row['id']);
                $rows[$key]['org'] = self::getOrgInfoForClient($row['client_id']);
                if(!empty($rows[$key]['org'])){
                    $lizing = ClientLizing::find()
                        ->from(ClientLizing::tableName()." as cl")
                        ->select('cl.title')
                        ->innerJoin(ClientLizingRel::tableName()." as clr", "clr.request_id = {$row['id']} AND clr.client_id={$row['client_id']} AND cl.id=clr.element_id")
                        ->asArray()
                        ->one();
                    if(!empty($lizing)){
                        $rows[$key]['lizing'] = $lizing['title'];
                    }
                }
            }
        }
        $rows = array_diff($rows, array(null));
        return $rows;
    }

    /**
     * @param bool $client_id
     * @return $this|array|\yii\db\ActiveRecord[]
     */
    public static function getListInPaid($client_id = FALSE)
    {
        $rows = self::find()
            ->from(self::tableName().' as r')
            ->select('r.*,c.firstname,c.fathername,c.lastname,p.user_id,rp.data')
            ->innerJoin(
                Client::tableName().' as c',
                "r.client_id=c.id")
            ->innerJoin(
                RequestsPost::tableName().' as rp',
                "rp.requests_id=r.id AND rp.trash=0")
            ->innerJoin(
                RequestsPurpose::tableName().' as p',
                "r.id=p.requests_id and p.status=0")
            ->where(['status_work' => self::STATUS_WORK_IN, 'p.trash' => 0, 'r.trash' => 0])
            ->orderBy(['r.id' => SORT_DESC])
            ->asArray();
        if($client_id !== FALSE){
            $rows = $rows->andWhere(['r.client_id' => $client_id]);
        }

        $rows = $rows->all();

        if(!empty($rows))
        {
            foreach ($rows as $key => $row)
            {
                $calc = RequestsCalculator::find()
                    ->from(RequestsCalculator::tableName()." as rc")
                    ->select('rci.sale, rci.buy')
                    ->innerJoin(RequestsCalculatorItem::tableName(). " as rci",
                        "rci.calc_id=rc.id")
                    ->where([
                        'rc.requests_id' => $row['id'],
                        'rci.trash' => 0,
                        'rc.trash' => 0
                    ])
                    ->asArray()->all();

                if(!empty($calc)){
                    $_calc = array_column($calc,'sale');
                    $rows[$key]['profit'] = array_sum($_calc);
                    $_calc = array_column($calc,'buy');
                    $rows[$key]['buy'] = array_sum($_calc);
                    $rows[$key]['profit_buy'] = $rows[$key]['profit']-$rows[$key]['buy'];
                }else{
                    $rows[$key]['profit'] = 0;
                }
                $rows[$key]['amount'] = RequestsPayment::getTotalAmount($row['id']);

                if($rows[$key]['amount'] == 0 ||
                    self::getLastStatus($row['id']) == RequestsProgress::STATUS_REQSUCCESS
                ){
                    $rows[$key] = null;
                    continue;
                }

                $contacts_info = ClientConnection::find()
                    ->select('value,field')
                    ->where(['client_id' => $row['client_id'], 'trash' => 0])
                    ->orderBy(['field' => SORT_DESC])
                    ->asArray()->all();

                if(!empty($contacts_info))
                {
                    $rows[$key]['connection'] = $contacts_info;
                }
                $rows[$key]['manager'] = UserProfile::getFullName($row['user_id']);
                $rows[$key]['upd'] = RequestsUpd::get($row['id']);
                $rows[$key]['manager'] = UserProfile::getFullName($row['user_id']);
                $rows[$key]['progress'] = RequestsProgress::getStatus($row['id']);
                $rows[$key]['org'] = self::getOrgInfoForClient($row['client_id']);
                $rows[$key]['prognoz'] = self::getSForecast($row['id']);
                if(!empty($rows[$key]['org'])){
                    $lizing = ClientLizing::find()
                        ->from(ClientLizing::tableName()." as cl")
                        ->select('cl.title')
                        ->innerJoin(ClientLizingRel::tableName()." as clr", "clr.request_id = {$row['id']} AND clr.client_id={$row['client_id']} AND cl.id=clr.element_id")
                        ->asArray()
                        ->one();
                    if(!empty($lizing)){
                        $rows[$key]['lizing'] = $lizing['title'];
                    }
                }
            }
        }
        $rows = array_diff($rows, array(null));
        return $rows;
    }


    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getListPurpose($client_id = FALSE)
    {
        $rows = self::find()
            ->from(self::tableName().' as r')
            ->select('r.*,rp.data,c.firstname,c.fathername,c.lastname,p.user_id')
            ->innerJoin(
                RequestsPost::tableName().' as rp',
                "rp.requests_id=r.id AND rp.trash=0")
            ->innerJoin(
                Client::tableName().' as c',
                "r.client_id=c.id")
            ->innerJoin(
                RequestsPurpose::tableName().' as p',
                "r.id=p.requests_id")
            ->where(['status_work' => self::STATUS_WORK_PURPOSE, 'p.trash' => 0, 'r.trash' => 0])
            ->orderBy(['r.id' => SORT_DESC])
            ->asArray();

        if($client_id !== FALSE){
            $rows = $rows->andWhere(['r.client_id' => $client_id]);
        }

        $rows = $rows->all();
        if(!empty($rows))
        {
            foreach ($rows as $key => $row)
            {
                $contacts_info = ClientConnection::find()
                    ->select('value,field')
                    ->where(['client_id' => $row['client_id'], 'trash' => 0])
                    ->orderBy(['field' => SORT_DESC])
                    ->asArray()->all();
                if(!empty($contacts_info))
                {
                    $rows[$key]['connection'] = $contacts_info;
                }

                $rows[$key]['manager'] = UserProfile::getFullName($row['user_id']);
                $rows[$key]['org'] = self::getOrgInfoForClient($row['client_id']);
                $rows[$key]['profit'] = 0;

            }
        }
        return $rows;
    }

    /**
     * @param bool $client_id
     * @return $this|array|\yii\db\ActiveRecord[]
     */
    public static function getListSuccess($client_id = FALSE, $type = 0, $pages = true)
    {
        $page = Yii::$app->request->get('page');
        $user_id = Yii::$app->request->get('user_id');
        if( $pages ) {
            $offset = 0;
            $count = Yii::$app->params['per_page_requests'];
            if( !empty($page) && $page != 1 )
            {
                $offset = $count*($page-1);
            }
        }

        if( empty($user_id) )
        {
            $rows = self::find()
                ->from(self::tableName().' as r')
                ->select('r.*,rp.data,c.firstname,c.fathername,c.lastname, p.user_id')
                ->innerJoin(
                    RequestsPost::tableName().' as rp',
                    "rp.requests_id=r.id AND rp.trash=0")
                ->innerJoin(
                    Client::tableName().' as c',
                    "r.client_id=c.id")
                ->innerJoin(
                    RequestsPurpose::tableName().' as p',
                    "r.id=p.requests_id and p.status=0")
                ->leftJoin(
                    RequestsUpd::tableName().' as ru',
                    "r.id=ru.requests_id and ru.trash=0")
                ->where(['status_work' => self::STATUS_WORK_SUCCESS, 'r.trash' => 0, 'p.trash' => 0])
                ->orderBy(['ru.date' => SORT_DESC,'r.date_end' => SORT_DESC, 'r.id' => SORT_DESC])
                ->asArray();
        }
        else
        {
            $rows = self::find()
                ->from(self::tableName().' as r')
                ->select('r.*,rp.data,c.firstname,c.fathername,c.lastname, p.user_id')
                ->innerJoin(
                    RequestsPost::tableName().' as rp',
                    "rp.requests_id=r.id AND rp.trash=0")
                ->innerJoin(
                    Client::tableName().' as c',
                    "r.client_id=c.id")
                ->innerJoin(
                    RequestsPurpose::tableName().' as p',
                    "r.id=p.requests_id and p.user_id={$user_id}")
                ->leftJoin(
                    RequestsUpd::tableName().' as ru',
                    "r.id=ru.requests_id and ru.trash=0")
                ->where(['status_work' => self::STATUS_WORK_SUCCESS, 'r.trash' => 0, 'p.trash' => 0])
                ->orderBy(['ru.date' => SORT_DESC,'r.date_end' => SORT_DESC, 'r.id' => SORT_DESC])
                ->asArray();
        }

        if($client_id !== FALSE){
            $rows = $rows->andWhere(['r.client_id' => $client_id]);
        }

        if( $type == 1 )
        {
            $rows = $rows->andWhere("r.date_end > 0");
        }
        elseif($type == 2)
        {
            $rows = $rows->andWhere("r.date_end = 0");
        }

        if( $pages ) {
            $rows = $rows->groupBy(['r.id'])->offset($offset)->limit($count)->all();
        }
        else {
            $rows = $rows->groupBy(['r.id'])->all();
        }


        if(!empty($rows))
        {
            foreach ($rows as $key => $row)
            {
                $contacts_info = ClientConnection::find()
                    ->select('value,field')
                    ->where(['client_id' => $row['client_id'], 'trash' => 0])
                    ->orderBy(['field' => SORT_DESC])
                    ->asArray()->all();
                if(!empty($contacts_info))
                {
                    $rows[$key]['connection'] = $contacts_info;
                }

                $calc = RequestsCalculator::find()
                    ->from(RequestsCalculator::tableName()." as rc")
                    ->select('rci.sale')
                    ->innerJoin(RequestsCalculatorItem::tableName(). " as rci",
                        "rci.calc_id=rc.id")
                    ->where([
                        'rc.requests_id' => $row['id'],
                        'rci.trash' => 0,
                        'rc.trash' => 0
                    ])
                    ->asArray()->all();

                if(!empty($calc)){
                    $calc = array_column($calc,'sale');
                    $rows[$key]['profit'] = array_sum($calc);
                }else{
                    $rows[$key]['profit'] = 0;
                }
                $rows[$key]['upd'] = RequestsUpd::get($row['id']);
                $rows[$key]['amount'] = RequestsPayment::getTotalAmount($row['id']);
                $rows[$key]['manager'] = UserProfile::getFullName($row['user_id']);
                $rows[$key]['progress'] = RequestsProgress::getStatus($row['id']);
                $rows[$key]['org'] = self::getOrgInfoForClient($row['client_id']);
                if(!empty($rows[$key]['org'])){
                    $lizing = ClientLizing::find()
                        ->from(ClientLizing::tableName()." as cl")
                        ->select('cl.title')
                        ->innerJoin(ClientLizingRel::tableName()." as clr", "clr.request_id = {$row['id']} AND clr.client_id={$row['client_id']} AND cl.id=clr.element_id")
                        ->asArray()
                        ->one();
                    if(!empty($lizing)){
                        $rows[$key]['lizing'] = $lizing['title'];
                    }
                }
            }
        }
        return $rows;
    }

    /**
     * @param $start
     * @param $end
     * @return int|string
     */
    public static function getListSite($start, $end)
    {
        $count = self::find()
            ->from(self::tableName().' as r')
            ->select('r.id')
            ->where(['status' => self::STATUS_SITE, 'r.trash' => 0])
            ->andWhere("create_at >= {$start} and create_at <= {$end}")
            ->asArray()->count();
        return $count ?? 0;
    }

    /**
     * @param $start
     * @param $end
     * @return int|string
     */
    public static function getListSiteNotCancel($start, $end)
    {
        $count = self::find()
            ->from(self::tableName().' as r')
            ->select('r.id')
            ->where(['status' => self::STATUS_SITE, 'r.trash' => 0])
            ->andWhere("create_at >= {$start} and create_at <= {$end}")
            ->andWhere("status_work <> 4 or (status_work = 4 and date_end > 0)")
            ->asArray()->count();
        return $count ?? 0;
    }

    /**
     * @param $start
     * @param $end
     * @return int|string
     */
    public static function getListMobile($start, $end)
    {
        $count = self::find()
            ->from(self::tableName().' as r')
            ->select('r.id')
            ->where(['status' => self::STATUS_MOBILE, 'r.trash' => 0])
            ->andWhere("create_at >= {$start} and create_at <= {$end}")
            ->asArray()->count();
        return $count ?? 0;
    }

    /**
     * @param $start
     * @param $end
     * @return int|string
     */
    public static function getListMobileNotCancel($start, $end)
    {
        $count = self::find()
            ->from(self::tableName().' as r')
            ->select('r.id')
            ->where(['status' => self::STATUS_MOBILE, 'r.trash' => 0])
            ->andWhere("create_at >= {$start} and create_at <= {$end}")
            ->andWhere("status_work <> 4 or (status_work = 4 and date_end > 0)")
            ->asArray()->count();
        return $count ?? 0;
    }

    /**
     * @param $start
     * @param $end
     * @return int|string
     */
    public static function getListPhone($start, $end)
    {
        $count = self::find()
            ->from(self::tableName().' as r')
            ->select('r.id')
            ->where(['status' => self::STATUS_PHONE, 'r.trash' => 0])
            ->andWhere("create_at >= {$start} and create_at <= {$end}")
            ->asArray()->count();
        return $count ?? 0;
    }

    /**
     * @param $start
     * @param $end
     * @return int|string
     */
    public static function getListPhoneNotCancel($start, $end)
    {
        $count = self::find()
            ->from(self::tableName().' as r')
            ->select('r.id')
            ->where(['status' => self::STATUS_PHONE, 'r.trash' => 0])
            ->andWhere("create_at >= {$start} and create_at <= {$end}")
            ->andWhere("status_work <> 4 or (status_work = 4 and date_end > 0)")
            ->asArray()->count();
        return $count ?? 0;
    }

    /**
     * @param $start
     * @param $end
     * @return int|string
     */
    public static function getListTender($start, $end)
    {
        $count = self::find()
            ->from(self::tableName().' as r')
            ->select('r.id')
            ->where(['tender' => 1, 'r.trash' => 0])
            ->andWhere("create_at >= {$start} and create_at <= {$end}")
            ->asArray()->count();
        return $count ?? 0;
    }

    /**
     * @param $user_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getListInForUser($user_id)
    {
        $rows = self::find()
            ->from(self::tableName().' as r')
            ->select('r.*,rp.data,c.firstname,c.fathername,c.lastname, p.user_id')
            ->innerJoin(
                RequestsPost::tableName().' as rp',
                "rp.requests_id=r.id AND rp.trash=0")
            ->innerJoin(
                Client::tableName().' as c',
                "r.client_id=c.id")
            ->innerJoin(
                RequestsPurpose::tableName().' as p',
                "r.id=p.requests_id and p.user_id={$user_id}")
            ->where(['status_work' => self::STATUS_WORK_IN, 'p.trash' => 0, 'r.trash' => 0])
            ->asArray()
            ->orderBy(['r.status_work' => SORT_NUMERIC, 'r.id' => SORT_DESC])
            ->all();
        $progress = [];
        if(!empty($rows))
        {
            foreach ($rows as $key => $row)
            {
                $rows[$key]['trend'] = self::staticGetTrend($row['id']);
                $contacts_info = ClientConnection::find()
                    ->select('value,field')
                    ->where(['client_id' => $row['client_id'], 'trash' => 0])
                    ->orderBy(['field' => SORT_DESC])
                    ->asArray()->all();
                if(!empty($contacts_info))
                {
                    $rows[$key]['connection'] = $contacts_info;
                }

                $calc = RequestsCalculator::find()
                    ->from(RequestsCalculator::tableName()." as rc")
                    ->select('rci.buy,rci.sale')
                    ->innerJoin(RequestsCalculatorItem::tableName(). " as rci",
                        "rci.calc_id=rc.id")
                    ->where([
                        'rc.requests_id' => $row['id'],
                        'rci.trash' => 0,
                        'rc.trash' => 0
                    ])
                    ->asArray()->all();

                if(!empty($calc)){
                    $_calc = array_column($calc,'sale');
                    $_buys = array_column($calc,'buy');
                    $rows[$key]['profit'] = array_sum($_calc);
                    $rows[$key]['profit_buy'] = array_sum($_calc) - array_sum($_buys);
                }else{
                    $rows[$key]['profit'] = $rows[$key]['profit_buy'] = 0;
                }
                $rows[$key]['upd'] = RequestsUpd::get($row['id']);
                $rows[$key]['amount'] = RequestsPayment::getTotalAmount($row['id']);
                $rows[$key]['org'] = self::getOrgInfoForClient($row['client_id']);
                if(!empty($rows[$key]['org'])){
                    $lizing = ClientLizing::find()
                        ->from(ClientLizing::tableName()." as cl")
                        ->select('cl.title')
                        ->innerJoin(ClientLizingRel::tableName()." as clr", "clr.request_id = {$row['id']} AND clr.client_id={$row['client_id']} AND cl.id=clr.element_id")
                        ->asArray()
                        ->one();
                    if(!empty($lizing)){
                        $rows[$key]['lizing'] = $lizing['title'];
                    }
                }
                $rows[$key]['progress'] = RequestsProgress::getStatus($row['id']);
                $progress[$key] = $rows[$key]['progress']['status'];
                if( !empty($rows[$key]['progress']['status']) && $rows[$key]['progress']['status'] == RequestsProgress::STATUS_OFFER ) {
                    $calls = MegafonHistory::find()
                        ->from(MegafonHistory::tableName()." as h")
                        ->select('h.type,h.user,h.phone,h.diversion,h.start,h.duration,h.link,h.status,p.full_name')
                        ->innerJoin(UserProfile::tableName()." as p", "p.megafone_id=h.user");
                    $phones = [];
                    foreach ($rows[$key]['connection'] as $phone) {
                        if( $phone['field'] == 'phone' ) {
                            $_phone = AppFunctions::clearCountyCodePhone($phone['value']);
                            if( substr($_phone, 0, 1) == '8' ) {
                                if( substr($_phone, 0, 3) != '831' ) {
                                    $_phone = substr($_phone, 1);
                                }
                            }
                            $phones[] = "h.phone LIKE '%{$_phone}%'";
                        }
                    }
                    $phones = implode(' OR ', $phones);
                    $start_request = '1=1';
                    if( !empty($rows[$key]['create_at']) ) {
                        $start_request .= " and h.start >= {$rows[$key]['create_at']}";
                    }

                    //
                    if( !empty($phones) ) {
                        $calls = $calls
                            ->where($phones)
                            ->andWhere("h.duration > 0")
                            ->andWhere(['h.trash' => 0])
                            ->andWhere($start_request)
                            ->asArray()
                            ->all();
                        /*if( '5.227.27.207' == $_SERVER['REMOTE_ADDR'] ) {
                            if( $row['id'] == '6262' ){
                                //print_r($start_request);die;
                            }

                        }*/
                        $rows[$key]['calls'] = count($calls);

                    }

                }
            }
            array_multisort($progress, SORT_NUMERIC, SORT_ASC, $rows);
        }
        //print_r($rows);die;
        return $rows;
    }

    /**
     * @param $user_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getListInForUserCount($user_id)
    {
        $count = self::find()
            ->from(self::tableName().' as r')
            ->select('r.id')
            ->innerJoin(
                RequestsPurpose::tableName().' as p',
                "r.id=p.requests_id and p.user_id={$user_id}")
            ->where(['status_work' => self::STATUS_WORK_IN, 'p.trash' => 0, 'r.trash' => 0])
            ->asArray()
            ->count();

        return $count;
    }

    public static function getListInCount()
    {
        $user_id = Yii::$app->request->get('user_id');
        if(!empty($user_id))
        {
            $count = self::find()
                ->from(self::tableName().' as r')
                ->select('r.id')
                ->innerJoin(
                    RequestsPurpose::tableName().' as p',
                    "r.id=p.requests_id and p.user_id={$user_id}")
                ->where(['status_work' => self::STATUS_WORK_IN, 'p.trash' => 0, 'r.trash' => 0])
                ->asArray()
                ->count();
        }
        else
        {
            $count = self::find()
                ->from(self::tableName().' as r')
                ->select('r.id')
                ->where(['status_work' => self::STATUS_WORK_IN, 'r.trash' => 0])
                ->asArray()
                ->count();
        }

        return $count;
    }

    public static function getListSuccessCount($type = 0, $start = FALSE,$end = FALSE)
    {
        $user_id = Yii::$app->request->get('user_id');
        if(!empty($user_id)) {
            $count = self::find()
                ->from(self::tableName() . ' as r')
                ->select('r.*,c.firstname,c.fathername,c.lastname')
                ->innerJoin(
                    Client::tableName() . ' as c',
                    "r.client_id=c.id")
                ->innerJoin(
                    RequestsPurpose::tableName() . ' as p',
                    "r.id=p.requests_id and p.user_id={$user_id}")
                ->where(['status_work' => self::STATUS_WORK_SUCCESS, 'p.trash' => 0, 'r.trash' => 0])
                ->orderBy(['ru.date' => SORT_DESC, 'r.date_end' => SORT_DESC, 'r.id' => SORT_DESC])
                ->asArray();
        }
        else
        {
            $count = self::find()
                ->from(self::tableName() . ' as r')
                ->select('r.*,rp.data,c.firstname,c.fathername,c.lastname')
                ->innerJoin(
                    RequestsPost::tableName() . ' as rp',
                    "rp.requests_id=r.id AND rp.trash=0")
                ->innerJoin(
                    Client::tableName() . ' as c',
                    "r.client_id=c.id")
                ->leftJoin(
                    RequestsUpd::tableName() . ' as ru',
                    "r.id=ru.requests_id and ru.trash=0")
                ->where(['status_work' => self::STATUS_WORK_SUCCESS, 'r.trash' => 0])
                ->orderBy(['ru.date' => SORT_DESC, 'r.date_end' => SORT_DESC, 'r.id' => SORT_DESC])
                ->asArray();
        }

        if( $type == 0 ){
            if($start !== FALSE){
                $count = $count->andWhere("r.date_end >= {$start}");
            }
            if($end !== FALSE){
                $count = $count->andWhere("date_end <= {$end}");
            }
        }
        elseif($type == 1)
        {
            $count = $count->andWhere("r.date_end > 0");
        }
        elseif($type == 2)
        {
            $count = $count->andWhere("r.date_end = 0");
        }
        $count = $count->count();

        return $count;
    }

    /**
     * @param $user_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getListInPaidForUser($user_id)
    {
        $rows = self::find()
            ->from(self::tableName().' as r')
            ->select('r.*,rp.data,c.firstname,c.fathername,c.lastname, p.user_id')
            ->innerJoin(
                RequestsPost::tableName().' as rp',
                "rp.requests_id=r.id AND rp.trash=0")
            ->innerJoin(
                Client::tableName().' as c',
                "r.client_id=c.id")
            ->innerJoin(
                RequestsPurpose::tableName().' as p',
                "r.id=p.requests_id and p.user_id={$user_id}")
            ->where(['status_work' => self::STATUS_WORK_IN, 'p.trash' => 0, 'r.trash' => 0])
            ->asArray()
            ->orderBy(['r.id' => SORT_DESC])
            ->all();

        if(!empty($rows))
        {
            foreach ($rows as $key => $row)
            {
                $rows[$key]['trend'] = self::staticGetTrend($row['id']);
                $calc = RequestsCalculator::find()
                    ->from(RequestsCalculator::tableName()." as rc")
                    ->select('rci.sale, rci.buy')
                    ->innerJoin(RequestsCalculatorItem::tableName(). " as rci",
                        "rci.calc_id=rc.id")
                    ->where([
                        'rc.requests_id' => $row['id'],
                        'rci.trash' => 0,
                        'rc.trash' => 0
                    ])
                    ->asArray()->all();

                if(!empty($calc)){
                    $_calc = array_column($calc,'sale');
                    $_buy = array_column($calc,'buy');
                    $rows[$key]['profit'] = array_sum($_calc);
                    $rows[$key]['profit_buy'] = array_sum($_calc)-array_sum($_buy);
                }else{
                    $rows[$key]['profit'] = 0;
                    $rows[$key]['profit_buy'] = 0;
                }
                $rows[$key]['amount'] = RequestsPayment::getTotalAmount($row['id']);
                if($rows[$key]['amount'] == 0  ||
                    self::getLastStatus($row['id']) == RequestsProgress::STATUS_REQSUCCESS
                ){
                    $rows[$key] = null;
                    continue;
                }
                $contacts_info = ClientConnection::find()
                    ->select('value,field')
                    ->where(['client_id' => $row['client_id'], 'trash' => 0])
                    ->orderBy(['field' => SORT_DESC])
                    ->asArray()->all();
                if(!empty($contacts_info))
                {
                    $rows[$key]['connection'] = $contacts_info;
                }
                $rows[$key]['upd'] = RequestsUpd::get($row['id']);
                $rows[$key]['org'] = self::getOrgInfoForClient($row['client_id']);
                if(!empty($rows[$key]['org'])){
                    $lizing = ClientLizing::find()
                        ->from(ClientLizing::tableName()." as cl")
                        ->select('cl.title')
                        ->innerJoin(ClientLizingRel::tableName()." as clr", "clr.request_id = {$row['id']} AND clr.client_id={$row['client_id']} AND cl.id=clr.element_id")
                        ->asArray()
                        ->one();
                    if(!empty($lizing)){
                        $rows[$key]['lizing'] = $lizing['title'];
                    }
                }
                $rows[$key]['progress'] = RequestsProgress::getStatus($row['id']);
            }
        }
        $rows = array_diff($rows, array(null));
        return $rows;
    }

    /**
     * @param $user_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getListInReqForUser($user_id)
    {
        $rows = self::find()
            ->from(self::tableName().' as r')
            ->select('r.*,rp.data,c.firstname,c.fathername,c.lastname, p.user_id')
            ->innerJoin(
                RequestsPost::tableName().' as rp',
                "rp.requests_id=r.id AND rp.trash=0")
            ->innerJoin(
                Client::tableName().' as c',
                "r.client_id=c.id")
            ->innerJoin(
                RequestsPurpose::tableName().' as p',
                "r.id=p.requests_id and p.user_id={$user_id}")
            ->where(['status_work' => self::STATUS_WORK_IN, 'p.trash' => 0, 'r.trash' => 0])
            ->asArray()
            ->orderBy(['r.id' => SORT_DESC])
            ->all();

        if(!empty($rows))
        {
            foreach ($rows as $key => $row)
            {
                $calc = RequestsCalculator::find()
                    ->from(RequestsCalculator::tableName()." as rc")
                    ->select('rci.sale')
                    ->innerJoin(RequestsCalculatorItem::tableName(). " as rci",
                        "rci.calc_id=rc.id")
                    ->where([
                        'rc.requests_id' => $row['id'],
                        'rci.trash' => 0,
                        'rc.trash' => 0
                    ])
                    ->asArray()->all();

                if(!empty($calc)){
                    $calc = array_column($calc,'sale');
                    $rows[$key]['profit'] = array_sum($calc);
                }else{
                    $rows[$key]['profit'] = 0;
                }
                $rows[$key]['amount'] = RequestsPayment::getTotalAmount($row['id']);
                if(self::getLastStatus($row['id']) != RequestsProgress::STATUS_REQSUCCESS){
                    $rows[$key] = null;
                    continue;
                }
                $contacts_info = ClientConnection::find()
                    ->select('value,field')
                    ->where(['client_id' => $row['client_id'], 'trash' => 0])
                    ->orderBy(['field' => SORT_DESC])
                    ->asArray()->all();
                if(!empty($contacts_info))
                {
                    $rows[$key]['connection'] = $contacts_info;
                }
                $rows[$key]['upd'] = RequestsUpd::get($row['id']);
                $rows[$key]['org'] = self::getOrgInfoForClient($row['client_id']);
                if(!empty($rows[$key]['org'])){
                    $lizing = ClientLizing::find()
                        ->from(ClientLizing::tableName()." as cl")
                        ->select('cl.title')
                        ->innerJoin(ClientLizingRel::tableName()." as clr", "clr.request_id = {$row['id']} AND clr.client_id={$row['client_id']} AND cl.id=clr.element_id")
                        ->asArray()
                        ->one();
                    if(!empty($lizing)){
                        $rows[$key]['lizing'] = $lizing['title'];
                    }
                }
                $rows[$key]['progress'] = RequestsProgress::getStatus($row['id']);
            }
        }
        $rows = array_diff($rows, array(null));
        return $rows;
    }

    /**
     * @param $user_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getListPurposeForUser($user_id)
    {
        $rows = self::find()
            ->from(self::tableName().' as r')
            ->select('r.*,rp.data,c.firstname,c.fathername,c.lastname, p.user_id')
            ->innerJoin(
                RequestsPost::tableName().' as rp',
                "rp.requests_id=r.id AND rp.trash=0")
            ->innerJoin(
                Client::tableName().' as c',
                "r.client_id=c.id")
            ->innerJoin(
                RequestsPurpose::tableName().' as p',
                "r.id=p.requests_id and p.user_id={$user_id}")
            ->where(['status_work' => self::STATUS_WORK_PURPOSE, 'p.trash' => 0, 'r.trash' => 0])
            ->orderBy(['r.id' => SORT_DESC])
            ->asArray()
            ->all();
        if(!empty($rows))
        {
            foreach ($rows as $key => $row)
            {
                $contacts_info = ClientConnection::find()
                    ->select('value,field')
                    ->where(['client_id' => $row['client_id'], 'trash' => 0])
                    ->orderBy(['field' => SORT_DESC])
                    ->asArray()->all();
                if(!empty($contacts_info))
                {
                    $rows[$key]['connection'] = $contacts_info;
                }
                $rows[$key]['org'] = self::getOrgInfoForClient($row['client_id']);
                if(!empty($rows[$key]['org'])){
                    $lizing = ClientLizing::find()
                        ->from(ClientLizing::tableName()." as cl")
                        ->select('cl.title')
                        ->innerJoin(ClientLizingRel::tableName()." as clr", "clr.request_id = {$row['id']} AND clr.client_id={$row['client_id']} AND cl.id=clr.element_id")
                        ->asArray()
                        ->one();
                    if(!empty($lizing)){
                        $rows[$key]['lizing'] = $lizing['title'];
                    }
                }
            }
        }
        return $rows;
    }

    /**
     * @param $user_id
     * @return int|string
     */
    public static function getListPurposeForUserCount($user_id)
    {
        $count = self::find()
            ->from(self::tableName().' as r')
            ->select('r.id')
            ->innerJoin(
                RequestsPurpose::tableName().' as p',
                "r.id=p.requests_id and p.user_id={$user_id}")
            ->where(['status_work' => self::STATUS_WORK_PURPOSE, 'p.trash' => 0, 'r.trash' => 0])
            ->orderBy(['r.id' => SORT_DESC])
            ->asArray()
            ->count();

        return $count;
    }

    /**
     * @param $user_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getListSuccessForUser($user_id,$start = FALSE,$end = FALSE)
    {
        $rows = self::find()
            ->from(self::tableName().' as r')
            ->select('r.*,rp.data,c.firstname,c.fathername,c.lastname, p.user_id')
            ->innerJoin(
                RequestsPost::tableName().' as rp',
                "rp.requests_id=r.id AND rp.trash=0")
            ->innerJoin(
                Client::tableName().' as c',
                "r.client_id=c.id")
            ->innerJoin(
                RequestsPurpose::tableName().' as p',
                "r.id=p.requests_id and p.user_id={$user_id}")
            ->leftJoin(
                RequestsUpd::tableName().' as ru',
                "r.id=ru.requests_id and ru.trash=0")
            ->where(['status_work' => self::STATUS_WORK_SUCCESS, 'p.trash' => 0, 'r.trash' => 0])
            ->orderBy(['ru.date' => SORT_DESC, 'r.date_end' => SORT_DESC, 'r.id' => SORT_DESC])
            ->asArray();

        if($start !== FALSE){
            $rows = $rows->andWhere("r.date_end >= {$start}");
        }
        if($end !== FALSE){
            $rows = $rows->andWhere("date_end <= {$end}");
        }
        $rows = $rows->all();

        if(!empty($rows))
        {
            foreach ($rows as $key => $row)
            {
                $rows[$key]['trend'] = self::staticGetTrend($row['id']);
                $contacts_info = ClientConnection::find()
                    ->select('value,field')
                    ->where(['client_id' => $row['client_id'], 'trash' => 0])
                    ->orderBy(['field' => SORT_DESC])
                    ->asArray()->all();
                if(!empty($contacts_info))
                {
                    $rows[$key]['connection'] = $contacts_info;
                }

                $calc = RequestsCalculator::find()
                    ->from(RequestsCalculator::tableName()." as rc")
                    ->select('rci.buy,rci.sale')
                    ->innerJoin(RequestsCalculatorItem::tableName(). " as rci",
                        "rci.calc_id=rc.id")
                    ->where([
                        'rc.requests_id' => $row['id'],
                        'rci.trash' => 0,
                        'rc.trash' => 0
                    ])
                    ->asArray()->all();

                if(!empty($calc)){
                    $_calc = array_column($calc,'sale');
                    $_buys = array_column($calc,'buy');
                    $rows[$key]['profit'] = array_sum($_calc);
                    $rows[$key]['profit_buy'] = array_sum($_calc) - array_sum($_buys);
                }else{
                    $rows[$key]['profit'] = 0;
                }

                if(($start !== FALSE || $end !== FALSE) && $rows[$key]['profit'] != 0 ){
                    $_calc = array_column($calc,'buy');
                    $rows[$key]['totals'] = [
                        'sale' => $rows[$key]['profit'],
                        'buy' => array_sum($_calc)
                    ];
                }
                $rows[$key]['upd'] = RequestsUpd::get($row['id']);
                $rows[$key]['amount'] = RequestsPayment::getTotalAmount($row['id']);
                $rows[$key]['org'] = self::getOrgInfoForClient($row['client_id']);
                if(!empty($rows[$key]['org'])){
                    $lizing = ClientLizing::find()
                        ->from(ClientLizing::tableName()." as cl")
                        ->select('cl.title')
                        ->innerJoin(ClientLizingRel::tableName()." as clr", "clr.request_id = {$row['id']} AND clr.client_id={$row['client_id']} AND cl.id=clr.element_id")
                        ->asArray()
                        ->one();
                    if(!empty($lizing)){
                        $rows[$key]['lizing'] = $lizing['title'];
                    }
                }
                $rows[$key]['progress'] = RequestsProgress::getStatus($row['id']);
            }
        }
        return $rows;
    }

    /**
     * @param $user_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getListSuccessPrognosisForUser($user_id,$start,$end)
    {
        $rows = self::find()
            ->from(self::tableName().' as r')
            ->select('r.*,rp.data,c.firstname,c.fathername,c.lastname, p.user_id')
            ->innerJoin(
                RequestsPost::tableName().' as rp',
                "rp.requests_id=r.id AND rp.trash=0")
            ->innerJoin(
                Client::tableName().' as c',
                "r.client_id=c.id")
            ->innerJoin(
                RequestsPurpose::tableName().' as p',
                "r.id=p.requests_id and p.user_id={$user_id}")
            ->leftJoin(
                RequestsPrognosis::tableName().' as ru',
                "r.id=ru.request_id and ru.trash=0")
            ->where(['status_work' => self::STATUS_WORK_IN, 'p.trash' => 0, 'r.trash' => 0])
            ->orderBy(['ru.date' => SORT_DESC, 'r.id' => SORT_DESC])
            ->asArray();

        if($start !== FALSE){
            $rows = $rows->andWhere("ru.date >= {$start}");
        }
        if($end !== FALSE){
            $rows = $rows->andWhere("ru.date <= {$end}");
        }
        $rows = $rows->all();

        if(!empty($rows))
        {
            foreach ($rows as $key => $row)
            {
                $rows[$key]['trend'] = self::staticGetTrend($row['id']);
                $contacts_info = ClientConnection::find()
                    ->select('value,field')
                    ->where(['client_id' => $row['client_id'], 'trash' => 0])
                    ->orderBy(['field' => SORT_DESC])
                    ->asArray()->all();
                if(!empty($contacts_info))
                {
                    $rows[$key]['connection'] = $contacts_info;
                }

                $calc = RequestsCalculator::find()
                    ->from(RequestsCalculator::tableName()." as rc")
                    ->select('rci.buy,rci.sale')
                    ->innerJoin(RequestsCalculatorItem::tableName(). " as rci",
                        "rci.calc_id=rc.id")
                    ->where([
                        'rc.requests_id' => $row['id'],
                        'rci.trash' => 0,
                        'rc.trash' => 0
                    ])
                    ->asArray()->all();

                if(!empty($calc)){
                    $_calc = array_column($calc,'sale');
                    $_buys = array_column($calc,'buy');
                    $rows[$key]['profit'] = array_sum($_calc);
                    $rows[$key]['profit_buy'] = array_sum($_calc) - array_sum($_buys);
                }else{
                    $rows[$key]['profit'] = 0;
                }

                if(($start !== FALSE || $end !== FALSE) && $rows[$key]['profit'] != 0 ){
                    $_calc = array_column($calc,'buy');
                    $rows[$key]['totals'] = [
                        'sale' => $rows[$key]['profit'],
                        'buy' => array_sum($_calc)
                    ];
                }
                //$rows[$key]['upd'] = RequestsUpd::get($row['id']);
                $rows[$key]['amount'] = RequestsPayment::getTotalAmount($row['id']);
                $rows[$key]['org'] = self::getOrgInfoForClient($row['client_id']);
                if(!empty($rows[$key]['org'])){
                    $lizing = ClientLizing::find()
                        ->from(ClientLizing::tableName()." as cl")
                        ->select('cl.title')
                        ->innerJoin(ClientLizingRel::tableName()." as clr", "clr.request_id = {$row['id']} AND clr.client_id={$row['client_id']} AND cl.id=clr.element_id")
                        ->asArray()
                        ->one();
                    if(!empty($lizing)){
                        $rows[$key]['lizing'] = $lizing['title'];
                    }
                }
                $rows[$key]['progress'] = RequestsProgress::getStatus($row['id']);
            }
        }
        return $rows;
    }

    public static function getListFavoritesForUser($user_id,$start = FALSE,$end = FALSE)
    {
        $success = self::STATUS_WORK_SUCCESS;
        $work_remove = self::STATUS_WORK_REMOVE;
        $rows = self::find()
            ->from(self::tableName().' as r')
            ->select('r.*,rp.data,c.firstname,c.fathername,c.lastname, p.user_id')
            ->innerJoin(
                RequestsPost::tableName().' as rp',
                "rp.requests_id=r.id AND rp.trash=0")
            ->innerJoin(
                Client::tableName().' as c',
                "r.client_id=c.id")
            ->innerJoin(
                RequestsPurpose::tableName().' as p',
                "r.id=p.requests_id and p.user_id={$user_id} and p.trash=0")
            ->innerJoin(
                ClientTrend::tableName()." as ct",
                "ct.client_id=r.id and ct.user_id={$user_id}"
            )
            ->andWhere(['r.trash' => 0])
            ->andWhere("r.status_work <> {$success}")
            ->andWhere("r.status_work <> {$work_remove}")
            ->orderBy(['r.id' => SORT_DESC])
            ->asArray();

        /*if($start !== FALSE){
            $rows = $rows->andWhere("r.create_at >= {$start}");
        }*/
        if($end !== FALSE){
            $rows = $rows->andWhere("r.create_at <= {$end}");
        }

        $rows = $rows->all();
        if(!empty($rows))
        {
            foreach ($rows as $key => $row)
            {

                /*$contacts_info = ClientConnection::find()
                    ->select('value,field')
                    ->where(['client_id' => $row['client_id'], 'trash' => 0])
                    ->orderBy(['field' => SORT_DESC])
                    ->asArray()->all();
                if(!empty($contacts_info))
                {
                    $rows[$key]['connection'] = $contacts_info;
                }*/

                $calc = RequestsCalculator::find()
                    ->from(RequestsCalculator::tableName()." as rc")
                    ->select('rci.buy,rci.sale')
                    ->innerJoin(RequestsCalculatorItem::tableName(). " as rci",
                        "rci.calc_id=rc.id")
                    ->where([
                        'rc.requests_id' => $row['id'],
                        'rci.trash' => 0,
                        'rc.trash' => 0
                    ])
                    ->asArray()->all();

                if(!empty($calc)){
                    $_calc = array_column($calc,'sale');
                    $_buys = array_column($calc,'buy');
                    $rows[$key]['profit'] = array_sum($_calc);
                    $rows[$key]['profit_buy'] = array_sum($_calc) - array_sum($_buys);
                }else{
                    $rows[$key]['profit'] = 0;
                }

                if(($start !== FALSE || $end !== FALSE) && $rows[$key]['profit'] != 0 ){
                    $_calc = array_column($calc,'buy');
                    $rows[$key]['totals'] = [
                        'sale' => $rows[$key]['profit'],
                        'buy' => array_sum($_calc)
                    ];
                }
                $rows[$key]['upd'] = RequestsUpd::get($row['id']);
                $rows[$key]['amount'] = RequestsPayment::getTotalAmount($row['id']);
                /*$rows[$key]['org'] = self::getOrgInfoForClient($row['client_id']);
                if(!empty($rows[$key]['org'])){
                    $lizing = ClientLizing::find()
                        ->from(ClientLizing::tableName()." as cl")
                        ->select('cl.title')
                        ->innerJoin(ClientLizingRel::tableName()." as clr", "clr.request_id = {$row['id']} AND clr.client_id={$row['client_id']} AND cl.id=clr.element_id")
                        ->asArray()
                        ->one();
                    if(!empty($lizing)){
                        $rows[$key]['lizing'] = $lizing['title'];
                    }
                }*/
                $rows[$key]['progress'] = RequestsProgress::getStatus($row['id']);
            }
        }
        return $rows;
    }

    /**
     * @param $user_id
     * @param bool $start
     * @param bool $end
     * @return int|string|\yii\db\ActiveQuery
     */
    public static function getListSuccessForUserCount($user_id,$start = FALSE,$end = FALSE)
    {
        $count = self::find()
            ->from(self::tableName().' as r')
            ->select('r.id')
            ->innerJoin(
                RequestsPurpose::tableName().' as p',
                "r.id=p.requests_id and p.user_id={$user_id}")
            ->leftJoin(
                RequestsUpd::tableName().' as ru',
                "r.id=ru.requests_id and ru.trash=0")
            ->where(['status_work' => self::STATUS_WORK_SUCCESS, 'p.trash' => 0, 'r.trash' => 0])
            ->orderBy(['ru.date' => SORT_DESC, 'r.date_end' => SORT_DESC, 'r.id' => SORT_DESC])
            ->asArray();

        if($start !== FALSE){
            $count = $count->andWhere("r.date_end >= {$start}");
        }
        if($end !== FALSE){
            $count = $count->andWhere("r.date_end <= {$end}");
        }
        $count = $count->count();

        return $count;
    }

    /**
     * @param $user_id
     * @param $requests_id
     * @return bool
     */
    public static function startPurpose($user_id,$requests_id)
    {
        $error = false;
        $role = Module::getRoleUser($user_id);
        if($role == User::STATUS_ACT){
            $new_purpose = new RequestsPurpose();
            $new_purpose->user_id = $user_id;
            $new_purpose->requests_id = $requests_id;
            $request = self::findOne(['id' => $requests_id, 'trash' => 0]);
            if(!empty($request)){
                if($new_purpose->save()){
                    $request->status_work = self::STATUS_WORK_PURPOSE;
                    if($request->save()){
                        $error = true;
                        $user = User::findOne(['id' => $user_id]);
                        $mail = new SendEmails(
                            Yii::$app->params['noreplyEmail'],
                            $user->email,
                            'Новая заявка №'.$requests_id,
                            "",
                            '<h3>Здравствуйте</h3>
                    <p>Вам добавили новую заявку, вы можете принять ее в работу, либо отказаться, для этого зайдите в личный кабинет, найдите заявку в разделе "Ожидания" и выполните действия</p>
                    <p><a href="'.Yii::$app->urlManager->createAbsoluteUrl('/').'">Заявки</a></p>'
                        );
                    }
                }
            }
        }
        return $error;
    }

    /**
     * @param $user_id
     * @param $requests_id
     * @return bool
     */
    public static function changePurpose($user_id,$requests_id,$user_id_old)
    {
        $error = false;
        $role = Module::getRoleUser($user_id);
        if(!empty($role == User::STATUS_ACT)){
            $new_purpose = new RequestsPurpose();
            $new_purpose->user_id = $user_id;
            $new_purpose->requests_id = $requests_id;
            $request = self::findOne(['id' => $requests_id, 'trash' => 0]);
            if(!empty($request)){
                if($new_purpose->save()){
                    $request->status_work = self::STATUS_WORK_PURPOSE;
                    if($request->save()){
                        $error = true;
                        RequestsPurpose::trashPurpose($requests_id,$user_id_old);
                    }
                }
            }
        }
        return $error;
    }

    /**
     * @param $user_id
     * @param $requests_id
     * @return bool
     */
    public static function cancelPurpose($user_id,$requests_id)
    {
        $error = false;
        $purpose = RequestsPurpose::find()->where(['requests_id' => $requests_id, 'user_id' => $user_id, 'trash' => 0])->one();
        if(!empty($purpose)){
            $purpose->trash = 1;
            $purpose->save();

            $error = true;

            $other_purpose = RequestsPurpose::find()->where([
                'requests_id' => $requests_id,
                'trash' => 0
            ])->asArray()->one();
            if(empty($other_purpose)){
                $request = Requests::findOne(['id' => $requests_id,'trash' => 0]);
                $request->status_work = Requests::STATUS_WORK_NEW;
                $request->save();
            }
        }

        return $error;
    }

    /**
     * @param $user_id
     * @param $requests_id
     * @return bool
     */
    public static function acceptPurpose($user_id,$requests_id)
    {
        $error = false;
        $purpose = RequestsPurpose::find()->where(['requests_id' => $requests_id, 'user_id' => $user_id, 'trash' => 0])->one();
        if(!empty($purpose)){

            $request = Requests::findOne(['id' => $requests_id, 'trash' => 0]);
            if(empty($request)){
                return $error;
            }

            $request->status_work = Requests::STATUS_WORK_IN;
            $request->save();
            $error = true;

            $access_client = new ClientAccess();
            $access_client->user_id = $user_id;
            $access_client->client_id = $request->client_id;
            $access_client->date_end = strtotime("+1 year");

            $access_request = new RequestsAccess();
            $access_request->user_id = $user_id;
            $access_request->requests_id = $request->id;
            if($access_client->save() && $access_request->save()){
                $error = true;
                $sms = new SmsDev(Yii::$app->params['smslogin'],Yii::$app->params['smspassword']);
                $client = '';
                $profile = UserProfile::findOne(['user_id' => $user_id, 'trash' => 0]);
                if($sms->getBalance() > 0) {
                    $client = Client::findOne(['id' => $request->client_id, 'trash' => 0]);
                    $phones = $client->getConnection('phone')->all();
                    $m_phones = !empty($profile->phones) ? json_decode($profile->phones,true) : '';
                    if(!empty($profile) && !empty($phones) && !empty($m_phones)){
                        $msg = "Компания \"СпецТехПром\"\n(Производство спецавтомобилей)\nВаш персональный менеджер\n"
                            .trim($profile->full_name)
                            ."\nтел.".preg_replace('/[\s-]*/im','',$m_phones[0])
                            ."\nE-mail:{$profile->emails}"
                            ."\nСайт:www.stpnn.ru";
                        $sms->sendSms(
                            $msg,
                            preg_replace(
                                '/[^\d]*/im',
                                '',
                                $phones[count($phones)-1]->value
                            ),
                            Yii::$app->params['smsname']);
                    }
                }
                if( !empty($client) ) {
                    $client = Client::findOne(['id' => $request->client_id, 'trash' => 0]);
                }
                if( !empty($client) ) {
                    $emails = $client->getConnection('email')->all();
                }

                if( !empty($emails) ) {
                    $emails = array_column($emails, 'value');
                    $_u_phones = !empty($profile->phones) ? json_decode($profile->phones, true) : '';
                    $out_phones = '';
                    if( $_u_phones ) {
                        foreach ($_u_phones as $key => $u_phone) {
                            if( $key > 0 ) $out_phones .= ', ';
                            $out_phones .= '<a href="tel:'.$u_phone.'">'.$u_phone.'</a>';
                        }
                    }
                    $mail = new SendEmails(
                        Yii::$app->params['noreplyEmail'],
                        $emails,
                        'STPNN - Назначен менеджер',
                        "",
                        '<h3>Здравствуйте!</h3>
                    <p>Благодарим Вас  за  обращение  в  нашу компанию <strong>ООО "СпецТехПром"   ("SpecTehProm")</strong></p><br>
                    <p>Ваш персональный менеджер отдела продаж: <b>'.$profile->full_name.'</b></p>
                    '.(!empty($out_phones) ? '<p>Телефон: '.$out_phones.'</p>' : '')
                        .(!empty($profile->emails) ? '<p>Электронная почта: <a href="mailto: '.$profile->emails.'">'.$profile->emails.'</a></p>' : '')
                        .'
                    <p>г. Нижний  Новгород, ул. Новикова - Прибоя,  дом  6 а</p>                                                                              
                    <p><b>Наш  сайт: </b>  <a href="https://stpnn.ru/" target="_blank">stpnn.ru</a></p> 
                    <p><b>YouTube канал:</b> <a href="https://www.youtube.com/channel/UC9s2plYeFgW2Ff0Izy8r-Og" target="_blank"><img src="http://crm.stpnn.ru/uploads/youtube16.png" alt="" style="display: inline-block;vertical-align: bottom;max-width: 30px;"></a></p>
                    '
                    );
                }
            }
        }

        return $error;
    }

    public static function acceptPurpose2($user_id,$requests_id)
    {
        $error = false;
        $purpose = RequestsPurpose::find()->where(['requests_id' => $requests_id, 'user_id' => $user_id, 'trash' => 0])->one();
       /* if(!empty($purpose)){

            $request = Requests::findOne(['id' => $requests_id, 'trash' => 0]);
            if(empty($request)){
                return $error;
            }
            $profile = UserProfile::findOne(['user_id' => $user_id, 'trash' => 0]);
            $client = '';
            //--------
            if( empty($client) ) {
                $client = Client::findOne(['id' => $request->client_id, 'trash' => 0]);
            }
            $emails = $client->getConnection('email')->all();

            if( !empty($emails) ) {
                $emails = array_column($emails, 'value');
                $emails = array('nice.andriyanov@mail.ru', 'nice.andriyanov@gmail.com');
                $_u_phones = !empty($profile->phones) ? json_decode($profile->phones, true) : '';
                $out_phones = '';
                if( $_u_phones ) {
                    foreach ($_u_phones as $key => $u_phone) {
                        if( $key > 0 ) $out_phones .= ', ';
                        $out_phones .= '<a href="tel:'.$u_phone.'">'.$u_phone.'</a>';
                    }
                }
                $mail = new SendEmails(
                    Yii::$app->params['noreplyEmail'],
                    $emails,
                    'STPNN - Назначан менеджер',
                    "",
                    '<h3>Здравствуйте!</h3>
                    <p>Благодарим Вас  за  обращение  в  нашу компанию <strong>ООО "СпецТехПром"   ("SpecTehProm")</strong></p><br>
                    <p>Ваш персональный менеджер отдела продаж: <b>'.$profile->full_name.'</b></p>
                    '.(!empty($out_phones) ? '<p>Телефон: '.$out_phones.'</p>' : '')
                    .(!empty($profile->emails) ? '<p>Электронная почта: <a href="mailto: '.$profile->emails.'">'.$profile->emails.'</a></p>' : '')
                    .'
                    <p>г. Нижний  Новгород, ул. Новикова - Прибоя,  дом  6 а</p>                                                                              
                    <p><b>Наш  сайт: </b>  <a href="https://stpnn.ru/" target="_blank">stpnn.ru</a></p> 
                    <p><b>YouTube канал:</b> <a href="https://www.youtube.com/channel/UC9s2plYeFgW2Ff0Izy8r-Og" target="_blank"><img src="http://crm.stpnn.ru/uploads/youtube16.png" alt="" style="display: inline-block;vertical-align: bottom;max-width: 30px;"></a></p>
                    '
                );
            }
        }*/
        return $error;
    }

    /**
     * @param $role
     * @return array
     */
    public function getDataViewInfo($role)
    {
        $result = [];
        $user_id = Yii::$app->user->id;
        $client_info = [];
        $access = true;
        if($role != 'admin' ){
            $now = strtotime('now');
            $row = ClientAccess::find()->select('id')
                ->where([
                    'client_id' => $this->client_id,
                    'user_id' => $user_id,
                    'trash' => 0
                ])/*->andWhere("date_end > {$now}")*/
                ->asArray()
                ->one();
            if(empty($row)){
                $access = false;
            }
        }

        if($role == 'admin' ){
            $result['manager'] = UserProfile::find()
                ->from(UserProfile::tableName()." as u")
                ->innerJoin(RequestsPurpose::tableName()." as rp",
                    "rp.user_id=u.user_id")
                /*->innerJoin(RequestsAccess::tableName()." as ra",
                    "ra.user_id=u.user_id")*/
                ->andWhere([
                    'rp.requests_id' => $this->id,
                    'rp.trash' => 0,
                    'u.trash' => 0,
                    //'ra.trash' => 0
                ])
                ->orderBy([
                    'rp.id' => SORT_DESC
                ])
                ->asArray()
                ->one();
            $result['notes'] = UserNote::findAll([
                'requests_id' => $this->id,
                'trash' => 0
            ]);
        }
        if($this->id == 622){
            //var_dump($access);
        }
        if($access){
            $client_info = Client::find()
                ->from(Client::tableName()." as c")
                ->select([
                    'c.id',
                    'c.firstname',
                    'c.fathername',
                    'c.lastname',
                    'c.role',
                    'c.date_birthday',
                ])
                ->where(['c.id' => $this->client_id, 'trash' => 0])
                ->one();
            $result['client_info'] = $client_info;

            if($role == User::STATUS_ACT){
                $result['notes'] = UserNote::findAll([
                    'user_id' => $user_id,
                    'requests_id' => $this->id,
                    'trash' => 0
                ]);
            }
        }

        $progress = RequestsProgress::findAll(['requests_id' => $this->id, 'trash' => 0]);

        $button = [
            'name' => 'Добавить событие',
            'items' => RequestsProgress::getAccessList($this->id,$role)
        ];

        $calc = RequestsCalculator::findAll(['requests_id' => $this->id, 'trash' => 0]);

        $result['progress'] = $progress;
        $result['button'] = $button;
        $result['calc'] = $calc;
        $result['upd'] = RequestsUpd::get($this->id);

        if($this->status == self::STATUS_SITE){
            $tmp_json = RequestsPost::find()
                ->select('data')
                ->andWhere(['requests_id' => $this->id, 'trash' => 0])
                ->asArray()->one();
            $result['request_post'] = json_decode($tmp_json['data'], true);
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        $url = '';
        $post = RequestsPost::find()->select('data')
            ->where(['requests_id' => $this->id, 'trash' => 0])
            ->asArray()
            ->one();
        if(!empty($post['data'])){
            $post = json_decode($post['data'], true);
            if(!empty($post['url'])){
                $url = $post['url'];
            }
        }

        return $url;
    }

    /**
     * @return array|mixed|null|\yii\db\ActiveRecord
     */
    public function getPostInfo()
    {
        $post = RequestsPost::find()->select('data')
            ->where(['requests_id' => $this->id, 'trash' => 0])
            ->asArray()
            ->one();
        if(!empty($post['data'])){
            $post = json_decode($post['data'], true);
        }

        return $post;
    }

    /**
     * @return float|int
     */
    public function getReqSalary($calc_id = 0)
    {
        $salary = 0;
        if($calc_id == 0){
            $calculators = RequestsCalculator::findAll([
                'requests_id' => $this->id,
                'trash' => 0
            ]);
        }else{
            $calculators = RequestsCalculator::findAll([
                'requests_id' => $this->id,
                'id' => $calc_id,
                'trash' => 0
            ]);
        }

        if(!empty($calculators)){
            $income = 0;
            foreach ($calculators as $calc) {
                $items = $calc->items;
                if(!empty($items)){
                    foreach ($items as $item) {
                        $income += floatval($item->sale ?? 0) - $item->buy ?? 0;
                    }
                }
            }
            if($income > 0) {
                $salary = $income*
                    (1-Yii::$app->params['nds'])*
                    (1-Yii::$app->params['salary'])*
                    Yii::$app->params['income'];
            }
        }

        return $salary;
    }

    public static function getBonusSalary($request_id,$calc_id = 0)
    {
        $salary = 0;
        if($calc_id == 0){
            $calculators = RequestsCalculator::findAll([
                'requests_id' => $request_id,
                'trash' => 0
            ]);
        }else{
            $calculators = RequestsCalculator::findAll([
                'requests_id' => $request_id,
                'id' => $calc_id,
                'trash' => 0
            ]);
        }

        if(!empty($calculators)){
            $income = 0;
            foreach ($calculators as $calc) {
                $items = $calc->items;
                if(!empty($items)){
                    foreach ($items as $item) {
                        $income += floatval($item->sale ?? 0) - $item->buy ?? 0;
                    }
                }
            }
            if($income > 0) {
                $salary = $income*0.1;
            }
        }

        return $salary;
    }

    /**
     * Сумма по sale у конфигуратора
     * @return float|int
     */
    public function getTotalSale()
    {
        $total = 0;
        $calculators = RequestsCalculator::findAll([
            'requests_id' => $this->id,
            'trash' => 0
        ]);

        if(!empty($calculators)){
            foreach ($calculators as $calc) {
                $items = $calc->items;
                if(!empty($items)){
                    foreach ($items as $item) {
                        $total += floatval($item->sale ?? 0);
                    }
                }
            }
        }

        return $total;
    }

    /**
     * Сумма по buy у конфигуратора
     * @return float|int
     */
    public function getTotalBuy()
    {
        $total = 0;
        $calculators = RequestsCalculator::findAll([
            'requests_id' => $this->id,
            'trash' => 0
        ]);

        if(!empty($calculators)){
            foreach ($calculators as $calc) {
                $items = $calc->items;
                if(!empty($items)){
                    foreach ($items as $item) {
                        $total += floatval($item->buy ?? 0);
                    }
                }
            }
        }

        return $total;
    }

    /**
     * Получаем связь с платежами
     * @return \yii\db\ActiveQuery
     */
    public function getPayments()
    {
        return $this->hasMany(RequestsPayment::className(),['requests_id' => 'id','trash' => '_trash']);
    }

    /**
     * Получаем сколько оплатили по конкретной заявке
     * @return float|int
     */
    public function getSuccessPayment()
    {
        $payments = $this->payments;

        $received = 0;

        if(!empty($payments)){
            $received = array_sum(array_column($payments,'received'));
        }

        return $received;
    }


    public static function getListRequestCreated($start,$end)
    {
        $rows = self::find()
            ->where(['trash' => 0])
            ->andWhere("status_work = :in or status_work = :success",
                [
                    ':in' => self::STATUS_WORK_IN,
                    ':success' => self::STATUS_WORK_SUCCESS
                ])
            ->andWhere("create_at >= {$start} and  create_at <= {$end}")
            ->asArray()
            ->all();
        if(!empty($rows)){
            foreach ($rows as $key => $row) {
                $rows[$key]['calcs'] = RequestsCalculator::find()->where([
                    'requests_id' => $row['id'],
                    'trash' => 0
                ])->asArray()->all();
                if(!empty($rows[$key]['calcs'])){
                    foreach ($rows[$key]['calcs'] as $jey => $calc) {
                        $rows[$key]['calcs'][$jey]['items'] = RequestsCalculatorItem::find()
                            ->where([
                                'calc_id' => $calc['id'],
                                'trash' => 0
                            ])->asArray()->all();
                    }
                }
            }
        }
        return $rows;
    }

    public static function getListRequestInSalary($user_id)
    {
        $rows = self::find()
            ->select('r.*')
            ->from(self::tableName()." as r")
            ->innerJoin(RequestsAccess::tableName()." as ra","r.id=ra.requests_id and ra.user_id={$user_id}")
            ->where(['r.trash' => 0,'ra.trash' => 0])
            ->andWhere("r.status_work = :in",
                [
                    ':in' => self::STATUS_WORK_IN
                ])
            ->asArray()
            ->all();
        if(!empty($rows)){
            foreach ($rows as $key => $row) {
                $rows[$key]['calcs'] = RequestsCalculator::find()->where([
                    'requests_id' => $row['id'],
                    'trash' => 0
                ])->asArray()->all();
                if(!empty($rows[$key]['calcs'])){
                    foreach ($rows[$key]['calcs'] as $jey => $calc) {
                        $rows[$key]['calcs'][$jey]['items'] = RequestsCalculatorItem::find()
                            ->where([
                                'calc_id' => $calc['id'],
                                'trash' => 0
                            ])->asArray()->all();
                    }
                }
            }
        }
        return $rows;
    }

    /**
     * @return int
     */
    public function getRequestUser()
    {
        $rows = RequestsPurpose::find()
            ->select('user_id')
            ->where([
                'requests_id' => $this->id,
                'status' => 0,
                'trash' => 0,
            ])->asArray()->one();

        return $rows['user_id'] ?? -1;
    }

    public static function getListRequestsSuccessDate($start,$end)
    {
        $result = [];
        $rows = self::find()
            ->select('id,status,client_id,title,status_work,date_end,vin')
            ->where(['trash' => 0])
            ->andWhere(['between', 'date_end', $start, $end ])
            ->all();
        if(!empty($rows)){
            foreach ($rows as $key => $row) {
                $result[] = $row->attributes;
                $buy = $row->getTotalBuy();
                $sale = $row->getTotalSale();
                $result[$key]['totals'] = [
                    'buy' => !empty($buy) ? $buy : 0,
                    'sale' => !empty($sale) ? $sale : 0
                ];
                $result[$key]['user_id'] = $row->getRequestUser();
            }
        }

        return $result;
    }

    public function toTrash()
    {
        $error = false;
        $this->trash = 1;
        if($this->save()){
            $error = true;
            $id = $this->id;
            RequestsAccess::toTrash($id,'requests_id');
            RequestsCalculator::toTrash($id,'requests_id');
            RequestsPayment::toTrash($id,'requests_id');
            RequestsProgress::toTrash($id,'requests_id');
            RequestsPurpose::toTrash($id,'requests_id');
        }
        return $error;
    }

    public function getUpd(){
        return $this->hasOne(RequestsUpd::className(),['requests_id' => 'id'])
            ->orderBy(['id' => SORT_DESC]);
    }

    public static function getLastStatus($request_id)
    {
        $result = 0;

        $row = RequestsProgress::find()
            ->select('status')
            ->where([
                'trash' => 0,
                'requests_id' => $request_id
            ])
            ->orderBy(['id' => SORT_DESC])
            ->asArray()
            ->one();
        if(!empty($row)){
            $result = $row['status'];
        }
        return $result;
    }

    /**
     * Полная информация о конфигураторе в таблицах
     * @return array
     */
    public function getDataForTables()
    {
        $result = [
            'upd' => $this->upd
        ];
        if(!$this->getTablesCopy()){ // Когда копия была сделана
            $result['calc'] = RequestsCalculator::find()
                ->where(['requests_id' => $this->id, 'trash' => 0])
                ->asArray()->all();
            if(!empty($result['calc'])){
                foreach ($result['calc'] as $key => $item) {
                    $calc_admin = new RequestsCalculatorAdmin();
                    $calc_admin->load($item,'');
                    if($calc_admin->save()){
                        $result['calc'][$key]['items'] = RequestsCalculatorItem::find()
                            ->where(['calc_id' => $item['id'], 'trash' => 0])
                            ->asArray()->all();
                        if(!empty($result['calc'][$key]['items'])){
                            foreach ($result['calc'][$key]['items'] as $_item) {
                                $calc_admin_item = new RequestsCalculatorAdminItem();
                                $calc_admin_item->load($_item,'');
                                $calc_admin_item->calc_id = $calc_admin->id;
                                $calc_admin_item->item_id = $_item['id'];
                                if(!$calc_admin_item->save()){
                                    print_r($calc_admin_item->errors);die;
                                }
                            }
                        }
                    }else{
                        print_r($calc_admin->errors);die;
                    }

                }
            }
            $copy = new RequestsCalculatorAdminCopy();
            $copy->requests_id = $this->id;
            $copy->copy = 1;
            $copy->save();
        }
        $result['calc'] = RequestsCalculatorAdmin::find()
            ->where(['requests_id' => $this->id, 'trash' => 0])
            ->all();
        if(!empty($result['calc'])){
            foreach ($result['calc'] as $key => $item) {
                $result['calc'][$key]->items = RequestsCalculatorAdminItem::find()
                    ->where(['calc_id' => $item->id, 'trash' => 0])
                    ->asArray()->all();
            }
        }
        return $result;
    }

    /**
     * @return bool
     */
    public function getTablesCopy()
    {
        $res = false;
        $copy = RequestsCalculatorAdminCopy::find()
            ->where(['requests_id' => $this->id, 'copy' => 1])
            ->asArray()->one();
        if(!empty($copy)){
            $res = true;
        }
        return $res;
    }

    public static function getTypeRequest($id){
        $result = 0;
        $cacl_ids = RequestsCalculator::find()->select('id')
            ->where(['requests_id' => $id])
            ->asArray()
            ->all();
        if(!empty($cacl_ids)){
            $cacl_ids = array_column($cacl_ids,'id');
            $rows = RequestsBilling::find()->select('type')
                ->where(['in', 'calc_id', $cacl_ids])
                ->andWhere(['trash' => 0])
                ->asArray()->all();
            if(!empty($rows)){
                $result = $rows[0]['type'];
            }
        }
        return $result;
    }

    public function getTrend(){
        $res = false;
        $row = ClientTrend::find()->where(['client_id' => $this->id,'user_id'=>Yii::$app->user->id])->asArray()->one();
        if(!empty($row)){
            $res = true;
        }

        return $res;
    }

    public static function staticGetTrend($id){
        $res = false;
        $row = ClientTrend::find()->where(['client_id' => $id,'user_id'=>Yii::$app->user->id])->asArray()->one();
        if(!empty($row)){
            $res = true;
        }

        return $res;
    }

    public function setTrend(){
        $res = false;
        $row = ClientTrend::find()->where(['client_id' => $this->id,'user_id'=>Yii::$app->user->id])->one();
        if(!empty($row)){
            $row->delete();
            $res = true;
        }else{
            $new = new ClientTrend();
            $new->user_id = Yii::$app->user->id;
            $new->client_id = $this->id;
            if($new->save()){
                $res = true;
            }
        }

        return $res;
    }

    public static function setQTender($id)
    {
        $row = Requests::findOne(['id' => $id, 'trash' => 0]);
        $row->qtender = 1;
        $row->save();
    }

    public function getMoreContacts()
    {
        $rows = RequestsClientMore::find()
            ->from(RequestsClientMore::tableName()." as rcm")
            ->select("rcm.*")
            ->innerJoin(RequestsClientMoreRel::tableName()." as rcmr", "rcmr.request_id={$this->id} AND rcm.id = rcmr.client_id")
            ->andWhere("rcm.trash=0 AND rcmr.trash=0")
            ->asArray()
            ->groupBy('rcm.id')
            ->all();

        if( !empty($rows) )
        {
            foreach ($rows as $key => $row) {
                $connections = RequestsConnection::find()
                    ->from(RequestsConnection::tableName().' as rc')
                    ->select('rc.value, rc.field')
                    ->andWhere("rc.trash=0 AND rc.client_id={$row['id']}")
                    ->asArray()
                    ->all();
                $rows[$key]['connections'] = $connections;
                $full_name = [];
                if( !empty($row['firstname']) )
                {
                    $full_name[] = $row['firstname'];
                }
                if( !empty($row['fathername']) )
                {
                    $full_name[] = $row['fathername'];
                }
                if( !empty($row['lastname']) )
                {
                    $full_name[] = $row['lastname'];
                }
                $rows[$key]['full_name'] = implode(' ', $full_name);
            }
        }

        return $rows;
    }

    public static function ExportQuery($start, $end)
    {
        $res = [];
        $car = $status = $city = '';
        $str_start = strtotime($start." 00:00:00");
        $str_end = strtotime($end." 23:59:59");
        $rows = self::find()
            ->andWhere("create_at >= {$str_start} AND create_at <= {$str_end} AND trash = 0")
            ->asArray()
            ->orderBy(['id' => SORT_ASC])
            ->groupBy("id")
            ->all();
        if( !empty($rows) ) {
            foreach ($rows as $key => $row) {
                //Подгрузить запрос с сайта
                $post = RequestsPost::find()
                    ->select('data')
                    ->andWhere(['requests_id' => $row['id']])
                    ->asArray()
                    ->one();
                if( !empty($post['data']) ) {
                    $post = json_decode($post['data'], true);
                }
                //Клиент
                $client = Client::find()->andWhere(['id' => $row['client_id']])->one();
                $client_out = $client->getFullName();
                $connects = $client->connection;
                if( !empty($connects) ) {
                    foreach ($connects as $connect) {
                        $client_out .= ", ".($connect->field == 'phone' ? 'т.' : '').$connect->value;
                    }
                }

                //Менеджер
                $last_manager = UserProfile::find()
                    ->from(UserProfile::tableName()." as up")
                    ->select('up.full_name')
                    ->innerJoin(ClientAccess::tableName()." as ca", "up.user_id=ca.user_id AND ca.trash=0")
                    ->asArray()
                    ->orderBy(["ca.id" => SORT_DESC])
                    ->andWhere("ca.client_id={$row['client_id']}")
                    ->one();

                //Сумма
                $calc = RequestsCalculator::find()
                    ->from(RequestsCalculator::tableName()." as rc")
                    ->select('rc.id as ID, rc.title as name, rci.title, rci.sale, rci.buy')
                    ->innerJoin(RequestsCalculatorItem::tableName(). " as rci",
                        "rci.calc_id=rc.id")
                    ->where([
                        'rc.requests_id' => $row['id'],
                        'rci.trash' => 0,
                        'rc.trash' => 0
                    ])
                    ->asArray()->all();
                $summa = $cost = $bonus = 0;
                if( !empty($calc) ) {
                    $summa = array_sum(array_column($calc, 'sale'));
                    $cost = array_sum(array_column($calc, 'buy'));
                    $car = implode(', ', array_column($calc, 'title'));
                    $bonus = round(AppFunctions::getBonus($cost, $summa), 2);
                }

                //Что заказали
                if( empty($car) && !empty($post['car']) ) {
                    $car = $post['car'];
                }

                //Статус
                $status = self::getStStatusesWorkText($row['status_work']);
                if( $row['date_end'] == 0 && $row['status_work'] == self::STATUS_WORK_SUCCESS )
                    $status = 'Отказ';

                //Город
                if( !empty($post['your-city']) ) {
                    $city = $post['your-city'];
                }


                //Результат
                $res[] = [
                    $row['id'],
                    date('d.m.Y H:i', $row['create_at']),
                    $client_out,
                    !empty($last_manager['full_name']) ? $last_manager['full_name'] : 'Нет',
                    $summa,
                    $car,
                    $bonus,
                    $status,
                    $city,
                    !empty($post['utm']['utm_source']) ? $post['utm']['utm_source'] : '',
                    !empty($post['utm']['utm_medium']) ? $post['utm']['utm_medium'] : '',
                    !empty($post['utm']['utm_campaign']) ? $post['utm']['utm_campaign'] : '',
                    !empty($post['utm']['utm_content']) ? $post['utm']['utm_content'] : '',
                    !empty($post['utm']['utm_term']) ? $post['utm']['utm_term'] : '',
                ];

            }
        }
        return $res;
    }

    public static function getFindUserRequest($user_id, $start, $end, $find = true)
    {
        $row = self::find()
            ->select("r.*")
            ->from(self::tableName()." as r")
            ->innerJoin(RequestsAccess::tableName()." as ra", "ra.requests_id=r.id and ra.user_id={$user_id}")
            ->andWhere("r.create_at >= {$start} and r.create_at <= {$end} and ra.trash=0");

        if( $find ) {
            $row = $row->asArray()->all();
        }
        else {
            $row = $row->count();
        }
        return $row;
    }

    public static function getFindUserRequestNotCancel($user_id, $start, $end, $find = true)
    {
        $row = self::find()
            ->select("r.*")
            ->from(self::tableName()." as r")
            ->innerJoin(RequestsAccess::tableName()." as ra", "ra.requests_id=r.id and ra.user_id={$user_id}")
            ->andWhere("r.create_at >= {$start} and r.create_at <= {$end} and ra.trash=0")
            ->andWhere("status_work <> 4 or (status_work = 4 and date_end > 0)");

        if( $find ) {
            $row = $row->asArray()->all();
        }
        else {
            $row = $row->count();
        }
        return $row;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLizing(){
        return $this->hasOne(ClientLizing::className(),['id' => 'element_id'])
            ->where(['trash' => 0])
            ->via('pivotLizing');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPivotLizing(){
        return $this->hasOne(ClientLizingRel::className(), ['request_id' => 'id'])//, 'request_id' => 'now_request'
        ->orderBy(['id' => SORT_DESC]);
    }

    /**
     * Прогнозируемые оплаты получить
     * @return array
     */
    public function getPaymentForecast()
    {
        $rows = RequestsPaymentPrognosis::find()
            ->where(['request_id' => $this->id, 'trash' => 0])
            ->orderBy(['date' => SORT_ASC])
            ->asArray()->all();

        return $rows;
    }

    /**
     * Прогнозируемые дата закрытия
     * @return array
     */
    public function getForecast()
    {
        $rows = RequestsPrognosis::find()
            ->where(['request_id' => $this->id, 'trash' => 0])
            ->orderBy(['date' => SORT_ASC])
            ->asArray()->all();

        return $rows;
    }
    public static function getSForecast($id)
    {
        $rows = RequestsPrognosis::find()
            ->select('request_id,date')
            ->where(['request_id' => $id, 'trash' => 0])
            ->orderBy(['date' => SORT_ASC])
            ->asArray()->all();

        return $rows;
    }

    /**
     * Прогнозируемые оплаты установить
     */
    public function addPaymentForecast()
    {

    }

    /**
     * Прогнозируемые оплаты удалить
     */
    public function removePaymentForecast()
    {

    }


}
