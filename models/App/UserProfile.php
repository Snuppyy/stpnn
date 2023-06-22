<?php

namespace app\models\App;

use phpDocumentor\Reflection\Types\Self_;
use Yii;

/**
 * This is the model class for table "{{%user_profile}}".
 *
 * @property int $id
 * @property int $user_id
 * @property string $full_name
 * @property string $post
 * @property string $avatar
 * @property int $status
 * @property string $phones
 * @property string $emails
 * @property string $skypes
 * @property string $megafone_id
 * @property int $date_birthday
 * @property int $create_at
 * @property int $update_at
 * @property int $trash
 */
class UserProfile extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user_profile}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'full_name', 'status', 'phones', 'emails'], 'required'],
            [['user_id', 'status', 'create_at', 'update_at', 'trash', 'megafon_old', 'megafon_new'], 'integer'],
            [['emails', 'skypes', 'megafone_id'], 'string'],
            [['phones'], 'phonesJson'],
            [['vp'], 'number'],
            [['date_birthday'], 'goIntValue'],
            [['full_name', 'post', 'avatar'], 'string', 'max' => 255],
        ];
    }

    public function goIntValue(){
        if(!empty($this->date_birthday) && !is_numeric($this->date_birthday)){
            $this->date_birthday = strtotime($this->date_birthday);
        }
    }

    public function phonesJson(){
        if(is_array($this->phones)){
            $this->phones = json_encode($this->phones);
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
            'full_name' => Yii::t('app', 'Full Name'),
            'post' => Yii::t('app', 'Post'),
            'avatar' => Yii::t('app', 'Avatar'),
            'status' => Yii::t('app', 'Status'),
            'phones' => Yii::t('app', 'Phones'),
            'emails' => Yii::t('app', 'E-mail'),
            'skypes' => Yii::t('app', 'Skype'),
            'date_birthday' => Yii::t('app', 'Date Birthday'),
            'create_at' => Yii::t('app', 'Create At'),
            'update_at' => Yii::t('app', 'Update At'),
            'trash' => Yii::t('app', 'Trash'),
        ];
    }

    public function beforeSave($insert)
    {
        if($insert == 1)
        {
            $this->create_at = strtotime("now");
        }
        $this->update_at = strtotime("now");
        return parent::beforeSave($insert);
    }

    public static function getFullName($id, $find = 'user_id')
    {
        $name = '';
        $row = self::find()->select('full_name')->where([$find => $id, 'trash' => 0])->asArray()->one();
        if(!empty($row)){
            $name = $row['full_name'];
        }else{
            $name = 'Admin';
        }
        return $name;
    }

    public static function getUsersInMegafon()
    {
        $row = self::find()
            ->select("user_id, full_name, megafone_id")
            ->where("megafone_id<> ''")
            ->andWhere(['trash' => 0])
            ->orderBy(['user_id' => SORT_NUMERIC])
            ->asArray()->all();

        return $row;
    }
}
