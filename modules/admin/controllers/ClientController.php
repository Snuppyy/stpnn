<?php

namespace app\modules\admin\controllers;

use app\components\AppFunctions;
use app\models\App\Client\ClientAccess;
use app\models\App\Client\ClientConnection;
use app\models\App\Client\ClientLizing;
use app\models\App\Client\ClientLizingRel;
use app\models\App\Client\ClientOrganization;
use app\models\App\Client\ClientOtherInfo;
use app\models\App\Request\RequestsClientMore;
use app\models\App\Request\RequestsClientMoreRel;
use app\models\App\Request\RequestsConnection;
use app\models\User;
use app\modules\admin\Module;
use app\models\App\Request\Requests;
use Yii;
use app\models\App\Client\Client;
use app\modules\admin\models\Search\ClientSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * ClientController implements the CRUD actions for Client model.
 */
class ClientController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Client models.
     * @return mixed
     */
    public function actionIndex()
    {
        $role = Module::getRoleUser(Yii::$app->user->id);
        $searchModel = new ClientSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $users = User::getListManagers();
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'users' => $users,
            'role' => $role,
        ]);
    }

    /**
     * Lists all Client models.
     * @return mixed
     */
    public function actionLong()
    {
        $role = Module::getRoleUser(Yii::$app->user->id);
        $searchModel = new ClientSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $users = User::getListManagers();
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'users' => $users,
            'role' => $role,
        ]);
    }

    public function actionSetLongType()
    {
        $result = [
            'code' => 'error',
            'msg' => 'Возникла ошибка, обратитесь к администратору сайта'
        ];

        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;

        $post = Yii::$app->request->post();
        if( !empty($post['id']) ) {
            $client = Client::findOne(['id' => $post['id']]);
            if( !empty($client->long) ) {
                $client->long = 0;
            }
            else {
                $client->long = 1;
            }
            if( $client->save() ) {
                $result = [
                    'code' => 'success',
                    'msg' => 'Успешно обновили клиента'
                ];
            }
        }

        $res->data = $result;
        return $res;
    }

    /**
     * Displays a single Client model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {

      
        $model = $this->findModel($id);
        $role = Module::getRoleUser(Yii::$app->user->id);
        if($role == 'admin' || $role == User::STATUS_ACCOUNTANT){
            return $this->render('view', [
                'role' => $role,
                'model' => $model,
                'organization' => $model->organization,
                'otherinfo' => $model->otherinfo,
                'manager' => $model->manager,
                'request_new' => Requests::getListNew($model->id),
                'request_in' => Requests::getListIn($model->id),
                'request_purpose' => Requests::getListPurpose($model->id),
                'request_success' => Requests::getListSuccess($model->id),
                'list_users' => User::getListManagers()
            ]);
        }elseif($role == User::STATUS_ACT){
            return $this->render('view', [
                'role' => $role,
                'model' => $model,
                'organization' => $model->organization,
                'otherinfo' => $model->otherinfo,
                'manager' => $model->manager,
                'request_in' => Requests::getListIn($model->id),
                'request_success' => Requests::getListSuccess($model->id),
            ]);
        }


    }

    /**
     * Creates a new Client model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Client();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Client model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    /*public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }*/

    /**
     * Deletes an existing Client model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    /*public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }*/

    /**
     * Finds the Client model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Client the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $user_id = Yii::$app->user->id;
        $roles = Yii::$app->authManager->getRolesByUser($user_id);
        if($roles[User::STATUS_ACT] || $roles[User::STATUS_NON_ACT]){
            if($roles[User::STATUS_ACT] && ClientAccess::getAccessToClient($id)){
                if (($model = Client::findOne($id)) !== null) {
                    return $model;
                }
            }
        }else{
            if (($model = Client::findOne($id)) !== null) {
                return $model;
            }
        }


        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }

    public function actionUpdateInfo()
    {
        $post = Yii::$app->request->post();
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;

        $result = [
            'code' => 'error',
            'msg' => 'Возникла ошибка, обратитесь к администратору сайта'
        ];
        $client = Client::findOne($post['Client']['id']);
        $client->load($post);
        if($client->save()){
            $result = [
                'code' => 'success',
                'msg' => 'Успешно обновили пользователя'
            ];
        }
        $res->data = $result;
        return $res;
    }

    public function actionUpdateInfoMore()
    {
        $post = Yii::$app->request->post();
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;

        $result = [
            'code' => 'error',
            'msg' => 'Возникла ошибка, обратитесь к администратору сайта'
        ];
        if( empty($post['type']) ) {
            if( empty($post['RequestsClientMore']['id']) ) {
                $model = new RequestsClientMore();
            }
            else {
                $model = RequestsClientMore::findOne(['id' => $post['RequestsClientMore']['id']]);
            }
            if( $model->load($post) && $model->save() ){
                $result = [
                    'code' => 'success',
                    'msg' => empty($post['RequestsClientMore']['id']) ? 'Успешно добавили контакт' : 'Обновили контакт'
                ];
            }else {
                $result['msg'] = $model->errors;
            }
        }
        elseif( $post['type'] == 'delete' ) {
            RequestsClientMore::deleteAll("id = {$post['id']}");
            RequestsClientMoreRel::deleteAll("client_id = {$post['id']}");
            RequestsConnection::deleteAll("client_id = {$post['id']}");

            $result = [
                'code' => 'success',
                'msg' => 'Удален дополнительный контакт'
            ];
        }

        $res->data = $result;
        return $res;
    }

    public function actionSearchOrganization()
    {
        $post = Yii::$app->request->post();
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;
        $name = 'ClientOrganization';
        $result = [
            'code' => 'error',
            'msg' => 'Возникла ошибка, обратитесь к администратору сайта'
        ];

        if(!empty($post[$name]['inn']) && strlen($post[$name]['inn']) >= 5){
            $org = ClientOrganization::findOne(['inn' => $post[$name]['inn']]);
            if(!empty($org)){
                $result = [
                    'code' => 'success',
                    'msg' => '',
                    'org' => $org,
                ];
            }else{
                $result['msg'] = 'Такой организации в списке нет';
            }
        }else{
            $result['msg'] = 'Заполните поле ИНН, не менее 5 цифрами';
        }

        $res->data = $result;
        return $res;
    }
    public function actionSearchLizing()
    {
        $post = Yii::$app->request->post();
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;
        $name = 'ClientLizing';
        $result = [
            'code' => 'error',
            'msg' => 'Возникла ошибка, обратитесь к администратору сайта'
        ];

        if(!empty($post[$name]['inn']) && strlen($post[$name]['inn']) >= 5){
            $org = ClientLizing::findOne(['inn' => $post[$name]['inn']]);
            if(!empty($org)){
                $result = [
                    'code' => 'success',
                    'msg' => '',
                    'org' => $org,
                ];
            }else{
                $result['msg'] = 'Такой организации в списке нет';
            }
        }else{
            $result['msg'] = 'Заполните поле ИНН, не менее 5 цифрами';
        }

        $res->data = $result;
        return $res;
    }

    public function actionSearchClient()
    {
        $role = Module::getRoleUser(Yii::$app->user->id);
        $post = Yii::$app->request->post();
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;
        $name = 'Client';
        $result = [
            'code' => 'error',
            'msg' => 'Возникла ошибка, обратитесь к администратору сайта'
        ];
        $clients = $ids = [];
        if(!empty($post[$name]['phone']) || !empty($post[$name]['email'])){
            $phone = $email = '';
            if(!empty($post[$name]['phone'])){
                if( !is_array($post[$name]['phone']) ) {
                    $phone = AppFunctions::clearPhoneNumber($post[$name]['phone']);
                }
                else {
                    $phone = $post[$name]['phone'];
                }
            }
            if(!empty($post[$name]['email'])){
                if( !is_array($post[$name]['email']) ) {
                    $email = AppFunctions::clearField($post[$name]['email']);
                }
                else{
                    $email = $post[$name]['email'];
                }
            }
            $ids = Client::findClientIDConnection($phone,$email,false);
            if(!empty($ids)){
                $clients = Client::find()->where(['in','id', $ids])->andWhere(['trash' => 0])->all();
                $managers = [];

                /** @var  $client Client */
                foreach ($clients as $key => $client) {
                    $result['clients'][$key] = $client->attributes;
                    $org = $client->organization;
                    $result['clients'][$key]['org']['title'] = !empty($org->title) ? $org->title : '';
                    //$connections = $client->connection;
                    /** @var $connection ClientConnection */
                    /*if(!empty($connections)){
                        foreach ($connections as $connection) {
                            $result['clients'][$key]['connection'][] = $connection->value;
                        }
                    }*/
                    $result['clients'][$key]['access'] = ClientAccess::getAccessToClient($result['clients'][$key]['id']);
                    $_manager = $client->manager;
                    if(empty($_manager)){
                        $result['clients'][$key]['access'] = true;
                    }
                    $managers = array_merge($managers,$_manager);
                }
                if(!empty($managers)){
                    $managers = array_column($managers,'full_name');
                }
                $result['code'] = 'success';
                $result['msg'] = 'Закройте окно и выберите клиента';
                $result['managers'] = implode('<br/>', array_unique($managers));
            }else{
                $result = [
                    'code' => 'success',
                    'msg' => 'Клиент не найден',
                    'clients' => []
                ];
            }
        }else{
            $result['msg'] = 'Заполните поля поиска: телефон/e-mail';
        }

        $res->data = $result;
        return $res;
    }

    public function actionSaveOrganization()
    {
        $post = Yii::$app->request->post();
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;
        $name = 'ClientOrganization';
        $result = [
            'code' => 'error',
            'msg' => 'Возникла ошибка, обратитесь к администратору сайта'
        ];

        if(!empty($post[$name]['id'])){
            $clientorganization = ClientOrganization::findOne(['id' => $post[$name]['id']]);
        }else{
            $clientorganization = new ClientOrganization();
        }

        if($clientorganization->load($post) && $clientorganization->save()){
            $result = [
                'code' => 'success',
                'msg' => 'Успешно сохранили информацию об организации'
            ];
            $client = $clientorganization->getClient();
            /** @var $client Client */
            if(!empty($client)){
                $client->role = 1;
                $client->save();
            }
        }else{
            $result['msg'] = $clientorganization->errors;
        }

        $res->data = $result;
        return $res;
    }

    public function actionSaveLizing()
    {
        $post = Yii::$app->request->post();
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;
        $name = 'ClientLizing';
        $result = [
            'code' => 'error',
            'msg' => 'Возникла ошибка, обратитесь к администратору сайта'
        ];
        if(!empty($post[$name]['id'])){
            $clientorganization = ClientLizing::findOne(['id' => $post[$name]['id']]);
        }else{
            $clientorganization = new ClientLizing();
        }

        if($clientorganization->load($post) && $clientorganization->save()){
            $result = [
                'code' => 'success',
                'msg' => 'Успешно сохранили информацию об организации'
            ];
            $client = $clientorganization->getClient();
            /** @var $client Client */
            if(!empty($client)){
                $client->role = 1;
                $client->save();
            }
        }else{
            $result['msg'] = $clientorganization->errors;
        }

        $res->data = $result;
        return $res;
    }

    public function actionTrashLizingRequest() {
        $post = Yii::$app->request->post();
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;
        $result = [
            'code' => 'error',
            'msg' => 'Возникла ошибка, обратитесь к администратору сайта'
        ];
        if(!empty($post['id'])){
            ClientLizingRel::deleteAll([
                'request_id' => $post['id']
            ]);
            $result = [
                'code' => 'success',
                'msg' => 'Успешно удалили связь'
            ];
        }

        $res->data = $result;
        return $res;
    }

    /**
     * @return \yii\console\Response|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionAddOtherInfo()
    {
        $post = Yii::$app->request->post();
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;
        $result = [
            'code' => 'error',
            'msg' => 'Возникла ошибка, обратитесь к администратору сайта'
        ];

        $other_info = new ClientOtherInfo();
        if($other_info->load($post) && $this->findModel($other_info->client_id)){
            if($other_info->save()){
                $result = [
                    'code' => 'success',
                    'msg' => 'Добавили дополнительную информацию'
                ];
            }
        }
        $res->data = $result;
        return $res;
    }


    public function actionUpdateOtherInfo()
    {
        $post = Yii::$app->request->post();
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;
        $result = [
            'code' => 'error',
            'msg' => 'Возникла ошибка, обратитесь к администратору сайта'
        ];

        $other_info = ClientOtherInfo::findOne(['id' => $post['ClientOtherInfo']['id']]);
        if(!empty($other_info)){
            $other_info->load($post);
            if(empty($other_info->value)){
                $other_info->delete();
                $result = [
                    'code' => 'success',
                    'msg' => 'Удалили дополнительную информацию, из за того что передали пустое значение'
                ];
            }elseif($other_info->save()){
                $result = [
                    'code' => 'success',
                    'msg' => 'Обновили дополнительную информацию'
                ];
            }
        }

        $res->data = $result;
        return $res;
    }

    public function actionDelete()
    {
        $user_id = Yii::$app->user->id;
        $role = Module::getRoleUser($user_id);
        $post = Yii::$app->request->post();
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;
        $result = [
            'code' => 'error',
            'msg' => 'Возникла ошибка, обратитесь к администратору сайта'
        ];
        if(!empty($post['id']) && ($role == 'admin' || $role == User::STATUS_ACCOUNTANT)){
            $client = Client::findOne(['id' => $post['id']]);

            if($client->toTrash()){
                $result = [
                    'code' => 'success',
                    'msg' => 'Клиент успешно удален'
                ];
            }

        }else{
            $result['msg'] = 'У вас не прав на данное действие';
        }
        $res->data = $result;
        return $res;
    }

    public function actionGetInfoForAddRequest()
    {
        $post = Yii::$app->request->post();
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;
        $result = [
            'code' => 'error',
            'msg' => 'Возникла ошибка, обратитесь к администратору сайта'
        ];

        if(!empty($post['id'])){
            $client = Client::findOne(['id' => $post['id']]);
            $result['client']['id'] = $client->id;
            $result['client']['firstname'] = $client->firstname;
            $connect = $client->connection;
            if(!empty($connect)){
                foreach ($connect as $item) {
                    if(empty($result['client'][$item->field])){
                        if($item->field == 'phone' && (
                            8 == mb_substr($item->value,0,1) ||
                            7 == mb_substr($item->value,0,1)
                            )){
                            $val = mb_substr($item->value,1);
                        }else{
                            $val = $item->value;
                        }
                        $result['client'][$item->field] = $val;
                    }
                }
            }
            $result['code'] = 'success';
            $result['msg'] = '';
        }else{
            $result['msg'] = 'У вас не прав на данное действие';
        }

        $res->data = $result;
        return $res;
    }

    public function actionCopyEmails(){
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;
        $result = [
            'code' => 'error',
            'msg' => 'Возникла ошибка, обратитесь к администратору сайта'
        ];
        $user_id = Yii::$app->user->id;
        $user_roles = Yii::$app->authManager->getRolesByUser($user_id);

        if(!empty($user_roles[User::STATUS_ACT])){
            $client_ids =  Client::find()
                ->from(Client::tableName()." as c")
                ->select('c.id')
                ->innerJoin(ClientAccess::tableName()." as ca",
                    "ca.client_id=c.id and ca.user_id={$user_id} and ca.trash=0")
                ->asArray()->all();
        }else{
            $post = Yii::$app->request->post();
            if( !empty($post['user']) && $post['user'] > 0 ) {
                $client_ids =  Client::find()
                    ->from(Client::tableName()." as c")
                    ->select('c.id')
                    ->innerJoin(ClientAccess::tableName()." as ca",
                        "ca.client_id=c.id and ca.user_id={$post['user']} and ca.trash=0")
                    ->asArray()->all();
            }
            else {
                $client_ids =  Client::find()
                    ->from(Client::tableName()." as c")
                    ->select('c.id')
                    ->asArray()->all();
            }
        }
        if(!empty($client_ids)){
            $client_ids = array_column($client_ids,'id');
            $emails = ClientConnection::find()->select('value')
                ->where(['in', 'client_id', $client_ids])
                ->andWhere(['field' => 'email','trash' => 0])
                ->orderBy(['value' => SORT_ASC])
                ->groupBy(['value'])
                ->asArray()->all();

            $emails_lizing = ClientLizing::find()
                ->from(ClientLizing::tableName()." as cl")
                ->select('cl.email')
                ->innerJoin(ClientLizingRel::tableName()." as clr", "clr.element_id=cl.id")
                ->where(['in','clr.client_id', $client_ids])
                ->asArray()
                ->all();
            if(!empty($emails)){
                $emails = implode(',', array_column($emails,'value'));
                if(!empty($emails_lizing)){
                    $emails = $emails . ',' . implode(',', array_column($emails_lizing,'email'));
                }
                $result = [
                    'code' => 'success',
                    'emails' => $emails
                ];
            }else{
                $result['msg'] = 'У доступных клиентов не заполненны E-mail';
            }
        }else{
            $result['msg'] = 'Клиентов не найдено';
        }

        $res->data = $result;
        return $res;
    }

    public function actionCopyPhones(){
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;
        $result = [
            'code' => 'error',
            'msg' => 'Возникла ошибка, обратитесь к администратору сайта'
        ];
        $user_id = Yii::$app->user->id;
        $user_roles = Yii::$app->authManager->getRolesByUser($user_id);

        if(!empty($user_roles[User::STATUS_ACT])){
            $client_ids =  Client::find()
                ->from(Client::tableName()." as c")
                ->select('c.id')
                ->innerJoin(ClientAccess::tableName()." as ca",
                    "ca.client_id=c.id and ca.user_id={$user_id} and ca.trash=0")
                ->asArray()->all();
        }else{
            $post = Yii::$app->request->post();
            if( !empty($post['user']) && $post['user'] > 0 ) {
                $client_ids =  Client::find()
                    ->from(Client::tableName()." as c")
                    ->select('c.id')
                    ->innerJoin(ClientAccess::tableName()." as ca",
                        "ca.client_id=c.id and ca.user_id={$post['user']} and ca.trash=0")
                    ->asArray()->all();
            }
            else {
                $client_ids =  Client::find()
                    ->from(Client::tableName()." as c")
                    ->select('c.id')
                    ->asArray()->all();
            }
        }
        if(!empty($client_ids)){
            $client_ids = array_column($client_ids,'id');
            $phones = ClientConnection::find()->select('value')
                ->where(['in', 'client_id', $client_ids])
                ->andWhere(['field' => 'phone','trash' => 0])
                ->orderBy(['value' => SORT_ASC])
                ->groupBy(['value'])
                ->asArray()->all();
            $phones_lizing = ClientLizing::find()
                ->from(ClientLizing::tableName()." as cl")
                ->select('cl.phone')
                ->innerJoin(ClientLizingRel::tableName()." as clr", "clr.element_id=cl.id")
                ->where(['in','clr.client_id', $client_ids])
                ->asArray()
                ->all();
            if(!empty($phones)){
                $phones = implode(',', array_column($phones,'value'));
                if(!empty($phones_lizing)){
                    $phones = $phones . ',' . implode(',', array_column($phones_lizing,'phone'));
                }
                $result = [
                    'code' => 'success',
                    'emails' => $phones
                ];
            }else{
                $result['msg'] = 'У доступных клиентов не заполненны номера телефонов';
            }
        }else{
            $result['msg'] = 'Клиентов не найдено';
        }

        $res->data = $result;
        return $res;
    }

}