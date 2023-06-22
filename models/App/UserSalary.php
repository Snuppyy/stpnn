<?php

namespace app\models\App;

use app\models\User;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%user_salary}}".
 *
 * @property int $id
 * @property int $user_id
 * @property double $value
 * @property int $date_start
 * @property int $create_at
 * @property int $update_at
 * @property int $trash
 */
class UserSalary extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user_salary}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'value'], 'required'],
            [['user_id', 'create_at', 'update_at', 'trash'], 'integer'],
            [['date_start'], 'goIntValue'],
            [['value'], 'number'],
        ];
    }

    public function goIntValue()
    {
        if(!empty($this->date_start) && !is_numeric($this->date_start)){
            $this->date_start = strtotime($this->date_start);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'value' => Yii::t('app', 'Value'),
            'date_start' => Yii::t('app', 'Date Start'),
            'create_at' => Yii::t('app', 'Create At'),
            'update_at' => Yii::t('app', 'Update At'),
            'trash' => Yii::t('app', 'Trash'),
        ];
    }

    public function beforeSave($insert)
    {
        if($insert) {
            $this->create_at = strtotime("now");
        }
        $this->update_at = strtotime("now");

        if(empty($this->date_start)){
            $this->date_start = strtotime("first day of this month 00:00:00");
        }else{
            $this->date_start = strtotime(date('01.m.Y 00:00:00',$this->date_start));
        }

        return parent::beforeSave($insert);
    }

    /*public static function getUserSalary($user_id, $month = 0)
    {
        $salary = 0;
        if($month == 0){
            $month = strtotime("now");
        }
        $row = self::find()->select('id,value')
            ->where(['user_id' => $user_id, 'trash' => 0])
            ->andWhere("date_start <= {$month}")
            ->orderBy(['id' => SORT_DESC])
            ->asArray()
            ->one();

        if(!empty($row)){
            $salary = $row['value'];
        }

        return $salary;
    }*/

    public static function getUsersSalary($user_ids,$month = 0)
    {
        $result = [];
        if($month == 0){
            $month = strtotime("now");
        }
        $rows = self::find()->select('id,user_id,value,date_start')
            ->andWhere(['in', 'user_id', $user_ids])
            ->andWhere("date_start <= {$month}")
            ->orderBy(['-`date_start`' => SORT_NUMERIC])
            ->asArray()->all();

        if(!empty($rows)){
            $user_ids = array_unique(array_column($rows,'user_id'));
            foreach ($user_ids as $key => $user_id) {
                $result[] = $rows[$key];
            }
        }
        return $result;
    }

    public static function findSalaryNotesForUsers($user_ids, $period) {
        $rows = UserSalaryNotes::find()
            ->select('user_id, text')
            ->where(['period' => $period])
            ->andWhere(['in', 'user_id', $user_ids])
            ->asArray()
            ->all();
        if(!empty($rows)){
            $rows = ArrayHelper::map($rows,'user_id','text');
        }

        return $rows;
    }

    public static function getTotalSalaryUser()
    {
        $res = [];
        $get = Yii::$app->request->get();
        $first_day = strtotime("first day of this month 00:00:00");
        $end = strtotime("last day of this month 23:59:59");
        if(!empty($get['month']) && !empty($get['year'])){
            $first_day = strtotime($get['month'].'/1/'.$get['year'].' 00:00:00');
            $end = strtotime($get['month'].'/1/'.$get['year'].' 23:59:59 + 1 month -1 day');
        }
        $status_act = User::STATUS_ACT;
        $status_non_act = User::STATUS_NON_ACT;

        $users = User::find()
            ->select('u.id as ID,up.*')
            ->from(User::tableName().' as u')
            ->innerJoin(
                'auth_assignment as a',
                "(a.item_name='{$status_act}' or a.item_name='{$status_non_act}' AND a.created_at>{$first_day}) AND a.user_id=u.id")
            ->leftJoin(
                UserProfile::tableName().' as up',
                "up.user_id=u.id AND up.trash=0")
            ->andWhere("u.created_at <= {$end} ")
            ->orderBy(['u.created_at' => SORT_ASC])
            ->asArray()
            ->all();

        if( !empty($users) ) {
            foreach ($users as $user) {
                $model_user = User::findOne(['id' => $user['ID']]);
                $total = 0;
                $total += $model_user->getSuccessProfit($first_day,$end);
                $total += $model_user->getPremiumsPeriodUser($first_day,$end);
                $salary = self::getUsersSalary([$user['ID']], $end);
                if( !empty($salary[0]['value']) ) {
                    $total += $salary[0]['value'];
                }
                $res[] = [
                    'id' => $user['ID'],
                    'full_name' => $user['full_name'],
                    'total' => $total
                ];
            }
        }
        return $res;
    }

}
