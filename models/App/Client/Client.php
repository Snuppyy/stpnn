<?php

namespace app\models\App\Client;

use app\components\AppFunctions;
use app\models\App\Request\Requests;
use app\models\App\Request\RequestsProgress;
use app\models\App\UserProfile;
use app\models\User;
use Yii;
use yii\helpers\Url;

/**
 * This is the model class for table "{{%client}}".
 *
 * @property int $id
 * @property string $firstname
 * @property string $fathername
 * @property string $lastname
 * @property string $position
 * @property int $role
 * @property int $date_birthday
 * @property int $user_id
 * @property int $create_at
 * @property int $update_at
 * @property int $trash
 * @property ClientOrganization $organization
 * @property ClientLizing $lizing
 * @property ClientOtherInfo $otherinfo
 * @property UserProfile $manager
 */
class Client extends \yii\db\ActiveRecord
{
    public $name_firm;
    public $contact_info;
    public $count_request;
    public $managers;
    public $income;

    public $request;
    public $msg;
    public $phone;
    public $email;

    const ROLE_FIZ = 0;
    const ROLE_FIZ_TEXT = 'Физическое лицо';

    const ROLE_UR = 1;
    const ROLE_UR_TEXT = 'Юридическое лицо';

    const MESSAGE_NOT_TEXT = 'Не определено';

    public $now_request;

    public $_trash = 0;
    public $_file = 0;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%client}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['firstname'], 'required'],
            [['user_id', 'create_at', 'update_at', 'trash'], 'integer'],
            [['date_birthday'], 'goIntValue'],
            ['role','in', 'range' =>[
                self::ROLE_FIZ,
                self::ROLE_UR
            ]],
            [['firstname', 'fathername', 'lastname', 'position'], 'string', 'max' => 255],
            [['request','msg','phone','email', 'now_request'], 'safe'],
            [['_file'], 'safe'],
        ];
    }

    public function goIntValue(){
        if(!empty($this->date_birthday) && !is_numeric($this->date_birthday)){
            $this->date_birthday = strtotime($this->date_birthday);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'firstname' => Yii::t('app', 'Firstname'),
            'fathername' => Yii::t('app', 'Fathername'),
            'lastname' => Yii::t('app', 'Lastname'),
            'role' => Yii::t('app', 'Role'),
            'date_birthday' => Yii::t('app', 'Date Birthday'),
            'user_id' => Yii::t('app', 'User ID'),
            'create_at' => Yii::t('app', 'Create At'),
            'update_at' => Yii::t('app', 'Update At'),
            'trash' => Yii::t('app', 'Trash'),
            'name_firm' => 'Название организации',
            'contact_info' => 'Контактные данные',
            'count_request' => 'Количество заявок',
            'managers' => 'Менеджеры',
            'income' => 'Сум.контрактов',
            'position' => 'Должность',
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
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        $post = Yii::$app->request->post();
        //if(!$insert){
            if(!empty($post['ClientConnection']['phone']) || !empty($post['ClientConnection']['email'])){
                if(!empty($post['ClientConnection']['phone'])){
                    $rows = ClientConnection::findAll([
                        'client_id' => $this->id,
                        'field' => 'phone',
                        'trash' => 0,
                    ]);
                    $del_phones = $phones = array_column($rows,'value');
                    foreach ($post['ClientConnection']['phone'] as $item) {
                        $_item = AppFunctions::clearPhoneNumber($item);
                        if(!in_array($_item,$phones)){
                            $new = new ClientConnection();
                            $new->client_id = $this->id;
                            $new->value = $_item;
                            $new->field = 'phone';
                            $new->save();
                        }else{
                            $key = array_search($_item,$phones);
                            if($key !== FALSE){
                                unset($del_phones[$key]);
                            }
                        }
                    }
                    if(!empty($del_phones)){
                        foreach ($del_phones as $key => $phone) {
                            $rows[$key]->trash = 1;
                            $rows[$key]->save();
                        }
                    }
                }
                if(!empty($post['ClientConnection']['email'])){
                    $rows = ClientConnection::findAll([
                        'client_id' => $this->id,
                        'field' => 'email',
                        'trash' => 0,
                    ]);
                    $del_emails = $emails = array_column($rows,'value');
                    foreach ($post['ClientConnection']['email'] as $item) {
                        $_item = AppFunctions::clearField($item);
                        if(!in_array($_item,$emails)){
                            $new = new ClientConnection();
                            $new->client_id = $this->id;
                            $new->value = $_item;
                            $new->field = 'email';
                            $new->save();
                        }else{
                            $key = array_search($_item,$emails);
                            if($key !== FALSE){
                                unset($del_emails[$key]);
                            }
                        }
                    }

                    if(!empty($del_emails)){
                        foreach ($del_emails as $key => $email) {
                            $rows[$key]->trash = 1;
                            $rows[$key]->save();
                        }
                    }
                }
            }

            if(!empty($this->phone)){
                if( !is_array($this->phone) ) {
                    $this->phone = AppFunctions::clearPhoneNumber($this->phone);
                    $rows = ClientConnection::findAll([
                        'client_id' => $this->id,
                        'field' => 'phone',
                        'trash' => 0,
                    ]);
                    $first = mb_substr($this->phone,0,1);
                    $needly = $first == 7 || $first == 8 ? mb_substr($this->phone,1) : $this->phone;
                    $addedPhone = true;
                    foreach ($rows as $row) {
                        if(strpos($row->value,$needly) !== FALSE ){
                            $addedPhone = false;
                            break;
                        }
                    }
                    if($addedPhone) {
                        $new_phone = new ClientConnection();
                        $new_phone->client_id = $this->id;
                        $new_phone->value = str_replace(' ', '', $this->phone);
                        $new_phone->field = 'phone';
                        $new_phone->save();
                    }
                }
                else {
                    foreach ($this->phone as $_phone) {
                        if( empty($_phone) ) continue;
                        $phone = AppFunctions::clearPhoneNumber($_phone);
                        $rows = ClientConnection::findAll([
                            'client_id' => $this->id,
                            'field' => 'phone',
                            'trash' => 0,
                        ]);
                        $first = mb_substr($phone,0,1);
                        $needly = $first == 7 || $first == 8 ? mb_substr($phone,1) : $phone;
                        $addedPhone = true;
                        foreach ($rows as $row) {
                            if(strpos($row->value,$needly) !== FALSE ){
                                $addedPhone = false;
                                break;
                            }
                        }
                        if($addedPhone) {
                            $new_phone = new ClientConnection();
                            $new_phone->client_id = $this->id;
                            $new_phone->value = str_replace(' ', '', $phone);
                            $new_phone->field = 'phone';
                            $new_phone->save();
                        }
                    }
                }

            }
            if(!empty($this->email)){
                if( !is_array($this->email) ) {
                    $this->email = AppFunctions::clearField($this->email);
                    $rows = ClientConnection::findAll([
                        'client_id' => $this->id,
                        'field' => 'email',
                        'trash' => 0,
                    ]);

                    $addedEmail = true;
                    foreach ($rows as $row) {
                        if(strpos($row->value,$this->email) !== FALSE ){
                            $addedEmail = false;
                            break;
                        }
                    }
                    if($addedEmail) {
                        $new_email = new ClientConnection();
                        $new_email->client_id = $this->id;
                        $new_email->value = str_replace(' ', '', $this->email);
                        $new_email->field = 'email';
                        $new_email->save();
                    }
                }
                else {
                    foreach ($this->email as $_email) {
                        $email = AppFunctions::clearField($_email);
                        $rows = ClientConnection::findAll([
                            'client_id' => $this->id,
                            'field' => 'email',
                            'trash' => 0,
                        ]);

                        $addedEmail = true;
                        foreach ($rows as $row) {
                            if(strpos($row->value,$email) !== FALSE ){
                                $addedEmail = false;
                                break;
                            }
                        }
                        if($addedEmail) {
                            $new_email = new ClientConnection();
                            $new_email->client_id = $this->id;
                            $new_email->value = str_replace(' ', '', $email);
                            $new_email->field = 'email';
                            $new_email->save();
                        }
                    }
                }

            }

        //}
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @return array
     */
    public static function getRoles(){
        return [
            self::ROLE_FIZ => self::ROLE_FIZ_TEXT,
            self::ROLE_UR => self::ROLE_UR_TEXT,
        ];
    }

    /**
     * @return string
     */
    public function getRoleText(){
        $roles = self::getRoles();
        return !empty($roles[$this->role]) ? $roles[$this->role] : self::MESSAGE_NOT_TEXT;
    }

    /**
     * @param string|null $phone
     * @param string|null $email
     * @return array|bool
     */
    public static function findClientIDConnection($phone = null, $email = null, $strictly = true)
    {
        if(empty($phone) && empty($email))
            return false;

        $client_ids = ClientConnection::find()
            ->select('client_id');
        if(!empty($phone)) {
            if( !is_array($phone) ) {
                $phone = AppFunctions::clearPhoneNumber($phone);
                $first = mb_substr($phone, 0, 1);
                if ($first == 7 || $first == 8) {
                    $phone = mb_substr($phone, 1);
                }
                $phone_ids = clone $client_ids;
                $phone_ids = $phone_ids->andWhere("field = 'phone' AND trash=0 AND value LIKE '%{$phone}%'")
                    ->orderBy(['client_id' => SORT_ASC])->groupBy(['client_id'])->asArray()->all();
                if(!empty($phone_ids)){
                    $phone_ids = array_column($phone_ids,'client_id');
                }
            }
            else {
                $phones = array_diff(array_values(array_unique($phone)), array(''));
                if( !empty($phones) ) {
                    unset($phone);
                    $where_p = '(';
                    foreach ($phones as $key => $_phone) {
                        if( empty($_phone) ) continue;
                        $phone = AppFunctions::clearPhoneNumber($_phone);
                        $first = mb_substr($phone, 0, 1);
                        if ($first == 7 || $first == 8) {
                            $phone = mb_substr($phone, 1);
                        }
                        if( $key > 0 ) {
                            $where_p .= ' OR ';
                        }
                        $where_p .= " value LIKE '%{$phone}%'";
                    }
                    $where_p .= ')';

                    $phone_ids = clone $client_ids;
                    $phone_ids = $phone_ids->andWhere("field = 'phone' AND trash=0 AND {$where_p}")
                        ->orderBy(['client_id' => SORT_ASC])->groupBy(['client_id'])->asArray()->all();
                    if(!empty($phone_ids)){
                        $phone_ids = array_column($phone_ids,'client_id');
                    }
                }
            }
        }
        if(!empty($email)){
            if( !is_array($email) ) {
                $email_ids = clone $client_ids;
                $email_ids = $email_ids->andWhere("field = 'email' AND trash=0 AND value LIKE '%{$email}%'")
                    ->orderBy(['client_id' => SORT_ASC])->groupBy(['client_id'])->asArray()->all();
                if(!empty($email_ids)){
                    $email_ids = array_column($email_ids,'client_id');
                }
            }
            else {
                $emails = array_diff(array_values(array_unique($email)), array(''));
                if( !empty($emails) ) {
                    unset($email);
                    $where_e = '(';
                    foreach ($emails as $key => $email) {
                        if( empty($email) ) continue;
                        if( $key > 0 ) {
                            $where_e .= ' OR ';
                        }
                        $where_e .= " value LIKE '%{$email}%'";
                    }
                    $where_e .= ')';

                    $email_ids = clone $client_ids;
                    $email_ids = $email_ids->andWhere("field = 'email' AND trash=0 AND {$where_e}")
                        ->orderBy(['client_id' => SORT_ASC])->groupBy(['client_id'])->asArray()->all();
                    if(!empty($email_ids)){
                        $email_ids = array_column($email_ids,'client_id');
                    }
                }

            }

        }
        if(!empty($phone_ids)){
            if(empty($email_ids)){
                $client_ids = array_unique($phone_ids);
            }else{
                if($strictly){
                    $client_ids = array_intersect($phone_ids,$email_ids);
                }else{
                    $client_ids = array_merge($phone_ids,$email_ids);
                }
                if(empty($client_ids)){
                    $client_ids = $phone_ids;
                }
                $client_ids = array_unique($client_ids);
            }
        }elseif(!empty($email_ids)){
            $client_ids = array_unique($email_ids);
        }else{
            $client_ids = false;
        }
        if(empty($client_ids)){
            return false;
        }
        return $client_ids;
    }

    /**
     * @param array $client
     * @return Client|bool
     */
    public static function createClient(array $client)
    {
        $model = new Client();
        if($model->load($client,'') && $model->save())
            return $model;

        return false;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConnection($name = ''){
        $db = $this->hasMany(ClientConnection::className(),['client_id' => 'id'])
            ->orderBy(['field' => SORT_DESC])
            ->where(['trash' => 0]);
        if(!empty($name)){
            $db->andWhere(['field' => $name]);
        }
        return $db;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrganization(){
        return $this->hasOne(ClientOrganization::className(),['id' => 'element_id'])
            ->where(['trash' => 0])
            ->via('pivotOrganization');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPivotOrganization(){
        return $this->hasOne(ClientOrganizationRel::className(), ['client_id' => 'id'])
            ->orderBy(['id' => SORT_DESC]);
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
        return $this->hasOne(ClientLizingRel::className(), ['client_id' => 'id'])//, 'request_id' => 'now_request'
            ->orderBy(['id' => SORT_DESC]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOtherinfo(){
        return $this->hasMany(ClientOtherInfo::className(),['client_id' => 'id'])
            ->where(['trash' => 0]);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getManager(){

        return $this->hasMany(UserProfile::className(),['user_id' => 'user_id'])
            ->where(['trash' => 0])
            ->orderBy(['id'=>SORT_DESC])
            ->via('pivotManager');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPivotManager(){
        $now = strtotime("now");
        return $this->hasMany(ClientAccess::className(), ['client_id' => 'id'])
            ->where(['trash' => 0]);
            //->andWhere("date_end >= {$now}");
    }

    /**
     * @return null|string
     */
    public function getNameOrg()
    {
        $name = null;
        if($this->role == self::ROLE_UR){
            $org = $this->organization;
            $name = '<a href="'.Url::to(['/admin/client/view', 'id' => $this->id]).'">'.$org->title.'</a>';
        }
        return $name;
    }

    /**
     * @return string
     */
    public function getContactInfo()
    {
        $contact = $this->connection;
        $out = '';
        $out .= "<div class='row no-gutters'>";
            $out .= '<div class="col-6">';
                $out .= '<a href="'.Url::to(['/admin/client/view/','id' => $this->id]).'">
                            '.(!empty($this->lastname) ? $this->lastname.' ' : '').'
                             '.(!empty($this->firstname) ? $this->firstname.' ' : '').'
                            '.(!empty($this->fathername) ? $this->fathername.' ' : '').'
                        </a>';
            $out .= '</div>';
            $out .= '<div class="col-6">';
            foreach ($contact as $item) {
                $out .= '<div class="d-block text-truncate" data-toggle="tooltip"
                        data-trigger="click"
                        data-html="true"
                        data-placement="top"
                        data-original-title="'.$item->value.'">
                            '.$item->value.'
                        </div>';
            }
            $out .= '</div>';
        $out .= '</div>';
        return $out;
    }

    /**
     * @return int|string
     */
    public function getCountRequest()
    {
        $count = 0;
        $count = Requests::find()->where([
            'trash' => 0,
            'client_id' => $this->id
        ])->asArray()->count();

        return $count;
    }

    /**
     * @return string
     */
    public function getManagers()
    {
        $out = '';
        ob_start();
        $rows = UserProfile::find()
            ->from(UserProfile::tableName()." as up")
            ->innerJoin(ClientAccess::tableName()." as ca",
                "ca.client_id={$this->id} and ca.user_id=up.user_id and ca.trash=0 and up.trash=0")
            ->asArray()->all();
        if(!empty($rows)){
             foreach ($rows as $row) { ?>
<div class="d-block">
  <a href="<?= Url::to(['/admin/user/view','id' => $row['user_id']]); ?>" target="_blank">
    <?= $row['full_name']; ?>
  </a>
</div>
<?php }
        }
        $out = ob_get_clean();
        return $out;
    }

    public function getIncome()
    {
        $income = 0;

        $requests = Requests::findAll([
            'trash' => 0,
            'client_id' => $this->id
        ]);
        if(!empty($requests)){
            /** @var  $request Requests*/
            foreach ($requests as $request) {
                $income += $request->getTotalSale();
            }
        }

        return $income;
    }

    public function toTrash()
    {
        $error = false;
        $this->trash = 1;
        if($this->save()){
            $error = true;
            $requests = Requests::findAll(['client_id' => $this->id]);

            if(!empty($requests)){
                /** @var $request Requests*/
                foreach ($requests as $request){
                    $request->toTrash();
                }
            }
            ClientAccess::updateAll(
                [
                    'trash' => 1
                ],
                [
                    'client_id' => $this->id
                ]
            );
            ClientConnection::updateAll(
                [
                    'trash' => 1
                ],
                [
                    'client_id' => $this->id
                ]
            );
            ClientOtherInfo::updateAll(
                [
                    'trash' => 1
                ],
                [
                    'client_id' => $this->id
                ]
            );

            $rel = $this->pivotOrganization;
            if(!empty($rel)){
                /** @var $rel ClientOrganizationRel*/
                $rel->trash = 1;
                $rel->save();

            }
        }

        return $error;
    }

    public function getFullName() {
        $res = [];
        if( !empty($this->firstname) ) {
            $res[] = $this->firstname;
        }
        if( !empty($this->fathername) ) {
            $res[] = $this->fathername;
        }
        if( !empty($this->lastname) ) {
            $res[] = $this->lastname;
        }

        return implode(' ', $res);
    }

    public static function FullName($id) {
        $res = [];
        $client = self::find()->where(['id' => $id])->select('id,firstname,fathername,lastname')->asArray()->one();
        if( !empty($client['firstname']) ) {
            $res[] = $client['firstname'];
        }
        if( !empty($client['fathername']) ) {
            $res[] = $client['fathername'];
        }
        if( !empty($client['lastname']) ) {
            $res[] = $client['lastname'];
        }

        return implode(' ', $res);
    }

    public static function getLastManager($id)
    {
        $manager = [];
        $request = Requests::find()
            ->from(Requests::tableName()." as r")
            ->select('r.id as requests_id, p.user_id')
            ->innerJoin(RequestsProgress::tableName()." as p", "p.requests_id=r.id and p.user_id <> 1")
            ->where(['r.client_id' => $id])
            ->orderBy(['r.id' => SORT_DESC, 'p.id' => SORT_DESC])
            ->asArray()
            ->one();

        $manager_id = 0;
        if( !empty($request['user_id']) ){
            $manager_id = $request['user_id'];
        }
        else {
            $user_id = ClientAccess::find()
                ->select('id,user_id')
                ->where(['client_id' => $id, 'trash' => 0])
                ->orderBy(['id' => SORT_DESC])
                ->asArray()
                ->on();
            if( !empty($user_id['user_id']) ) {
                $manager_id = $user_id['user_id'];
            }
        }

        if( !empty($manager_id) ) {
            $role = Yii::$app->authManager->getRolesByUser($manager_id);

            if( isset($role['manager']) ){
                $manager = User::find()
                    ->from(User::tableName()." as u")
                    ->select("up.*")
                    ->innerJoin(UserProfile::tableName()." as up", "up.user_id=u.id")
                    ->where(['u.id' => $manager_id, 'up.trash' => 0])
                    ->asArray()
                    ->one();
                //print_r($manager);die;
            }
        }
        //print_r($request);die;

        return $manager;
    }

}