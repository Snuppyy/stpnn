<?php

namespace app\modules\admin\models\Search;

use app\modules\admin\Module;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\App\Reminder\Reminder;

/**
 * ReminderSearch represents the model behind the search form of `app\models\App\Reminder\Reminder`.
 */
class ReminderSearch extends Reminder
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'status', 'user_id', 'reminder', 'create_at', 'update_at', 'trash'], 'integer'],
            [['title', 'text'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Reminder::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        
        $dateStart = strtotime($params["ReminderSearch"]["reminder"]);
        $dateEnd = $dateStart + 86399;

        
        $this->load($params);
        
        if (!$this->validate()) {

            if(!is_null($params["ReminderSearch"]["reminder"])) {
                $query->andFilterWhere(['between', 'reminder', $dateStart, $dateEnd]);
            } else {
                $query->andFilterWhere(['reminder' => $this->reminder]);
            }
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
            'user_id' => $this->user_id,
            'create_at' => $this->create_at,
            'update_at' => $this->update_at,
            'trash' => $this->trash,
        ]);
        $user_id = Yii::$app->user->id;
        $role = Module::getRoleUser($user_id);
        if($role != 'admin') {
            $query->andFilterWhere(['user_id' => $user_id]);
        }
        
        

        $query->andFilterWhere(['like', 'title', $this->title])
        ->andFilterWhere(['like', 'text', $this->text]);
        

        return $dataProvider;
    }
}