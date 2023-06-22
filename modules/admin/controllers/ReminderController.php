<?php

namespace app\modules\admin\controllers;

use app\models\App\Request\Requests;
use app\models\App\UserNotifications;
use Yii;
use app\models\App\Reminder\Reminder;
use app\modules\admin\models\Search\ReminderSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * ReminderController implements the CRUD actions for Reminder model.
 */
class ReminderController extends Controller
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
     * Lists all Reminder models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ReminderSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $cookies = Yii::$app->request->cookies;
        $servertime = $cookies->getValue('reminders');
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionLoadIds()
    {
        $post = Yii::$app->request->post();
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;

        $result = [
            'code' => 'error',
            'msg' => 'Ошибка, обратитесь к администратору сайта'
        ];

        if(!empty($post['ids'])){
            $reminders = [];
            foreach ($post['ids'] as $id) {
                $reminders[] = Reminder::find()
                    ->select('id,status,title,text,reminder')
                    ->where(['id' => $id])
                    ->asArray()->one();
            }
            
            $result = [
                'code' => 'success',
                'reminders' => $reminders
            ];
        }

        $res->data = $result;
        return $res;
    }

    public function actionLoadNotificationIds2() {
        $id = 172;
        $reminders = UserNotifications::find()
            ->select('un.id,un.status,un.message as text,un.date,un.request_id,r.title')
            ->from(UserNotifications::tableName()." as un")
            ->innerJoin(Requests::tableName()." as r", "un.request_id=r.id")
            ->where(['un.id' => $id])
            ->asArray()->one();
        print_r($reminders);die;
    }
    public function actionLoadNotificationIds()
    {
        $post = Yii::$app->request->post();
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;

        $result = [
            'code' => 'error',
            'msg' => 'Ошибка, обратитесь к администратору сайта'
        ];

        if(!empty($post['ids'])){
            $reminders = [];
            foreach ($post['ids'] as $key => $id) {
                $reminders[] = UserNotifications::find()
                    ->select('un.id,un.status,un.message as text,un.date,un.request_id,r.title')
                    ->from(UserNotifications::tableName()." as un")
                    ->innerJoin(Requests::tableName()." as r", "un.request_id=r.id")
                    ->where(['un.id' => $id])
                    ->asArray()->one();
                $reminders[$key]['title'] .= ' <a class="text-white" href="/request/view?id='.$reminders[$key]['request_id'].'"><i class="icon-link"></i></a>';
            }

            $result = [
                'code' => 'success',
                'notifications' => $reminders
            ];
        }

        $res->data = $result;
        return $res;
    }

    public function actionSetStatusExecuted()
    {
        $post = Yii::$app->request->post();
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;

        $result = [
            'code' => 'error',
            'msg' => 'Ошибка, обратитесь к администратору сайта'
        ];

        if(!empty($post['id']) && Reminder::setStatusExecuted($post['id']))
        {
            Yii::$app->session->addFlash('success','Успешно сохранено напоминание');
            if($_SERVER['HTTP_REFERER'])
            {
                return $this->redirect($_SERVER['HTTP_REFERER']);
            }
            $result = [
                'code' => 'success',
                'msg' => ''
            ];
        }

        $res->data = $result;
        return $res;
    }

    public function actionSetStatusExecutedNotifications()
    {
        $post = Yii::$app->request->post();
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;

        $result = [
            'code' => 'error',
            'msg' => 'Ошибка, обратитесь к администратору сайта'
        ];
        /*$post['id'] = 172;
        var_dump(UserNotifications::setStatusExecuted($post['id']));die;*/
        if(!empty($post['id']) && UserNotifications::setStatusExecuted($post['id'], $post['action']))
        {
            Yii::$app->session->addFlash('success','Успешно сохранено напоминание');
            if($_SERVER['HTTP_REFERER'])
            {
                return $this->redirect($_SERVER['HTTP_REFERER']);
            }
            $result = [
                'code' => 'success',
                'msg' => ''
            ];
        }

        $res->data = $result;
        return $res;
    }

    /**
     * Displays a single Reminder model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    /*public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    } */

    /**
     * Creates a new Reminder model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Reminder();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->addFlash('success','Успешно сохранено напоминание');
            Reminder::setCookieReminders();
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Reminder model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->addFlash('success','Успешно сохранено напоминание');
            Reminder::setCookieReminders();
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Reminder model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Reminder::setCookieReminders();
        return $this->redirect(['index']);
    }

    /**
     * Finds the Reminder model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Reminder the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Reminder::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
