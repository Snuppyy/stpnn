<?php

namespace app\models;
use app\models\App\Request\Requests;
use app\models\App\Request\RequestsAccess;
use app\models\App\Request\RequestsCalculator;
use app\models\App\UserPremiums;
use app\models\App\UserProfile;
use app\models\App\UserSalary;
use app\modules\admin\Module;
use mdm\admin\models\User as UserModel;
use yii\helpers\VarDumper;
/**
 * @property \app\models\App\UserProfile $profile
 * @property \app\models\App\UserSalary $salary
 * @property \app\models\App\UserPremiums $premiums
 */
class User extends UserModel
{
    const STATUS_ACT = 'manager';
    const STATUS_ACT_TEXT = 'Активный';
    const STATUS_NON_ACT = 'manager_disabled';
    const STATUS_NON_ACT_TEXT = 'Не активный';
    const STATUS_ACCOUNTANT = 'accountant';
    const STATUS_ACCOUNTANT_TEXT = 'Бухгалтер';

    /**
     * @param $user_id
     * @return bool|string
     */
    public static function getStatusUserText($user_id)
    {
        $role = \Yii::$app->authManager->getRolesByUser($user_id);
        if(!empty($role[self::STATUS_ACT])){
           return '<i class="ft-disc success font-medium-1 mr-1"></i>'.self::STATUS_ACT_TEXT;
        }elseif(!empty($role[self::STATUS_NON_ACT])){
            return '<i class="ft-disc danger font-medium-1 mr-1"></i>'.self::STATUS_NON_ACT_TEXT;
        }

        return false;
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function findActive()
    {
        $status = self::STATUS_ACT;

        return User::find()
            ->select('u.id as ID,u.username,u.email,a.item_name,up.*')
            ->from(User::tableName().' as u')
            ->innerJoin(
                'auth_assignment as a',
                "a.item_name='{$status}' AND a.user_id=u.id")
            ->leftJoin(
                UserProfile::tableName().' as up',
                "up.user_id=u.id AND up.trash=0")
                ->asArray()
                ->all();
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getListAllManagers()
    {
        return self::find()
            ->select('u.id as ID,u.username,u.email,up.*')
            ->from(self::tableName().' as u')
            ->leftJoin(
                UserProfile::tableName().' as up',
                "up.user_id=u.id AND up.trash=0")
            ->where("u.id <> 1")
            ->asArray()
            ->all();
    }

    public static function getNameAllManagers()
    {
        $rows = self::find()
        ->select('up.full_name, u.ID')
        ->from(self::tableName().' as u')
        ->leftJoin(
            UserProfile::tableName().' as up',
            "up.user_id=u.id AND up.trash=0")
        ->where("u.id <> 1")
        ->asArray()
        ->all();
        foreach ($rows as $key => $row) {
            $return[$row['ID']] = $row['full_name'];
        }

        return $return;
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function findManagerForSalary($end,$user_id = 0)
    {
        $status_act = self::STATUS_ACT;
        $status_non_act = self::STATUS_NON_ACT;
        $first_day = strtotime("first day of this month ".date('d.m.Y',$end));
        $rows = User::find()
            ->select('u.id as ID,u.username,u.email,a.item_name,up.*')
            ->from(User::tableName().' as u')
            ->innerJoin(
                'auth_assignment as a',
                "(a.item_name='{$status_act}' or a.item_name='{$status_non_act}' AND a.created_at>{$first_day}) AND a.user_id=u.id")
            ->leftJoin(
                UserProfile::tableName().' as up',
                "up.user_id=u.id AND up.trash=0")
            ->andWhere("u.created_at <= {$end} ")
            ->orderBy(['u.created_at' => SORT_ASC])
            ->asArray();
        if(!empty($user_id)){
            $rows = $rows->andWhere(['u.id' => $user_id]);
        }

        $rows = $rows->all();

        return $rows;
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function findNonActive()
    {
        $status = self::STATUS_NON_ACT;
        return User::find()
            ->select('u.id as ID,u.username,u.email,a.item_name,up.*')
            ->from(User::tableName().' as u')
            ->innerJoin(
                'auth_assignment as a',
                "a.item_name='{$status}' AND a.user_id=u.id")
            ->leftJoin(
                UserProfile::tableName().' as up',
                "up.user_id=u.id AND up.trash=0")
            ->asArray()
            ->all();
    }

    public function beforeDelete()
    {
        \Yii::$app->authManager->revokeAll($this->id);
        return parent::beforeDelete();
    }

    /**
     * @param $user_id
     * @return string
     */
    public static function getUserAvatar($user_id)
    {
        $avatar = '/uploads/avatars/not_avatar.jpg';
        $row = UserProfile::find()->select('avatar')->where(['user_id' => $user_id])->one();
        if(!empty($row['avatar'])){
            $avatar = $row['avatar'];
        }
        return $avatar;
    }

    public static function getListManagers($all = false)
    {
        if( !$all ) {
            $rows = self::findActive();
        }
        else {
            $rows = self::getListAllManagers();
        }

        if(!empty($rows)){
            $buh = '';
            foreach ($rows as $key => $row) {
                if( $row['ID'] == 18 ) {
                    $buh = $key;
                    continue;
                }
                $rows[$key]['count']['in'] = Requests::getListInForUserCount($row['ID']);
                $rows[$key]['count']['purpose'] = Requests::getListPurposeForUserCount($row['ID']);
                $rows[$key]['count']['success'] = Requests::getListSuccessForUserCount($row['ID']);
                /*$rows[$key]['count']['in'] = 0;
                $rows[$key]['count']['purpose'] = 0;
                $rows[$key]['count']['success'] = 0;*/
            }
            if( !empty($buh) ) {
                unset($rows[$buh]);
            }
            $rows = array_values($rows);
        }

        return $rows;
    }

    public function getProfile()
    {
        return $this->hasOne(UserProfile::className(),['user_id' => 'id'])
            ->where(['trash' => 0]);
    }

    public function getSalary()
    {
        return $this->hasOne(UserSalary::className(),['user_id' => 'id'])
            ->orderBy(['date_start' => SORT_DESC]);
    }

    public function getProfit($start = 0,$end = 0)
    {
        if(empty($start)){
            $start = strtotime("first day of this month 00:00:00");
        }
        if(empty($end)){
            $end = strtotime("last day of this month 23:59:59");
        }

        //По всем заявкам которые назначенные данные пользователю и они в работе
        $total = 0;
        $in = Requests::getListInForUser($this->id);
        if($in){
            foreach ($in as $item) {
                $calcs = RequestsCalculator::findAll(['requests_id' => $item['id'], 'trash' => 0]);
                if(!empty($calcs)){
                    foreach ($calcs as $calc) {
                        $rows = $calc->items;
                        if(!empty($rows)){
                            foreach ($rows as $row) {
                                $total += $row->sale-$row->buy;
                            }
                        }
                    }
                }

            }
        }

        return $total;
    }

    public function getBonusProfit($start = 0,$end = 0)
    {
        if(empty($start)){
            $start = strtotime("first day of this month 00:00:00");
        }
        if(empty($end)){
            $end = strtotime("last day of this month 23:59:59");
        }

        //По всем заявкам которые назначенные данные пользователю и они в работе
        $total = $this->getProfit($start,$end);

        return $total*(1-\Yii::$app->params['nds'])*
            (1-\Yii::$app->params['salary'])*
            \Yii::$app->params['income'];;
    }

    public function getSuccessProfit($start = 0,$end = 0)
    {
        if(empty($start)){
            $start = strtotime("first day of this month 00:00:00");
        }
        if(empty($end)){
            $end = strtotime("last day of this month 23:59:59");
        }

        $total = 0;
        $successed = Requests::find()
            ->from(Requests::tableName()." as r")
            ->select('r.*')
            ->innerJoin(RequestsAccess::tableName()." as ra",
            "ra.user_id={$this->id} and 
                ra.access=0 and 
                ra.trash=0 and 
                ra.requests_id=r.id")
            ->where("r.date_end >= {$start} and r.date_end <= {$end}")
            ->andWhere(['r.status_work' => Requests::STATUS_WORK_SUCCESS])
            ->asArray()->all();
        if($successed){
            foreach ($successed as $item) {
                $total += Requests::getBonusSalary($item['id']);
            }
        }
        return $total;
    }

    public function getPremiums()
    {
        return $this->hasMany(UserPremiums::className(),['user_id' => 'id'])
            ->orderBy(['date' => SORT_NUMERIC])->andWhere(['trash' => 0]);
    }

    public static function getHistorySalary($user_id, $start = null){
        $rows = UserSalary::find()
            ->select('value,date_start')
            ->where(['user_id' => $user_id]);
        if(!empty($start)){
            $rows = $rows->andWhere("date_start >= $start");
        }
        $rows = $rows->asArray()->all();

        return $rows;
    }

    public static function getPremiumsPeriod($user_id = null, $start, $end){
        $res = 0;
        if( $user_id == null ) {
            $rows = UserPremiums::find()
                ->select("value,color")
                ->andWhere("date >= $start and date <= $end")
                ->andWhere(['trash' => 0])
                ->asArray()->all();
        }
        else {
            $rows = UserPremiums::find()
                ->select("value,color")
                ->andWhere(['user_id' => $user_id])
                ->andWhere("date >= $start and date <= $end")
                ->andWhere(['trash' => 0])
                ->asArray()->all();
        }
        if(!empty($rows)) {
            foreach ($rows as $row) {
                if(!empty($row['color'])){
                    $res += $row['value'];
                }/*else{
                    $res -= $row['value'];
                }*/
            }
        }
        return $res;
    }

    public function getPremiumsPeriodUser($start, $end){
        $res = 0;
        $rows = UserPremiums::find()
            ->select("value,color")
            ->andWhere("date >= $start and date <= $end")
            ->andWhere(['trash' => 0, 'user_id' => $this->id])
            ->asArray()->all();
        if(!empty($rows)) {
            foreach ($rows as $row) {
                if(!empty($row['color'])){
                    $res += $row['value'];
                }else{
                    $res -= $row['value'];
                }
            }
        }
        return $res;
    }
}
