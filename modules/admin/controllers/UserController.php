<?php

namespace app\modules\admin\controllers;

use app\components\AppFunctions;
use app\components\SendEmails;
use app\models\App\Client\ClientAccess;
use app\models\App\Request\Requests;
use app\models\App\request\RequestsAccess;
use app\models\App\UserNote;
use app\models\App\UserPremiums;
use app\models\App\UserProfile;
use app\models\App\UserSalary;
use app\models\App\UserVisits;
use app\models\Basic\UploadForm;
use app\modules\admin\Module;
use Yii;
use app\models\User;
use app\modules\admin\models\Search\UserSearch;
use yii\base\Model;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\modules\admin\models\Signup;
use yii\web\UploadedFile;

/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends Controller
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
     * Lists all User models.
     * @return mixed
     */
    public function actionIndex()
    {
        $list_actives = User::findActive();
        $list_non_actives = User::findNonActive();
        $model = new Signup();
        $profile = new UserProfile();
        $salary = new UserSalary();
        return $this->render('index', [
            'list_active' => $list_actives,
            'list_non_actives' => $list_non_actives,
            'model' => $model,
            'profile' => $profile,
            'salary' => $salary
        ]);
    }

    /**
     * Displays a single User model.
     * @param $id
     * @return string
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        $roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->id);
        if(!empty($roles['admin']) ||
            (!empty($roles[User::STATUS_ACT]) && $id == Yii::$app->user->id)){
            $role = Module::getRoleUser(Yii::$app->user->id);
            $model = $this->findModel($id);
            return $this->render($role.'/view', [
                'model' => $model,
                'profile' => $model->profile,
                'salary' => $model->salary,
                'premiums' => UserPremiums::getPremiumsOfUserDate($model->id)
            ]);
        }else{
            throw new ForbiddenHttpException('У вас нет доступа к информации о другом сотруднике');
        }
    }

    /**
     * @return \yii\console\Response|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionUpdate()
    {
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;
        $result = [
            'code' => 'error',
            'msg' => 'Возникла ошибка, обратитесь к администратору сайта'
        ];
        $post = Yii::$app->request->post();
        $id = $post['UserProfile']['id'] ?? false;
        if(!empty($post['UserProfile']['user_id'])){
            unset($post['UserProfile']['user_id']);
        }
        $roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->id);
        if(!empty($roles['admin']) && $id ||
            (!empty($roles[User::STATUS_ACT]))){
                $profile = UserProfile::findOne(['id' => $id, 'trash' => 0]); 
                $salary = UserSalary::findOne(['id' => $id, 'trash' => 0]);                
            if(!empty($profile)){
                if(Yii::$app->user->id != $profile->user_id && empty($roles['admin'])){
                    $res->data = [
                        'code' => 'error',
                        'msg' => 'У вас нет доступа к информации о другом сотруднике'
                    ];
                    return $res;
                }
                $model = $this->findModel($profile->user_id);
                
                $salary = UserSalary::findOne(['user_id' => $profile->user_id, 'trash' => 0]);
                if($salary->date_start !== strtotime(date('01.m.Y 00:00:00', strtotime($post['UserSalary']['date_start'])))){
                    $salary_new = new UserSalary();
                    $salary_new->value = $post['UserSalary']['value'];
                    $salary_new->user_id = $profile->user_id;
                    $salary_new->date_start = $post['UserSalary']['date_start'];
                }

                $profile->load($post);
                $salary->load($post);
                
                if(empty($post['UserProfile']['avatar'])){
                    $profile->avatar = '';
                }
                if($model->email != $profile->emails){
                    $access = User::find()
                        ->where(['email' => $profile->emails])
                        ->andWhere("id <> :id",[':id' => $model->id])->asArray()->one();
                    if(!empty($access)){
                        $result['msg'] = 'Данный E-mail занят другим менеджером';
                    }else{
                        $model->email = $profile->emails;
                        $model->save();
                        $profile->save();
                        $result = [
                            'code' => 'success',
                            'msg' => !empty($roles['admin']) ? 'Успешно обновлен менеджер' : 'Успешно обновили свой профиль'
                        ];
                    }
                } elseif ($profile->save() && $salary->save()){
                    !empty($salary_new) ? $salary_new->save() : '';
                    $result = [
                        'code' => 'success',
                        'msg' => !empty($roles['admin']) ? 'Успешно обновлен менеджер' : 'Успешно обновили свой профиль'
                    ];
                }


            }else{
                $result['msg'] = 'Профиль не найден';
            }

        }else{
            $result['msg'] = 'У вас нет доступа к информации о другом сотруднике';
        }
        $res->data = $result;
        return $res;
    }

    public function actionPass()
    {
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;
        $result = ['code' => 'error', 'msg' => 'Возникла ошибка, обратитесь к администратору сайта'];
        $post = Yii::$app->request->post();
        $user = new User();
        if($user->load($post)){
            $user = User::findOne(['id' => $post['User']['id']]);
            $user->password_hash = Yii::$app->security->generatePasswordHash($post['User']['password']);
            if( $user->save() ){
                $result = [
                    'code' => 'success',
                    'msg' => 'Пароль обновлен у пользователя'
                ];
            }else {
                $result = ['code' => 'error', 'msg' => 'Возникла ошибка, при сохранении менеджера'];
            }
        }
        $res->data = $result;
        return $res;
    }
    /**
     * Deletes an existing User model.
     * @param $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($id)
    {
        if(Module::getRoleUser(Yii::$app->user->id) == 'admin') {
            $this->findModel($id)->delete();
            \Yii::$app->authManager->revokeAll($this->id);
            UserProfile::updateAll(
                ['trash' => 1],
                ['user_id' => $id]
            );
            UserSalary::updateAll(
                ['trash' => 1],
                ['user_id' => $id]
            );
            UserVisits::deleteAll(
                ['user_id' => $id]
            );
            UserNote::deleteAll(
                ['user_id' => $id]
            );
            Yii::$app->session->setFlash('success','Пользователь удален');
        }else{
            Yii::$app->session->setFlash('error','У вас нет прав на это действие');
        }


        return $this->redirect(['index']);
    }

    public function actionDeleteAjax($id)
    {
        $result = [
            'code' => 'error',
            'msg' => ''
        ];
        if(Module::getRoleUser(Yii::$app->user->id) == 'admin'){
            $model = $this->findModel($id);
            \Yii::$app->authManager->revokeAll($this->id);
            UserProfile::updateAll(
                ['trash' => 1],
                ['user_id' => $id]
            );
            UserSalary::updateAll(
                ['trash' => 1],
                ['user_id' => $id]
            );
            UserVisits::deleteAll(
                ['user_id' => $id]
            );
            UserNote::deleteAll(
                ['user_id' => $id]
            );
            $result = [
                'code' => 'success',
                'msg' => 'Пользователь удален'
            ];
            $model->delete();
        }else{
            $result = [
                'code' => 'error',
                'msg' => 'У вас нет прав на это действие'
            ];
        }
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;
        $res->data = $result;
        return $res;
    }

    public function actionSignup()
    {
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;
        $post = Yii::$app->request->post();
        $new_user = new Signup();
        $new_user->load($post);
        $new_user->retypePassword = $post['Signup']['password'] ?? '';
        if($new_user->validate()){
            $new_profile = new UserProfile();
            $new_salary = new UserSalary();
            $new_profile->load($post);
            $new_salary->load($post);
            $new_profile->user_id = $new_salary->user_id = $new_profile->status = -1;
            if(empty($new_salary->date_start)){
                $new_salary->date_start = strtotime('first day of this month 00:00:00');
            }
            $new_profile->emails = $new_user->email;
            if($new_profile->validate() && $new_salary->validate()){
                $mail = new SendEmails(
                    Yii::$app->params['noreplyEmail'],
                    $new_user->email,
                    'Добавлены в CRM как менеджер',
                    "",
                    '<h3>Здравствуйте, '.$new_profile->full_name.'</h3>
                    <p>Вас добавили в систему управления заявками от компании "СпецТехПром НН"</p>
                    <p>Данные для входа:</p>
                    <p>Логин: '.$new_user->username.'</p>
                    <p>Пароль: '.$new_user->password.'</p>
                    <p><a href="'.Yii::$app->urlManager->createAbsoluteUrl('/').'">Страница входа</a></p>'
                );
                $new_user->signup();
                $user = User::findByUsername($new_user->username);
                if($new_user->role == User::STATUS_ACT){
                    $role = Yii::$app->authManager->getRole(User::STATUS_ACT);
                    Yii::$app->authManager->assign($role,$user->id);
                }else{
                    $role = Yii::$app->authManager->getRole(User::STATUS_NON_ACT);
                    Yii::$app->authManager->assign($role,$user->id);
                }
                $new_profile->user_id = $new_salary->user_id = $user->id;
                $new_profile->save();
                $new_salary->save();
                $res->data = [
                    'code' => 'success',
                    'msg' => 'Cоздан новый менеджер',
                    'data' => $new_user
                ];
                return $res;
            }elseif(!empty($new_profile->errors)){
                $res->data = AppFunctions::getTranslate('app',$new_profile->errors);
                $res->setStatusCode('422');
                return $res;
            }elseif(!empty($new_salary->errors)){
                $res->data = AppFunctions::getTranslate('app',$new_salary->errors);
                $res->setStatusCode('422');
                return $res;
            }

        }else{
            $res->data = AppFunctions::getTranslate('app',$new_user->errors);
            $res->setStatusCode('422');
            return $res;
        }

        $res->data = ['code' => 'error', 'msg' => 'Ошибка, обратитесь к администратору'];
        return $res;
    }

    public function actionFileUpload(){
        $res = [];
        if(!empty($_FILES)){
            $model = new UploadForm();
            $files = UploadedFile::getInstances($model,'files');
            foreach ($files as $key => $file) {
                $time = strtotime('now');
                $res[] = '/uploads/avatars/' . $file->baseName.'-'.$time. '.' . $file->extension;
                $res_avatar[] = '/uploads/avatars/' . $file->baseName.'-'.$time. '-150x150.' . $file->extension;
                $name = Yii::getAlias('@app/web'.$res[$key]);
                $file->saveAs($name);
                $name_avatar = Yii::getAlias('@app/web'.$res_avatar[$key]);
                UploadForm::resize_crop_image(150,150,$name,$name_avatar,100);
            }
            return json_encode($res_avatar);
        }
        return false;
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }

    public function actionChangeRole()
    {
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;
        $result = [
            'code' => 'error',
            'msg' => ''
        ];
        $post = Yii::$app->request->post();
        $user_id = Yii::$app->user->id;
        if(Module::getRoleUser($user_id) == 'admin' && !empty($post['id'])){
            $role = Module::getRoleUser($post['id']);

            if($role == User::STATUS_ACT){
                Yii::$app->authManager->revokeAll($post['id']);
                $role = Yii::$app->authManager->getRole(User::STATUS_NON_ACT);
                Yii::$app->authManager->assign($role,$post['id']);

                $request_ids = Requests::find()
                    ->from(Requests::tableName()." as r")
                    ->select('r.id')
                    ->innerJoin(RequestsAccess::tableName()." as ra", "r.id=ra.requests_id")
                    ->where(['ra.user_id' => $post['id'], 'r.trash' => 0, 'r.status_work' => Requests::STATUS_WORK_IN])
                    ->asArray()
                    ->all();

                if(!empty($request_ids)){
                    $request_ids = array_column($request_ids, 'id');
                    foreach ($request_ids as $request_id) {
                        Requests::updateAll(
                            [
                                'status_work' => Requests::STATUS_WORK_REMOVE
                            ],
                            [
                                'id' => $request_id
                            ]
                        );
                    }

                }
                ClientAccess::deleteAll(['user_id' => $post['id']]);
                RequestsAccess::deleteAll(['user_id' => $post['id']]);
                $result = [
                    'code' => 'success',
                    'msg' => 'Успешно изменили статус пользователя'
                ];
            }elseif($role == 'other'){
                Yii::$app->authManager->revokeAll($post['id']);
                $role = Yii::$app->authManager->getRole(User::STATUS_ACT);
                Yii::$app->authManager->assign($role,$post['id']);
                $result = [
                    'code' => 'success',
                    'msg' => 'Успешно изменили статус пользователя'
                ];
            }else{
                $result['msg'] = 'Данному пользователю статус нельзя менять';
            }
        }

        $res->data = $result;
        return $res;
    }
}
