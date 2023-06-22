<?php

namespace app\modules\admin\controllers;

use app\components\AppFunctions;
use app\components\SendEmails;
use app\components\SendWhatsAppMess;
use app\components\SmsDev;
use app\models\App\Client\Client;
use app\models\App\Client\ClientAccess;
use app\models\App\Request\RequestsAccess;
use app\models\App\Request\RequestsBilling;
use app\models\App\Request\RequestsCalculator;
use app\models\App\Request\RequestsCalculatorItem;
use app\models\App\Request\RequestsCalculatorItemFiles;
use app\models\App\Request\RequestsDocs;
use app\models\App\Request\RequestsPayment;
use app\models\App\Request\RequestsPaymentPrognosis;
use app\models\App\Request\RequestsPrognosis;
use app\models\App\Request\RequestsProgress;
use app\models\App\Request\RequestsPurpose;
use app\models\App\Request\RequestsUpd;
use app\models\App\RequestsPost;
use app\models\App\UserProfile;
use app\models\Basic\Dictionary;
use app\models\User;
use app\modules\admin\Module;
use Yii;
use app\models\App\Request\Requests;
use app\modules\admin\models\Search\RequestSearch;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \Mpdf\Mpdf;

/**
 * RequestController implements the CRUD actions for Requests model.
 */
class RequestController extends Controller
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
   * Displays a single Requests model.
   * @param $id
   * @return string
   * @throws ForbiddenHttpException
   * @throws NotFoundHttpException
   */
  public function actionView($id)
  {
    $user_id = Yii::$app->user->id;
    $role = Module::getRoleUser($user_id);
    /** @var $model Requests */
    $model = $this->findModel($id);
    $upd = $model->upd;
    if (empty($upd)) {
      $upd = new RequestsUpd();
    }
    $progress = new RequestsProgress();
    $docs = new RequestsDocs();
    return $this->render($role . '/view', [
      'model' => $model,
      'progress' => $progress,
      'modeldocs' => $docs,
      'loadItemDocs' => new RequestsCalculatorItemFiles(),
      'data' => !empty($model) ? $model->getDataViewInfo($role) : '',
      'upd' => $upd
    ]);
  }

  /**
   * Creates a new Requests model.
   * If creation is successful, the browser will be redirected to the 'view' page.
   * @return mixed
   */
  public function actionCreate()
  {
    $post = Yii::$app->request->post();

    if (Yii::$app->request->isAjax) {
      $result = [
        'code' => 'error',
        'msg' => ''
      ];
      $res = \Yii::$app->getResponse();

      $res->format = \yii\web\Response::FORMAT_JSON;

      if (empty($post['Client']['id'])) {
        $client_ids = Client::findClientIDConnection(
          $post['Client']['phone'],
          $post['Client']['email'] ?? ''
        );
      } else {
        $client_ids = Client::find()->where([
          'id' => $post['Client']['id']
        ])->all();
      }
      $access = true;
      $send_sms = false;
      if ($client_ids != false) { //нашли клиента
        if (!empty($client_ids[0]->id)) {
          $client = $client_ids[0];
        } else {
          $client = Client::findOne(['id' => $client_ids[0]]);
        }

        $client->load($post);
        $access = ClientAccess::getAccessToClient($client->id);
        if (empty($client->manager)) {
          $access = true;
        }
      } else { // не нашли
        $client = new Client();
        $client->load($post);
        $send_sms = true;
      }

      if ($access) {
        if ($client->save()) {

          $request = new Requests();
          $request_post = new RequestsPost();
          $request->client_id = $client->id;
          $request->user_id = Yii::$app->user->id;
          $request->status = $post['Client']['request']['status'];

          /*
           * Загрзука файла
           */
          if (!empty($_FILES['file'])) {
            $time = strtotime("now");
            $url = '/uploads/request/';
            $uploaddir = $_SERVER['DOCUMENT_ROOT'] . $url;
            $uploadfile = $uploaddir . $time . basename($_FILES['file']['name']);
            $url .= $time . basename($_FILES['file']['name']);
            move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile);
            $request->file = $url;
          }

          if (Module::getRoleUser(Yii::$app->user->id) != 'admin') {
            $request->status_work = Requests::STATUS_WORK_IN;
            $request->save();
            $request_access = new RequestsAccess();
            $request_access->requests_id = $request->id;
            $request_access->user_id = Yii::$app->user->id;
            $request_access->save();
            $prev_access = ClientAccess::findOne([
              'client_id' => $client->id,
              'user_id' => Yii::$app->user->id,
              'trash' => 0
            ]);
            if (!empty($prev_access)) {
              $send_sms = false;
            } else {
              $send_sms = true;
            }
            $client_access = new ClientAccess();
            $client_access->client_id = $client->id;
            $client_access->user_id = Yii::$app->user->id;
            $client_access->date_end = strtotime("+1 year");
            $client_access->save();

            $request_purpose = new RequestsPurpose();
            $request_purpose->requests_id = $request->id;
            $request_purpose->user_id = Yii::$app->user->id;
            $request_purpose->save();
            $sms = new SmsDev(Yii::$app->params['smslogin'], Yii::$app->params['smspassword']);
            $client = Client::findOne(['id' => $request->client_id, 'trash' => 0]);
            $profile = UserProfile::findOne(['user_id' => Yii::$app->user->id, 'trash' => 0]);
            if ($sms->getBalance() > 0 && $send_sms) {
              $phones = $client->getConnection('phone')->all();
              $m_phones = !empty($profile->phones) ? json_decode($profile->phones, true) : '';
              if (!empty($profile) && !empty($phones) && !empty($m_phones)) {
                $msg = "Компания \"СпецТехПром\"\n(Производство спецавтомобилей)\nВаш персональный менеджер\n"
                  . trim($profile->full_name)
                  . "\nтел." . preg_replace('/[\s-]*/im', '', $m_phones[0])
                  . "\nE-mail:{$profile->emails}"
                  . "\nСайт:www.stpnn.ru";
                foreach ($phones as $phone) {
                  $sms->sendSms(
                    $msg,
                    preg_replace(
                      '/[^\d]*/im',
                      '',
                      $phone->value
                    ),
                    Yii::$app->params['smsname']
                  );
                }
              }
            }

            $phoneUrl = str_replace([' ', '(', ')', '-', '"]', '["'], '', $profile->phones);
            $phoneText = str_replace(['"]', '["'], '', $profile->phones);

            $phones = $client->getConnection('phone')->all();
            if (!empty($phones)) {
              $card = "http://crm.stpnn.ru/" . $profile->card;
              foreach ($phones as $phone) {
                new SendWhatsAppMess(
                  preg_replace('/[^\d]*/im', '', $phone->value),
                  $client->firstname,
                  trim($profile->full_name),
                  $card,
                  $phoneUrl
                );
              }
            }


            $emails = $client->getConnection('email')->all();
            if (!empty($emails)) {
              $profile = UserProfile::findOne(['user_id' => Yii::$app->user->id, 'trash' => 0]);
              $emails = array_column($emails, 'value');
              $_u_phones = !empty($profile->phones) ? json_decode($profile->phones, true) : '';
              $out_phones = '';
              if ($_u_phones) {
                foreach ($_u_phones as $key => $u_phone) {
                  if ($key > 0)
                    $out_phones .= ', ';
                  $out_phones .= '<a href="tel:' . $u_phone . '">' . $u_phone . '</a>';
                }
              }

              $mail = new SendEmails(
                Yii::$app->params['noreplyEmail'],
                $emails,
                'STPNN - Назначен менеджер',
                '',
                '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
                              <html lang="ru">

                              <head>
                                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                                <meta name="viewport" content="width=device-width, initial-scale=1">
                                <meta http-equiv="X-UA-Compatible" content="IE=edge">

                                <title></title>

                                <style type="text/css">
                                  @import url(\'https://fonts.cdnfonts.com/css/gilroy-bold\');

                                  * {
                                    font-family: \'Gilroy-Light\', sans-serif;
                                    font-size: 24px;
                                    line-height: 44px;
                                  }

                                  a {
                                    color: #000 !important;
                                    text-decoration: none !important;
                                  }

                                  i {
                                    padding-left: 60px;
                                  }

                                  tr,
                                  td {
                                    margin: 0;
                                    padding: 0;
                                  }

                                  .hidden {
                                    display: none;
                                  }

                                  @media only screen and (max-width: 1000px) {
                                    table {
                                      zoom: 0.8;
                                      -moz-transform: scale(0.8);
                                    }
                                  }

                                  @media only screen and (max-width: 600px) {
                                    table {
                                      zoom: 0.5;
                                      -moz-transform: scale(0.5);
                                    }
                                  }

                                  @media only screen and (max-width: 400px) {
                                    table {
                                      zoom: 0.3;
                                      -moz-transform: scale(0.3);
                                    }
                                  }

                                  @media screen and (device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3) {
                                    table {
                                      zoom: 0.2;
                                    }
                                  }
                                </style>
                              </head>

                              <body style="margin:0; padding:0; background-color:#F2F2F2; padding-bottom: 50px;">
                                <center>
                                <h2 style="padding-top: 50px;">Здравствуйте!<br><br>Вам назначен Ваш персональный менеджер: <span
                                            style="font-family: \'gilroy-bold\', sans-serif; color: #FF9028;">' . $profile->full_name . '</span></h2>
                                  <table height="614px" width="1087px"
                                    style="background-color: #FFF; background-image: url(\'http://crm.stpnn.ru/crm/img/email/background_' . $profile->sex . '.png\'); background-repeat: no-repeat; background-size: contain; font-size: 31px;"
                                    cellpadding="0" cellspacing="0" border="0" class="wrapper">
                                    <colgroup>
                                      <col span="1" style="width: 20%;">
                                      <col span="1" style="width: 20%;">
                                      <col span="1" style="width: 20%;">
                                      <col span="1" style="width: 25%;">
                                      <col span="1" style="width: 15%;">
                                    </colgroup>
                                    <tbody>
                                      <tr align="bottom">
                                        <td>&nbsp;</td>
                                        <td colspan="4" style="font-size: 82px; padding-top: 30px;"><b><span
                                            style="font-family: \'gilroy-bold\', sans-serif; color: #FF9028; font-size: 82px; line-height: 80px; padding-left: 65px;">Спец</span></b>ТехПром
                                        </td>
                                      </tr>
                                      <tr align="top">
                                        <td>&nbsp;</td>
                                        <td colspan="4" style="font-size: 20px; line-height: 20px; padding-left: 70px;">Производство и продажа
                                          автомобилей специального
                                          назначения</td>
                                      </tr>
                                      <tr>
                                        <td rowspan="3" colspan="2" style="vertical-align: middle;">
                                          <img width="200" height="200"
                                            src="http://crm.stpnn.ru/' . $profile->avatar . '"
                                            style="border-radius: 50%; border: 3px solid #FFF; margin-left: 50px;">
                                        </td>
                                        <td colspan="3" style="font-size: 50px; font-family: \'Gilroy-Bold\', sans-serif;">' . $profile->full_name . '</td>
                                      </tr>
                                      <tr>
                                        <td colspan="3" style="font-size: 34px; line-height: 44px; font-weight: 600;">
                                        ' . $profile->post . '
                                          <hr
                                            style="border: none; color: #000; background-color: #000; height: 2px; width: 490px; text-align: left; margin-left: 0">
                                        </td>
                                      </tr>
                                      <tr>
                                        <td colspan="3"><img src="http://crm.stpnn.ru/crm/img/email/phone.png"
                                            style="vertical-align: middle; margin-right: 10px;"><a href="tel:' . $phoneUrl . '">' . $phoneText . '</a></td>
                                      </tr>
                                      <tr>
                                        <td colspan="2" style="vertical-align: bottom;"><img src="http://crm.stpnn.ru/crm/img/email/wa.png"
                                            style="vertical-align: middle; margin-right: 15px; margin-left: 20px;"><a
                                            style="color: #fff !important; font-weight: 900; font-size: 22px; line-height: 30px;" href="tel:' . $phoneUrl . '">' . $phoneText . '</a></td>
                                        <td colspan="1"><img src="http://crm.stpnn.ru/crm/img/email/email.png"
                                            style="vertical-align: middle; margin-right: 10px;"><a href="mailto:' . $profile->emails . '">' . $profile->emails . '</a></td>
                                        <td colspan="1"><img src="http://crm.stpnn.ru/crm/img/email/url.png"
                                            style="vertical-align: middle; margin-right: 10px;"><a href="https://www.stpnn.ru">www.stpnn.ru</a></td>
                                        <td>&nbsp;</td>
                                      </tr>
                                      <tr>
                                        <td colspan="2" style="color: #FFF; font-size: 16px;"><i>WhatsApp</i></td>
                                        <td colspan="3"><img src="http://crm.stpnn.ru/crm/img/email/home.png"
                                            style="vertical-align: middle; margin-right: 10px;">г.
                                          Н.Новгород, ул.
                                          Новикова-Прибоя, д. 6А</td>
                                      </tr>
                                      <tr>
                                        <td colspan="2" style="color: #FFF; font-size: 16px;"><i>Telegram</i></td>
                                        <td colspan="3">&nbsp;</td>
                                      </tr>
                                      <tr>
                                        <td colspan="2" style="color: #FFF; font-size: 16px;"><i>Viber</i></td>
                                        <td colspan="3">&nbsp;</td>
                                      </tr>
                                      <tr>
                                        <td colspan="4" align="right" style="font-size: 34px; line-height: 0px;">Мы в соц. сетях:</td>
                                        <td><a style="margin-left: 20px;" href="https://www.youtube.com/channel/UC9s2plYeFgW2Ff0Izy8r-Og"><img src="http://crm.stpnn.ru/crm/img/email/yt.png"></a><a
                                            href="https://vk.com/stpnnspectehnika"><img src="http://crm.stpnn.ru/crm/img/email/vk.png"></a><a href="https://www.instagram.com/stp_nn/"><img
                                              src="http://crm.stpnn.ru/crm/img/email/is.png"></a></td>
                                      </tr>
                                      <tr class="hidden">
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                      </tr>
                                    </tbody>
                                  </table>
                                </center>
                              </body>

                              </html>'
              );
            }
          } else {
            $request->save();
          }
          $request_post->requests_id = $request->id;
          $request_post->data = json_encode([
            'your-name' => $client->firstname,
            'your-phone' => AppFunctions::clearPhoneNumber($post['Client']['phone']),
            'email-344' => !empty($post['Client']['email']) ? AppFunctions::clearField($post['Client']['email']) : '',
            'text-399' => $client->msg,
          ]);

          if ($request_post->save()) {
            $result = [
              'code' => 'success',
              'msg' => 'Усешно создана новая заявка'
            ];
          }
        } else {
          $result = [
            'code' => 'error',
            'msg' => 'Не верное заполнен клиент'
          ];
        }
      } else {
        $result['msg'] = 'У Вас нет допуска к клиенту';
      }

      $res->data = $result;
      return $res;
    } else {
      die;
      $model = new Requests();

      if ($model->load(Yii::$app->request->post()) && $model->save()) {
        return $this->redirect(['view', 'id' => $model->id]);
      }

      return $this->render('create', [
        'model' => $model,
      ]);
    }
  }

  /**
   * Updates an existing Requests model.
   * If update is successful, the browser will be redirected to the 'view' page.
   * @param $id
   * @return string|\yii\web\Response
   * @throws ForbiddenHttpException
   * @throws NotFoundHttpException
   */
  public function actionUpdate($id)
  {
    $model = $this->findModel($id);

    if ($model->load(Yii::$app->request->post()) && $model->save()) {
      return $this->redirect(['view', 'id' => $model->id]);
    }

    return $this->render('update', [
      'model' => $model,
    ]);
  }

  /**
   * Deletes an existing Requests model.
   * If deletion is successful, the browser will be redirected to the 'index' page.
   * @param $id
   * @return \yii\web\Response
   * @throws ForbiddenHttpException
   * @throws NotFoundHttpException
   */
  public function actionDelete($id)
  {
    if (Module::getRoleUser(Yii::$app->user->id) == 'admin') {
      $model = $this->findModel($id);
      $model->trash = 1;
      $model->save();
      $this->deleteDataInfo($id);
      Yii::$app->session->setFlash('success', 'Заявка удалена');
    } else {
      Yii::$app->session->setFlash('error', 'У вас нет прав на это действие');
    }

    return $this->goHome();
  }

  /**
   * @param $id
   * @return \yii\console\Response|\yii\web\Response
   * @throws ForbiddenHttpException
   * @throws NotFoundHttpException
   */
  public function actionDeleteAjax($id)
  {
    $result = [
      'code' => 'error',
      'msg' => ''
    ];
    if (
      Module::getRoleUser(Yii::$app->user->id) == 'admin' ||
      Module::getRoleUser(Yii::$app->user->id) == User::STATUS_ACCOUNTANT
    ) {
      $model = $this->findModel($id);
      $model->trash = 1;
      $model->save();
      $this->deleteDataInfo($id);
      $result = [
        'code' => 'success',
        'msg' => 'Заявка удалена'
      ];
    } else {
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

  /**
   * @param $id
   */
  protected function deleteDataInfo($id)
  {
    RequestsAccess::toTrash($id, 'requests_id');
    RequestsCalculator::toTrash($id, 'requests_id');
    RequestsPayment::toTrash($id, 'requests_id');
    RequestsProgress::toTrash($id, 'requests_id');
    RequestsPurpose::toTrash($id, 'requests_id');
  }

  /**
   * @return \yii\console\Response|\yii\web\Response
   */
  public static function actionStartPurpose()
  {
    $res = \Yii::$app->getResponse();
    $res->format = \yii\web\Response::FORMAT_JSON;

    $result = [
      'code' => 'error',
      'msg' => 'Ошибка, обратитесь к администратору сайта'
    ];

    $post = Yii::$app->request->post();

    if (!empty($post['user_id']) && !empty($post['req_id']) && Requests::startPurpose($post['user_id'], $post['req_id'])) {
      $result = [
        'code' => 'success',
        'msg' => 'Менеджеру успешно направлена выбранная заявка'
      ];
    }

    $res->data = $result;
    return $res;
  }

  public function actionRemove()
  {
    return $this->render('admin/remove', [
      'request_new' => Requests::getListRemove(),
      //'request_in' => Requests::getListIn(),
      //'request_success' => Requests::getListSuccess(),
      'list_users' => User::getListManagers(),
      'modelclient' => new \app\models\App\Client\Client()
    ]);
  }

  public function actionRemoveExport($user_id = null)
  {
    $items = Requests::getListRemove();
    $data = [
      ['ID', 'Организация', 'Контактные данные', 'Сообщение']
    ];
    foreach ($items as $key => $item) {
      if (!empty($user_id) && $item['user_id'] != $user_id)
        continue;
      $name = implode(' ', [$item['firstname'], $item['fathername'], $item['lastname']]);
      $name = trim(str_replace('  ', ' ', $name));
      $date = !empty($item['data']) ? json_decode($item['data'], true) : array();
      $connection = implode(' ', array_column($item['connection'], 'value'));
      $data[] = [
        $item['id'],
        (!empty($item['org']) ? $item['org']['title'] . " / " : '') . $name,
        $connection,
        (
          !empty($date['your-city']) ? "Город: " . $date['your-city'] : ''
        )
        .
        (
          !empty($date['text-399']) ? " Сообщение: " . $date['text-399'] . " " : ''
        )

      ];
    }

    ob_start();
    AppFunctions::convert_to_csv($data, 'снятые-заявки-' . strtotime("now") . ".csv", ';');
    $res = ob_get_clean();

    return $res;
  }

  /**
   * @return \yii\console\Response|\yii\web\Response
   */
  public static function actionChangePurpose()
  {
    $res = \Yii::$app->getResponse();
    $res->format = \yii\web\Response::FORMAT_JSON;

    $result = [
      'code' => 'error',
      'msg' => 'Ошибка, обратитесь к администратору сайта'
    ];

    $post = Yii::$app->request->post();
    if (
      !empty($post['user_id']) &&
      !empty($post['req_id']) &&
      !empty($post['user_id_old'])
    ) {

      if ($post['user_id'] != $post['user_id_old']) {
        if (Requests::changePurpose($post['user_id'], $post['req_id'], $post['user_id_old'])) {
          $result = [
            'code' => 'success',
            'msg' => 'Данная заявка переназначена на нового менеджера. Статус заявки перешел в "Ожидания принятия".'
          ];
        }
      } else {
        $result['msg'] = "Старый менеджер и новый совпадают. Попробуйте все таки сменить менеджера";
      }
    }

    $res->data = $result;
    return $res;
  }

  /**
   * @return \yii\console\Response|\yii\web\Response
   */
  public function actionCancelPurpose()
  {
    $res = \Yii::$app->getResponse();
    $res->format = \yii\web\Response::FORMAT_JSON;

    $result = [
      'code' => 'error',
      'msg' => 'Ошибка, обратитесь к администратору сайта'
    ];

    $post = Yii::$app->request->post();
    if (!empty($post['user_id']) && !empty($post['req_id'])) {
      $role = Yii::$app->authManager->getRolesByUser(Yii::$app->user->id);
      if (!empty($role['admin']) || (!empty($role['manager'] && $post['user_id'] == Yii::$app->user->id))) {
        if (Requests::cancelPurpose($post['user_id'], $post['req_id'])) {
          if (!empty($role['admin'])) {
            $result = [
              'code' => 'success',
              'msg' => 'С менеджера успешно снята заявка'
            ];
          } else {
            $result = [
              'code' => 'success',
              'msg' => 'Вы отменили заявку'
            ];
          }
        }
      } else {
        $result['msg'] = 'Ошибка, у Вас не достаточно прав на данное действие';
      }
    }

    $res->data = $result;
    return $res;
  }

  /**
   * @return \yii\console\Response|\yii\web\Response
   */
  public function actionAcceptPurpose()
  {
    $res = \Yii::$app->getResponse();
    $res->format = \yii\web\Response::FORMAT_JSON;
    $result = [
      'code' => 'error',
      'msg' => 'Ошибка, обратитесь к администратору сайта'
    ];

    $post = Yii::$app->request->post();
    if (!empty($post['user_id']) && !empty($post['req_id'])) {
      $role = Yii::$app->authManager->getRolesByUser(Yii::$app->user->id);
      if (!empty($role['manager'] && $post['user_id'] == Yii::$app->user->id)) {
        if (Requests::acceptPurpose($post['user_id'], $post['req_id'])) {
          $result = [
            'code' => 'success',
            'msg' => 'Вы взяли данную заявку в работу, удачи <i class="icon-emoticon-smile warning"></i>'
          ];
        }
      } elseif (!empty($role['admin'])) {
        $result['msg'] = 'Админ не может менеджером. Не нужно вам выполнять данную работу';
      } else {
        $result['msg'] = 'Ошибка, у Вас не достаточно прав на данное действие';
      }
    }

    $res->data = $result;
    return $res;
  }

  /**
   * Finds the Requests model based on its primary key value.
   * If the model is not found, a 404 HTTP exception will be thrown.
   * @param $id
   * @return null|static
   * @throws ForbiddenHttpException
   * @throws NotFoundHttpException
   */
  protected function findModel($id)
  {
    $user_id = Yii::$app->user->id;
    $role = Module::getRoleUser($user_id);
    if ($role == User::STATUS_ACT) {
      $req_access = RequestsAccess::find()
        ->where([
          'user_id' => $user_id,
          'requests_id' => $id,
          'trash' => 0
        ])
        ->asArray()->one();
      if (!empty($req_access)) {
        if (($model = Requests::findOne($id)) !== null) {
          return $model;
        }
      }
    } elseif ($role == 'admin') {
      if (($model = Requests::findOne($id)) !== null) {
        return $model;
      }
    } else {
      throw new ForbiddenHttpException(Yii::t('app', 'Вам не разрешено производить данное действие.'));
    }


    throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
  }

  /**
   * @return \yii\console\Response|\yii\web\Response
   */
  public function actionSetTitle()
  {
    $res = \Yii::$app->getResponse();
    $res->format = \yii\web\Response::FORMAT_JSON;
    $result = [
      'code' => 'error',
      'msg' => 'Ошибка, обратитесь к администратору сайта'
    ];

    $post = Yii::$app->request->post();
    if (!empty($post['Requests']['title']) && !empty($post['Requests']['id'])) {
      $row = Requests::findOne(['id' => $post['Requests']['id']]);
      $row->title = $post['Requests']['title'];
      if ($row->save()) {
        $result = [
          'code' => 'success',
          'msg' => ''
        ];
      }
    }
    $res->data = $result;
    return $res;
  }

  /**
   * @return string
   */
  public function actionJobs()
  {
    $user_id = \Yii::$app->user->id;
    $role = Module::getRoleUser($user_id);
    $view = $role . '/jobs';
    $request_count = $role == 'admin' ? Requests::getListInCount() : 0;
    return $this->render($view, [
      'request_in' => $role == 'admin' ? Requests::getListIn() : Requests::getListInForUser($user_id),
      'pages' => new Pagination(['totalCount' => $request_count, 'pageSize' => Yii::$app->params['per_page_requests']]),
      'list_users' => $role == 'admin' ? User::getListManagers() : [],
    ]);
  }

  public function actionSuccesses()
  {
    $type = 1;
    $user_id = \Yii::$app->user->id;
    $role = Module::getRoleUser($user_id);
    $view = $role . '/successes';
    $request_count = $role == 'admin' ? Requests::getListSuccessCount($type) : 0;
    return $this->render($view, [
      'request_success' => $role == 'admin' ? Requests::getListSuccess(false, $type) : Requests::getListSuccessForUser($user_id),
      'pages' => new Pagination(['totalCount' => $request_count, 'pageSize' => Yii::$app->params['per_page_requests']]),
      'list_users' => $role == 'admin' ? User::getListManagers(true) : [],
    ]);
  }

  public function actionChangeTender()
  {
    $id = Yii::$app->request->post('id');
    $type = Yii::$app->request->post('type');
    $res = \Yii::$app->getResponse();
    $res->format = \yii\web\Response::FORMAT_JSON;
    $result = [
      'code' => 'error',
      'msg' => 'Ошибка, обратитесь к администратору сайта'
    ];
    if (empty($type) && !empty($id)) {
      $model = Requests::findOne(['id' => $id]);
      if (!empty($model->tender)) {
        $model->tender = 0;
      } else {
        $model->tender = 1;
      }
      if ($model->save()) {
        $result = [
          'code' => 'success',
          'msg' => 'Успешно сохранено',
        ];
      }
    } elseif ($type == 2 && !empty($id)) {
      $model = Requests::findOne(['id' => $id]);
      $model->qtender = 1;
      if ($model->save()) {
        $result = [
          'code' => 'success',
          'msg' => 'Успешно сохранено',
        ];
      }
    }

    return $result;
  }

  public function actionCancels()
  {
    $type = 2;
    $user_id = \Yii::$app->user->id;
    $role = Module::getRoleUser($user_id);
    $view = $role . '/cancels';
    $request_count = $role == 'admin' ? Requests::getListSuccessCount($type) : 0;
    return $this->render($view, [
      'request_success' => $role == 'admin' ? Requests::getListSuccess(false, $type) : Requests::getListSuccessForUser($user_id),
      'pages' => new Pagination(['totalCount' => $request_count, 'pageSize' => Yii::$app->params['per_page_requests']]),
      'list_users' => $role == 'admin' ? User::getListManagers() : [],
    ]);
  }

  public function actionCancelsExport($user_id = null)
  {
    if (!empty($user_id)) {
      $items = Requests::getListSuccessForUser($user_id);
    } else {
      $items = Requests::getListSuccess(false, 2, false);
    }
    $data = [
      ['ID', 'Организация', 'Контактные данные', 'Сообщение']
    ];
    $man = "";
    foreach ($items as $key => $item) {
      if (!empty($user_id) && $item['user_id'] != $user_id)
        continue;
      $name = implode(' ', [$item['firstname'], $item['fathername'], $item['lastname']]);
      $name = trim(str_replace('  ', ' ', $name));
      $date = !empty($item['data']) ? json_decode($item['data'], true) : array();
      $connection = implode(' ', array_column($item['connection'], 'value'));
      $data[] = [
        $item['id'],
        (!empty($item['org']) ? $item['org']['title'] . " / " : '') . $name,
        $connection,
        (
          !empty($date['your-city']) ? "Город: " . $date['your-city'] : ''
        )
        .
        (
          !empty($date['text-399']) ? " Сообщение: " . $date['text-399'] . " " : ''
        )

      ];
    }
    $name = 'отмененные-заявки-';
    if (!empty($user_id)) {
      $name .= UserProfile::getFullName($user_id) . "-";
    }
    ob_start();
    AppFunctions::convert_to_csv($data, $name . strtotime("now") . ".csv", ';');
    $res = ob_get_clean();

    return $res;

  }

  public function actionPaid()
  {
    $user_id = \Yii::$app->user->id;
    $role = Module::getRoleUser($user_id);
    $view = $role . '/paid';
    return $this->render($view, [
      'requests' => $role == 'admin' ? Requests::getListInPaid() : Requests::getListInPaidForUser($user_id),
      'list_users' => $role == 'admin' ? User::getListManagers() : [],
    ]);
  }

  public function actionClosed()
  {
    $user_id = \Yii::$app->user->id;
    $role = Module::getRoleUser($user_id);
    $view = $role . '/closed';
    return $this->render($view, [
      'requests' => $role == 'admin' ? Requests::getListInReq() : Requests::getListInReqForUser($user_id),
      'list_users' => $role == 'admin' ? User::getListManagers() : [],
    ]);
  }

  public function actionUpdateUpd()
  {
    $res = \Yii::$app->getResponse();
    $res->format = \yii\web\Response::FORMAT_JSON;
    $result = [
      'code' => 'error',
      'msg' => 'Ошибка, обратитесь к администратору сайта'
    ];

    $post = Yii::$app->request->post();
    $upd = new RequestsUpd();
    if (!empty($post['RequestsUpd']['id'])) {
      $upd = RequestsUpd::findOne(['id' => $post['RequestsUpd']['id']]);
    }

    if ($upd->load($post) && $upd->save()) {
      $result = [
        'code' => 'success',
        'msg' => 'Информаия успешно сохранена'
      ];
    } else {
      $result['msg'] = 'Не правильно заполнили данные';
    }

    $res->data = $result;
    return $res;
  }

  /**
   * Изменение даты создания заявки
   * @return \yii\console\Response|\yii\web\Response
   */
  public function actionEditDateCreate()
  {
    $res = \Yii::$app->getResponse();
    $res->format = \yii\web\Response::FORMAT_JSON;
    $result = [
      'code' => 'error',
      'msg' => 'Ошибка, обратитесь к администратору сайта'
    ];

    $post = Yii::$app->request->post();
    if (!empty($post['Requests']['id']) && !empty($post['Requests']['create_at'])) {
      $post['Requests']['create_at'] = strtotime($post['Requests']['create_at']);
      $req = Requests::findOne(['id' => $post['Requests']['id']]);
      if ($post['Requests']['create_at'] <= strtotime("now")) {
        if ($req->load($post) && $req->save()) {
          $result = [
            'code' => 'success',
            'msg' => 'Дата создания успешно изменена'
          ];
        } else {
          $result['msg'] = $req->errors;
        }
      } else {
        $result['msg'] = 'Дата, которую пытаетесь установить, находится в будущем. Измените дату, чтобы она была в прошлом';
      }
    } else {
      $result['msg'] = 'Не указан ID заявки/Дата создания';
    }

    $res->data = $result;
    return $res;
  }

  /**
   * Изменение даты закрытия заявки
   * @return \yii\console\Response|\yii\web\Response
   */
  public function actionEditDateClose()
  {
    $res = \Yii::$app->getResponse();
    $res->format = \yii\web\Response::FORMAT_JSON;
    $result = [
      'code' => 'error',
      'msg' => 'Ошибка, обратитесь к администратору сайта'
    ];
    $post = Yii::$app->request->post();
    if (!empty($post['Requests']['id']) && !empty($post['Requests']['date_end'])) {
      $post['Requests']['date_end'] = strtotime($post['Requests']['date_end']);
      $id = $post['Requests']['id'];
      if ($post['Requests']['date_end'] <= strtotime("now")) {
        $req = Requests::findOne(['id' => $id]);
        if ($req->load($post) && $req->save()) {
          $result = [
            'code' => 'success',
            'msg' => 'Дата закрытия успешно изменена'
          ];
          $payments = RequestsPayment::findAll(['requests_id' => $id, 'trash' => 0]);
          if (!empty($payments)) {
            foreach ($payments as $payment) {
              $payment->create_at = $req->date_end;
              $payment->save();
            }
          }
        } else {
          $result['msg'] = $req->errors;
        }
      } else {
        $result['msg'] = 'Дата, которую пытаетесь установить, находится в будущем. Измените дату, чтобы она была в прошлом';
      }
    } else {
      $result['msg'] = 'Не указан ID заявки/Дата закрытия';
    }


    $res->data = $result;
    return $res;
  }

  /**
   * Создание счета
   */
  public function actionCreateChet()
  {
    $res = \Yii::$app->getResponse();
    $res->format = \yii\web\Response::FORMAT_JSON;
    $result = [
      'code' => 'error',
      'msg' => 'Ошибка, обратитесь к администратору сайта'
    ];
    $request_id = 0;
    $post = Yii::$app->request->post();
    $new_billings = new RequestsBilling();
    $_billings = [];
    if ($new_billings->load($post)) {
      $request_id = $new_billings->content['request_id'];
      if (is_array($new_billings->calc_id)) {
        if (count($new_billings->calc_id) > 1) {
          foreach ($new_billings->calc_id as $key => $item) {
            $_billings[] = $new_billings;
            $_billings[$key]->calc_id = $item;
          }
        } else {
          $new_billings->calc_id = $new_billings->calc_id[0];
          $_billings[] = $new_billings;
        }
      }
    }
    if (empty($_billings)) {
      Yii::$app->session->setFlash('error', 'Не достаточно данных');
      return $this->goBack();
    }

    //Сохраним варианты
    $dictionary = [];
    foreach ($_billings as $billing) {
      if (!empty($billing->content['items']['name'])) {
        foreach ($billing->content['items']['name'] as $item) {
          $dictionary[] = trim(strip_tags($item));
        }
      }
      $billing->save();
    }
    if (!empty($dictionary)) {
      $dictionary = array_unique($dictionary);
      foreach ($dictionary as $phrase) {
        $rows = Dictionary::find()->select('phrase')
          ->where(['phrase' => $phrase])
          ->andWhere(['trash' => 0])
          ->asArray()
          ->all();
        if (empty($rows)) {
          $m_dic = new Dictionary();
          $m_dic->phrase = $phrase;
          $m_dic->trash = 0;
          $m_dic->save();
        }
      }
    }
    Yii::$app->session->setFlash('success', 'Счет успешно создан');
    if (!empty($request_id)) {
      return $this->redirect(['/admin/request/view', 'id' => $request_id]);
    } else {
      return $this->goBack();
    }

    $res->data = $result;
    return $res;
  }


  /**
   * Обновление счета
   * @return \yii\console\Response|\yii\web\Response
   */
  public function actionUpdateChet($id)
  {
    $model = RequestsBilling::findOne(['id' => $id]);
    $post = Yii::$app->request->post();
    if ($model->load($post)) {
      $dictionary = [];
      if (!empty($model->content['items']['name'])) {
        foreach ($model->content['items']['name'] as $item) {
          $dictionary[] = trim(strip_tags($item));
        }
      }

      if ($model->save()) {
        if (!empty($dictionary)) {
          $dictionary = array_unique($dictionary);
          foreach ($dictionary as $phrase) {
            $rows = Dictionary::find()->select('phrase')
              ->where(['phrase' => $phrase])
              ->andWhere(['trash' => 0])
              ->asArray()
              ->all();
            if (empty($rows)) {
              $m_dic = new Dictionary();
              $m_dic->phrase = $phrase;
              $m_dic->trash = 0;
              $m_dic->save();
            }
          }
        }
        Yii::$app->session->setFlash('success', 'Счет успешно обновлен');
      } else {
        Yii::$app->session->setFlash('error', 'Проверьте правильность данных');
      }
    }

    return $this->render('chet', [
      'model' => $model
    ]);
  }

  /**
   * Редактирование счета
   * @param $id
   * @return string
   */
  public function actionEditChet($id)
  {

    $billing = RequestsBilling::findOne(['id' => $id]);

    return $this->render('chet', [
      'model' => $billing
    ]);
  }

  public function actionViewChet($id)
  {
    $res = \Yii::$app->getResponse();
    $res->format = \yii\web\Response::FORMAT_JSON;
    $result = [
      'code' => 'error',
      'msg' => 'Ошибка, обратитесь к администратору сайта'
    ];
    $billing = RequestsBilling::findOne(['id' => $id, 'trash' => 0]);
    if (!empty($billing) && !empty($billing->content)) {
      $post = json_decode($billing->content, true);
      $post['type'] = $billing->type;
      $post['number'] = $billing->number;
      $post['date'] = $billing->date;
    }
    if (!empty($post['request_id']) && !empty($post['number']) && !empty($post['date'])) {
      if (empty($post['type']) || !empty($post['type']) && ($post['type'] == '1' || $post['type'] == '3')) {
        //Вызов создание счета для stpnn

        $mpdf = new Mpdf([
          'default_font' => 'FreeSans',
          'default_font_size' => '10'
        ]);
        $name_file = 'Счет ' . $post['number'] . ' от ' . $post['date'];
        $mpdf->title = $name_file;
        $folder_font = $_SERVER['DOCUMENT_ROOT'] . '/app-assets/fonts/';
        $str_data = $post['date'];
        $mpdf->WriteHTML('<style>
                    @page {
                        background: url("/crm/img/newblank.jpg") no-repeat 0 0;
                        background-image-resize: 6;
                        margin-top:170px;
                        margin-bottom:110px;
                    }
                </style>');
        $mpdf->WriteHTML('<div style="font-size:5px;">');

        $mpdf->WriteHTML('<div style="font-weight:bold;margin-top:20px;text-decoration: underline;">Общество с ограниченной ответственностью "СпецТехПром"</div>');
        $mpdf->WriteHTML('<div style="font-weight:bold;margin-top:5px;">Адрес: 603064, Нижегородская обл., г.Нижний Новгород, ул. Новикова-Прибоя, д. 6А, к.3,</div>');
        $mpdf->WriteHTML('<div style="font-weight:bold;margin-top:5px;">тел.: 8 (831) 233-03-13</div>');
        $mpdf->WriteHTML('<div style="margin-top:5px;">Почтовый адрес: 603064, г.Нижний Новгород, а/я 56</div>');
        $mpdf->WriteHTML('<div style="margin-top:10px;text-align: center;font-weight:bold;font-size:12pt">Образец заполнения платежного поручения</div>');
        if (empty($post['type']) || !empty($post['type']) && ($post['type'] == '1')) {
          $mpdf->WriteHTML('<style>.text-right{text-align: right}.mt-1{margin-top: 5px}.mb-1{margin-bottom: 5px}.mt-2{margin-top: 10px}.mt-3{margin-top: 20px}.mb-3{margin-bottom: 20px}.h2{font-size: 15pt;font-weight: bold}.text-center{text-align: center}table{width:100%;border-collapse:collapse}table td{border:1px solid;padding:2px 5px}table td.text-bottom{vertical-align: bottom}table td.borderNone{border:none}</style><table style="margin-top: 5px;border: 1px solid">
                        <tbody>
                            <tr>
                                <td>ИНН 5258079050</td>
                                <td>КПП 525801001</td>
                                <td rowspan="2" class="text-center text-bottom">Сч. №</td>
                                <td rowspan="2" class="text-bottom">40702810414210000134</td>
                            </tr>
                            <tr>
                                <td colspan="2">Получатель<br>ООО "СпецТехПром"</td>
                            </tr>
                            <tr>
                                <td rowspan="2" colspan="2">Банк получателя<br>ПАО "САРОВБИЗНЕСБАНК" г.Саров</td>
                                <td class="text-center">БИК</td>
                                <td rowspan="2">042202718<br>30101810422020000718</td>
                            </tr>  
                            <tr>
                                <td class="text-center">Сч. №</td>
                            </tr> 
                        </tbody>
                    </table>');
        } elseif (!empty($post['type']) && ($post['type'] == '3')) {
          $mpdf->WriteHTML('<style>.text-right{text-align: right}.mt-1{margin-top: 5px}.mb-1{margin-bottom: 5px}.mt-2{margin-top: 10px}.mt-3{margin-top: 20px}.mb-3{margin-bottom: 20px}.h2{font-size: 15pt;font-weight: bold}.text-center{text-align: center}table{width:100%;border-collapse:collapse}table td{border:1px solid;padding:2px 5px}table td.text-bottom{vertical-align: bottom}table td.borderNone{border:none}</style><table style="margin-top: 5px;border: 1px solid">
                        <tbody>
                            <tr>
                                <td>ИНН 5258079050</td>
                                <td>КПП 525801001</td>
                                <td rowspan="2" class="text-center text-bottom">Сч. №</td>
                                <td rowspan="2" class="text-bottom">40702810200050000636</td>
                            </tr>
                            <tr>
                                <td colspan="2">Получатель<br>ООО "СпецТехПром"</td>
                            </tr>
                            <tr>
                                <td rowspan="2" colspan="2">Банк получателя<br>НИЖЕГОРОДСКИЙ Ф-Л ПАО АКБ "МЕТАЛЛИНВЕСТБАНК" г. Нижний Новгород</td>
                                <td class="text-center">БИК</td>
                                <td rowspan="2">042202886<br>30101810400000000886</td>
                            </tr>  
                            <tr>
                                <td class="text-center">Сч. №</td>
                            </tr> 
                        </tbody>
                    </table>');
        }
        $mpdf->WriteHTML('<div class="text-center h2 mt-3">
                        СЧЕТ №' . $post['number'] . ' от ' . date('d', $str_data) . ' ' . AppFunctions::getRussianMonthNameSclon
          (date('n', $str_data) - 1) . ' ' . date('Y', $str_data) . ' г.</div>');
        $mpdf->WriteHTML('<div class="mt-2">');
        $client = Client::findOne(['id' => $post['client_id']]);
        $request = Requests::findOne(['id' => $post['request_id']]);
        $org_info = $request->lizing;
        if (empty($org_info)) {
          $org_info = $client->organization;
        }
        $mpdf->WriteHTML('<div style="margin-bottom: 10px">Поставщик: ООО "СпецТехПром", ИНН 5258079050, КПП 525801001, 603064, Нижегородская обл, Нижний Новгород г, Новикова-Прибоя ул, дом № 6А, кабинет 3, тел.: 8(831)233-03-13</div>');
        $str_org = '';
        if (!empty($org_info->title)) {
          $str_org .= $org_info->title . ',';
        }
        if (!empty($org_info->inn)) {
          $str_org .= ' ИНН' . $org_info->inn . ',';
        }
        if (!empty($org_info->kpp)) {
          $str_org .= ' КПП' . $org_info->kpp . ',';
        }
        if (!empty($org_info->ur_address)) {
          $str_org .= ' ' . $org_info->ur_address . ',';
        }
        $phone = $client->getConnection('phone')->one();
        if (!empty($phone)) {
          $str_org .= ' тел.: +' . $phone->value;
        }
        if (!empty($str_org)) {
          $mpdf->WriteHTML('<div>Получатель: ' . $str_org . '</div>');
        }

        $mpdf->WriteHTML('</div>');
        $calcs = $post['items'];

        $mpdf->WriteHTML('<table style="margin-top: 10px;border: none">
                        <tbody>
                            <tr>
                                <td class="text-center">№</td>
                                <td class="text-center">Наименование<br>товара</td>
                                <td class="text-center">Единица<br>измерения</td>
                                <td class="text-center">Количество</td>
                                <td class="text-center">Цена</td>
                                <td class="text-center">Сумма</td>
                            </tr>');
        $num = 0;
        $total = 0;
        foreach ($calcs['name'] as $key => $name) {
          $cost = $calcs['price'][$key] * $calcs['count'][$key];
          $total += $cost;
          $num++;
          $mpdf->WriteHTML('
                                    <tr>
                                        <td class="text-right">' . $num . '</td>
                                        <td>' . $name . '</td>
                                        <td class="text-center text-bottom">' . $calcs['unit'][$key] . '</td>
                                        <td class="text-right text-bottom">' . $calcs['count'][$key] . '</td>
                                        <td class="text-center text-bottom">' . (!empty($calcs['price'][$key]) ? number_format
            ($calcs['price'][$key], 2, '-', '') : 0 - 00) . '</td>
                                        <td class="text-center text-bottom">' . (!empty($cost) ? number_format
            ($cost, 2, '-', '') : 0 - 00) . '</td>
                                    </tr>');
        }
        $mpdf->WriteHTML('
                        <tr>
                            <td style="font-weight: bold;" class="text-right borderNone" colspan="5">Итого:</td>
                            <td style="font-weight: bold;" class="text-center text-bottom">' . number_format
          ($total, 2, '-', '') . '</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;" class="text-right borderNone" colspan="5">Итого НДС:</td>
                            <td style="font-weight: bold;" class="text-center text-bottom">' . number_format
          ($total * 20 / 120, 2, '-', '') . '</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;" class="text-right borderNone" colspan="5">Всего к оплате:</td>
                            <td style="font-weight: bold;" class="text-center text-bottom">' . number_format
          ($total, 2, '-', '') . '</td>
                        </tr>');
        $mpdf->WriteHTML('                            
                        </tbody>
                    </table>');
        $mpdf->WriteHTML('<div class="mt-2">Всего наименований ' . $num . ', на сумму ' . number_format
        ($total, 2, '.', '\'') . '</div>');
        $mpdf->WriteHTML('<div style="font-weight: bold;">' . AppFunctions::mb_ucfirst(AppFunctions::num2str($total)) . '</div>');
        $mpdf->WriteHTML('<div style="position:relative;-ms-background-position: 35mm;background-position: 35mm;background-size:contain; background: url(\'/crm/img/stpnn.png\') no-repeate" class="mt-3">
                        
                        <p style="height:15mm;background-size:contain;background-position: 45mm -4mm;background: url(\'/crm/img/signature.png\') no-repeate">Руководитель предприятия ___________________ (Воронин М.П.)</p>
                        <p style="position:absolute;height:15mm;margin-top:-15mm;background-size:contain;background-position: 40mm -4mm;background: url(/crm/img/signature.png) no-repeate">Главный бухгалтер ___________________ (Воронин М.П.)</p>
                    </div>');
        $mpdf->WriteHTML('</div>');
        $name_file .= ' ' . strtotime("now") . '.pdf';
        $mpdf->Output($name_file, 'I');
      } elseif (!empty($post['type']) && $post['type'] == '2') {
        $mpdf = new Mpdf([
          'default_font' => 'FreeSans',
          'default_font_size' => '10'
        ]);
        $name_file = 'Счет ' . $post['number'] . ' от ' . date('d.m.Y', $post['date']);
        $mpdf->title = $name_file;
        $str_data = $post['date'];
        $mpdf->WriteHTML('<img src="/crm/img/stpnn-td.jpg"/>');
        $mpdf->WriteHTML('<div style="padding-top: 10mm;">');

        $mpdf->WriteHTML('<div style="font-weight:bold;margin-top:5px;text-decoration: underline;">ООО "Торговый Дом СпецТехПром"</div>');
        $mpdf->WriteHTML('<div style="font-weight:bold;margin-top:5px;">Адрес: 603073, Нижегородская обл., г.Нижний Новгород, ул. Адмирала Нахимова, д. 10, кор.1, к.3,тел.: 8 (831) 233-03-13</div>');
        $mpdf->WriteHTML('<div style="margin-top:10px;text-align: center;font-weight:bold;font-size:12pt">Образец заполнения платежного поручения</div>');
        $mpdf->WriteHTML('<style>.text-right{text-align: right}.mt-1{margin-top: 5px}.mb-1{margin-bottom: 5px}.mt-2{margin-top: 10px}.mt-3{margin-top: 20px}.mb-3{margin-bottom: 20px}.h2{font-size: 15pt;font-weight: bold}.text-center{text-align: center}table{width:100%;border-collapse:collapse}table td{border:1px solid;padding:2px 5px}table td.text-bottom{vertical-align: bottom}table td.borderNone{border:none}</style><table style="margin-top: 5px;border: 1px solid">
                        <tbody>
                            <tr>
                                <td>ИНН 5258123662</td>
                                <td>КПП 525801001</td>
                                <td rowspan="2" class="text-center text-bottom">Сч. №</td>
                                <td rowspan="2" class="text-bottom">40702810614210000510</td>
                            </tr>
                            <tr>
                                <td colspan="2">Получатель<br>ООО "Торговый Дом СпецТехПром"</td>
                            </tr>
                            <tr>
                                <td rowspan="2" colspan="2">Банк получателя<br>ПАО "САРОВБИЗНЕСБАНК" г.Саров</td>
                                <td class="text-center">БИК</td>
                                <td rowspan="2">042202718<br>30101810422020000718</td>
                            </tr>  
                            <tr>
                                <td class="text-center">Сч. №</td>
                            </tr> 
                        </tbody>
                    </table>');
        $mpdf->WriteHTML('<div class="text-center h2 mt-3">
                        СЧЕТ №' . $post['number'] . ' от ' . date('d', $str_data) . ' ' . AppFunctions::getRussianMonthNameSclon
          (date('n', $str_data) - 1) . ' ' . date('Y', $str_data) . ' г.</div>');
        $mpdf->WriteHTML('<div class="mt-2">');
        $client = Client::findOne(['id' => $post['client_id']]);
        $org_info = $client->lizing;
        if (empty($org_info)) {
          $org_info = $client->organization;
        }
        $mpdf->WriteHTML('<div style="margin-bottom: 10px">Поставщик: ООО "Торговый Дом СпецТехПром", ИНН 5258123662, КПП 525801001, 603073, Нижегородская обл, Нижний Новгород г, Адмирала Нахимова ул, дом № 10, корпус 1, квартира 11, тел.: +7(831)233-03-13</div>');
        $str_org = '';
        if (!empty($org_info->title)) {
          $str_org .= $org_info->title . ',';
        }
        if (!empty($org_info->inn)) {
          $str_org .= ' ИНН' . $org_info->inn . ',';
        }
        if (!empty($org_info->kpp)) {
          $str_org .= ' КПП' . $org_info->kpp . ',';
        }
        if (!empty($org_info->ur_address)) {
          $str_org .= ' ' . $org_info->ur_address . ',';
        }
        $phone = $client->getConnection('phone')->one();
        if (!empty($phone)) {
          $str_org .= ' тел.: +' . $phone->value;
        }
        if (!empty($str_org)) {
          $mpdf->WriteHTML('<div>Получатель: ' . $str_org . '</div>');
        }
        $mpdf->WriteHTML('</div>');
        $calcs = $post['items'];
        $mpdf->WriteHTML('<table style="margin-top: 10px;border: none">
                        <tbody>
                            <tr>
                                <td class="text-center">№</td>
                                <td class="text-center">Наименование<br>товара</td>
                                <td class="text-center">Единица<br>измерения</td>
                                <td class="text-center">Количество</td>
                                <td class="text-center">Цена</td>
                                <td class="text-center">Сумма</td>
                            </tr>');
        $num = 0;
        $total = 0;
        foreach ($calcs['name'] as $key => $name) {
          $cost = $calcs['price'][$key] * $calcs['count'][$key];
          $total += $cost;
          $num++;
          $mpdf->WriteHTML('
                                    <tr>
                                        <td class="text-right">' . $num . '</td>
                                        <td>' . $name . '</td>
                                        <td class="text-center text-bottom">' . $calcs['unit'][$key] . '</td>
                                        <td class="text-right text-bottom">' . $calcs['count'][$key] . '</td>
                                        <td class="text-center text-bottom">' . (!empty($calcs['price'][$key]) ? number_format
            ($calcs['price'][$key], 2, '-', '') : 0 - 00) . '</td>
                                        <td class="text-center text-bottom">' . (!empty($cost) ? number_format
            ($cost, 2, '-', '') : 0 - 00) . '</td>
                                    </tr>');
        }
        $mpdf->WriteHTML('
                        <tr>
                            <td style="font-weight: bold;" class="text-right borderNone" colspan="5">Итого:</td>
                            <td style="font-weight: bold;" class="text-center text-bottom">' . number_format
          ($total, 2, '-', '') . '</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;" class="text-right borderNone" colspan="5">Итого НДС:</td>
                            <td style="font-weight: bold;" class="text-center text-bottom">' . number_format
          ($total * 20 / 120, 2, '-', '') . '</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;" class="text-right borderNone" colspan="5">Всего к оплате:</td>
                            <td style="font-weight: bold;" class="text-center text-bottom">' . number_format
          ($total, 2, '-', '') . '</td>
                        </tr>');
        $mpdf->WriteHTML('                            
                        </tbody>
                    </table>');
        $mpdf->WriteHTML('<div class="mt-2">Всего наименований ' . $num . ', на сумму ' . number_format
        ($total, 2, '.', '\'') . '</div>');
        $mpdf->WriteHTML('<div style="font-weight: bold;">' . AppFunctions::num2str($total)
          . '</div>');
        $mpdf->WriteHTML('<div style="position:relative;-ms-background-position: 35mm;background-position: 35mm;background-size:contain; background: url(\'/crm/img/td.png\') no-repeate" class="mt-3">
                        
                        <p style="height:15mm;background-size:contain;background-position: 45mm -4mm;background: url(\'/crm/img/signature.png\') no-repeate">Руководитель предприятия ___________________ (Воронин М.П.)</p>
                        <p style="position:absolute;height:15mm;margin-top:-15mm;background-size:contain;background-position: 40mm -4mm;background: url(/crm/img/signature.png) no-repeate">Главный бухгалтер ___________________ (Воронин М.П.)</p>
                    </div>');
        $mpdf->WriteHTML('</div>');
        $name_file .= ' ' . strtotime("now") . '.pdf';
        $mpdf->SetHTMLFooter('<img src="/crm/img/stpnn-td-footer.jpg"/>');
        $mpdf->Output($name_file, 'I');
      }
    }

    $res->data = $result;
    return $res;
  }

  public function actionLoadCalc($req_id)
  {
    $res = \Yii::$app->getResponse();
    $res->format = \yii\web\Response::FORMAT_JSON;
    $result = [
      'code' => 'error',
      'msg' => 'Ошибка, обратитесь к администратору сайта'
    ];
    $calc_ids = RequestsCalculator::find()
      ->select('id,title')
      ->where(['requests_id' => $req_id, 'trash' => 0])
      ->asArray()->all();
    if (!empty($calc_ids)) {
      $result = [
        'code' => 'success',
        'items' => ArrayHelper::map($calc_ids, 'id', 'title')

      ];
    } else {
      $result['msg'] = 'Не создано ни одного коммерческого предложения';
    }
    $res->data = $result;
    return $res;
  }

  public function actionLoadLastCalc($type)
  {
    $res = \Yii::$app->getResponse();
    $res->format = \yii\web\Response::FORMAT_JSON;
    $result = [
      'code' => 'error',
      'msg' => 'Ошибка, обратитесь к администратору сайта'
    ];
    $num = RequestsBilling::find()
      ->select('number')
      ->where([ /*'type' => $type,*/'trash' => 0])
      ->orderBy(['number' => SORT_DESC])
      ->asArray()->one();

    if (!empty($num)) {
      $result = [
        'code' => 'success',
        'val' => $num['number'] + 1

      ];
    } else {
      $result = [
        'code' => 'success',
        'val' => 1

      ];
    }
    $res->data = $result;
    return $res;
  }

  public function actionDeleteMass()
  {
    $post = Yii::$app->request->post();
    $res = \Yii::$app->getResponse();
    $res->format = \yii\web\Response::FORMAT_JSON;
    $result = [
      'code' => 'error',
      'msg' => 'Ошибка, обратитесь к администратору сайта'
    ];


    if (!empty($post['ids'])) {
      foreach ($post['ids'] as $id) {
        $req = Requests::findOne(['id' => $id]);
        $req->trash = 1;
        $req->save();
      }
      $result = [
        'code' => 'success',
        'msg' => 'Успешно удален' . (count($post['ids']) > 1 ? 'ы заявки' : 'а заявка')
      ];
    } else {
      $result['msg'] = 'Выберите заявки для удаления';
    }

    $res->data = $result;
    return $res;
  }

  public function actionSaveVin()
  {
    $post = Yii::$app->request->post();
    $res = \Yii::$app->getResponse();
    $res->format = \yii\web\Response::FORMAT_JSON;
    $result = [
      'code' => 'error',
      'msg' => 'Ошибка, обратитесь к администратору сайта'
    ];

    if (!empty($post['vin']) && !empty($post['id'])) {
      $row = Requests::findOne(['id' => $post['id'], 'trash' => 0]);
      if (!empty($row)) {
        $row->vin = $post['vin'];
        if ($row->save()) {
          $result = [
            'code' => 'success',
            'msg' => 'Успешно сохранено',
            'value' => $post['vin']
          ];
        }
      }
    }
    $res->data = $result;
    return $res;
  }

  public function actionChangeTrend()
  {
    $post = Yii::$app->request->post();
    $res = \Yii::$app->getResponse();
    $res->format = \yii\web\Response::FORMAT_JSON;
    $result = [
      'code' => 'error',
      'msg' => 'Ошибка, обратитесь к администратору сайта'
    ];

    if (!empty($post['id'])) {
      $row = Requests::findOne(['id' => $post['id'], 'trash' => 0]);
      $last = Requests::getLastStatus($post['id']);
      if (!empty($row) && $last < RequestsProgress::STATUS_PAYMENT) {
        if ($row->setTrend()) {
          $result = [
            'code' => 'success',
            'msg' => 'Успешно сохранено',
          ];
        }
      } elseif (!empty($row) && $last >= RequestsProgress::STATUS_PAYMENT) {
        $result = [
          'code' => 'error',
          'msg' => 'После оплаты заявку нельзя сделать избранной',
        ];
      }
    }
    $res->data = $result;
    return $res;
  }

  public function actionExport()
  {
    return $this->render('export');
  }

  public function actionExportQuery()
  {
    $res = \Yii::$app->getResponse();
    $post = Yii::$app->request->post();
    $res->format = \yii\web\Response::FORMAT_JSON;

    $result = [
      'code' => 'error',
      'msg' => 'Ошибка, обратитесь к администратору сайта'
    ];
    if (!empty($post['start']) && !empty($post['end'])) {
      $data = Requests::ExportQuery($post['start'], $post['end']);
      if (!empty($data)) {
        $result = [
          'code' => 'success',
          'data' => $data
        ];
      } else {
        $result = [
          'code' => 'error',
          'msg' => 'Не удалось найти заявки на искомый период, попробуйте сменить/расширить зону поиска'
        ];
      }
    } else {
      $result = [
        'code' => 'error',
        'msg' => 'Установите дату начала и дату окончания поиска'
      ];
    }

    $res->data = $result;
    return $res;
  }

  public function actionExportQueryCsv()
  {
    $post = Yii::$app->request->post();
    if (!empty($post['start']) && !empty($post['end'])) {
      $data = Requests::ExportQuery($post['start'], $post['end']);
      if (!empty($data)) {
        array_unshift($data, [
          'ID',
          'Создан',
          'Контактные данные',
          'Менеджер',
          'Сумма контрактов',
          'Что заказали',
          'Доход от сделки',
          'Статус сделки',
          'Город',
          'UTM Source',
          'UTM Medium',
          'UTM Campaign',
          'UTM Content',
          'UTM Term'
        ]);
        ob_start();
        AppFunctions::convert_to_csv($data, 'export_' . strtotime("now") . '.csv', ';');
        $res = ob_get_clean();
        print_r($res);
        die;
      }
    }
  }

  public function actionAddPaymentForecast()
  {
    $res = \Yii::$app->getResponse();
    $post = Yii::$app->request->post();
    $res->format = \yii\web\Response::FORMAT_JSON;

    $result = [
      'code' => 'error',
      'msg' => 'Ошибка, обратитесь к администратору сайта'
    ];
    if (empty($post['id'])) {

      $model = new RequestsPaymentPrognosis();

    } else {
      $model = RequestsPaymentPrognosis::findOne(['id' => $post['id']]);
    }

    if ($model->load($post, '') && $model->save()) {
      $result = [
        'code' => 'success',
        'msg' => 'Успешно добавлено'
      ];
    } else {
      $result['msg'] = $model->errors;
    }
    $res->data = $result;
    return $res;
  }

  public function actionAddForecast()
  {
    $res = \Yii::$app->getResponse();
    $post = Yii::$app->request->post();
    $res->format = \yii\web\Response::FORMAT_JSON;

    $result = [
      'code' => 'error',
      'msg' => 'Ошибка, обратитесь к администратору сайта'
    ];
    if (empty($post['id'])) {
      $model = RequestsPrognosis::findOne(['request_id' => $post['request_id']]);
      if (empty($model)) {
        $model = new RequestsPrognosis();
      }

    } else {
      $model = RequestsPrognosis::findOne(['id' => $post['id']]);
    }

    if ($model->load($post, '') && $model->save()) {
      $result = [
        'code' => 'success',
        'msg' => 'Успешно добавлено'
      ];
    } else {
      $result['msg'] = $model->errors;
    }
    $res->data = $result;
    return $res;
  }

  public function actionRemovePaymentForecast()
  {
    $res = \Yii::$app->getResponse();
    $post = Yii::$app->request->post();
    $res->format = \yii\web\Response::FORMAT_JSON;

    $result = [
      'code' => 'error',
      'msg' => 'Ошибка, обратитесь к администратору сайта'
    ];
    if (empty($post['id'])) {

      $result['msg'] = 'Не передан ID для удаления';

    } else {
      RequestsPaymentPrognosis::deleteAll(['id' => $post['id']]);
      $result = [
        'code' => 'success',
        'msg' => 'Успешно удалено'
      ];
    }

    $res->data = $result;
    return $res;
  }

  public function actionRemoveForecast()
  {
    $res = \Yii::$app->getResponse();
    $post = Yii::$app->request->post();
    $res->format = \yii\web\Response::FORMAT_JSON;

    $result = [
      'code' => 'error',
      'msg' => 'Ошибка, обратитесь к администратору сайта'
    ];
    if (empty($post['id'])) {

      $result['msg'] = 'Не передан ID для удаления';

    } else {
      RequestsPrognosis::deleteAll(['id' => $post['id']]);
      $result = [
        'code' => 'success',
        'msg' => 'Успешно удалено'
      ];
    }

    $res->data = $result;
    return $res;
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
    if (!empty($post['id'])) {
      $request = Requests::findOne(['id' => $post['id']]);
      if (!empty($request->long_time)) {
        $request->long_time = 0;
      } else {
        $request->long_time = 1;
      }
      if ($request->save()) {
        $result = [
          'code' => 'success',
          'msg' => 'Успешно обновили клиента'
        ];
      }
    }

    $res->data = $result;
    return $res;
  }

  public function actionTestPurpose()
  {
    Requests::acceptPurpose2(15, 6263);
  }
}