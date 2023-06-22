<?php

namespace app\models\App\Reminder;

use Yii;

/**
 * This is the model class for table "{{%reminder}}".
 *
 * @property int $id
 * @property int $status
 * @property string $title
 * @property string $text
 * @property int $user_id
 * @property int $reminder
 * @property int $create_at
 * @property int $update_at
 * @property int $trash
 */
class Reminder extends \yii\db\ActiveRecord
{
    public $reminder_time;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%reminder}}';
    }

    //Статусы
    const STATUS_ACTIVE = 0;
    const STATUS_ACTIVE_TEXT = 'Активное';

    const STATUS_NOT_ACTIVE = 1;
    const STATUS_NOT_ACTIVE_TEXT = 'Не активное';

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'user_id', 'create_at', 'update_at', 'trash'], 'integer'],
            [['text'], 'string'],
            [['reminder','reminder_time'], 'timeToInteger'],
            [['title','text','reminder'], 'required'],
            [['title'], 'string', 'max' => 255],
        ];
    }

    public function timeToInteger($attribute, $params, $validator)
    {
        if(!is_numeric($this->$attribute)){
            $this->$attribute = strtotime($this->$attribute);
        }
    }

    public function beforeSave($insert)
    {
        if($insert){
            $this->create_at = strtotime("now");
        }
        $this->update_at = strtotime("now");
        $reminder = date('d.m.Y', $this->reminder).' '.date('H:i',$this->reminder_time);
        if(!empty($reminder)){
            $this->reminder = strtotime($reminder);
        }
        return parent::beforeSave($insert);
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
            'text' => Yii::t('app', 'Text'),
            'user_id' => Yii::t('app', 'User ID'),
            'reminder' => Yii::t('app', 'Reminder'),
            'create_at' => Yii::t('app', 'Create At'),
            'update_at' => Yii::t('app', 'Update At'),
            'trash' => Yii::t('app', 'Trash'),
        ];
    }

    public static function getStatuses() {
        return [
            self::STATUS_ACTIVE => self::STATUS_ACTIVE_TEXT,
            self::STATUS_NOT_ACTIVE => self::STATUS_NOT_ACTIVE_TEXT,
        ];
    }

    public static function getStatusText($status = 0)
    {
        $statuses = self::getStatuses();
        return $statuses[$status] ?? '';
    }

    public static function getActiveReminder($user_id = null)
    {
        if(empty($user_id)){
            $user_id = Yii::$app->user->id;
        }

        $rows = self::find()
            ->select('id,title,text,reminder')
            ->andWhere([
                'trash' => 0,
                'status' => self::STATUS_ACTIVE,
                'user_id' => $user_id
            ])
            ->orderBy(['reminder' => SORT_ASC])
            ->asArray()->all();

        return $rows;
    }

    public static function getActiveReminderCount($user_id = null)
    {
        if(empty($user_id)){
            $user_id = Yii::$app->user->id;
        }

        return self::find()
            ->select('id')
            ->andWhere([
                'trash' => 0,
                'status' => self::STATUS_ACTIVE,
                'user_id' => $user_id
            ])
            ->orderBy(['reminder' => SORT_ASC])
            ->count();
    }

    public static function setCookieReminders()
    {
        $cookies = Yii::$app->response->cookies;
        $reminders = Reminder::getActiveReminder();
        if(!empty($reminders)){
            if(!empty($reminders)) {
                $cookies_reminders = array();
                foreach ($reminders as $reminder) {
                    $cookies_reminders[$reminder['reminder']][] = $reminder['id'];
                }

                $cookies->add(new \yii\web\Cookie([
                    'name' => 'reminders',
                    'value' => json_encode($cookies_reminders),
                    'httpOnly' => false
                ]));
            }
        }else{
            $cookies->add(new \yii\web\Cookie([
                'name' => 'reminders',
                'value' => null,
                'httpOnly' => false
            ]));
        }
    }

    public static function setStatusExecuted($id)
    {
        $error = false;
        $reminder = self::findOne(['id' => $id]);
        $reminder->status = self::STATUS_NOT_ACTIVE;
        if($reminder->save())
        {
            $error = true;
            self::setCookieReminders();
        }
        else
        {
            print_r($reminder->getErrors());
        }

        return $error;
    }
}
