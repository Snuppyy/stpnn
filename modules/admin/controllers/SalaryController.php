<?php

namespace app\modules\admin\controllers;

use app\models\App\Request\Requests;
use app\models\App\Request\RequestsPayment;
use app\models\App\UserPremiums;
use app\models\App\UserSalary;
use app\models\App\UserSalaryNotes;
use app\models\User;
use app\modules\admin\Module;
use Yii;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

class SalaryController extends Controller
{

    public function actionIndex()
    {
        $get = Yii::$app->request->get();
        $month_now = $start = strtotime("first day of this month 00:00:00");
        $end = strtotime("last day of this month 23:59:59");
        if(!empty($get['month']) && !empty($get['year'])){
            $start = strtotime($get['month'].'/1/'.$get['year'].' 00:00:00');
            $end = strtotime($get['month'].'/1/'.$get['year'].' 23:59:59 + 1 month -1 day');
        }
        $user_id = Yii::$app->user->id;
        $role = Module::getRoleUser($user_id);
        if($role == 'admin'){
            $managers = User::findManagerForSalary($end);
            return $this->render('admin/index',[
                'start' => $start,
                'end' => $end,
                'month' => $get['month'] ?? date('n'),
                'month_note' => date('m', $end),
                'year' => $get['year'] ?? date('Y'),
                'requests' => Requests::getListRequestCreated($start,$end),
                'seccessed' => Requests::getListRequestsSuccessDate($start,$end),
                'received' => RequestsPayment::getTotalMonth($start,$end),
                'premium' => new UserPremiums(),
                'managers' => $managers,
                'user_notes' => !empty($managers)
                    ? UserSalary::findSalaryNotesForUsers(array_column($managers,'ID'), date('Ym', $end))
                    : ''
            ]);
        }elseif($role == User::STATUS_ACT){
            return $this->render($role.'/index',[
                'start' => $start,
                'start_real' => $month_now,
                'end' => $end,
                'month' => $get['month'] ?? date('n'),
                'year' => $get['year'] ?? date('Y'),
                'seccessed' => Requests::getListSuccessForUser($user_id,$start,$end),
                //'received' => RequestsPayment::getTotalMonth($start,$end,$user_id),
                'requests' => Requests::getListRequestInSalary($user_id),
                'managers' => User::findManagerForSalary($end,$user_id),
                't_salary' => UserSalary::getTotalSalaryUser()
            ]);
        }else{
            throw new ForbiddenHttpException('У вас нет прав для просмотра данного раздела');
        }
    }

    public function actionSaveNote(){
        $post = \Yii::$app->request->post();
        $res = \Yii::$app->getResponse();
        $res->format = \yii\web\Response::FORMAT_JSON;
        $result = [
            'code' => 'error',
            'msg' => 'Возникла ошибка, обратитесь к администратору сайта'
        ];
        if(!empty($post['period']) && !empty($post['user'])){
            $note = UserSalaryNotes::findOne([
                'period' => $post['period'],
                'user_id' => $post['user']
            ]);
            if(empty($note)){
                $note = new UserSalaryNotes();
                $note->period = $post['period'];
                $note->user_id = $post['user'];
            }
            $note->text = $post['text'];
            if($note->save()){
                $result = [
                    'code' => 'success',
                    'msg' => 'Успешно сохранено'
                ];
            }
        }
        $res->data = $result;

        return $res;
    }

    public function actionYearStatistic()
    {
        $get = Yii::$app->request->get();
        $end = strtotime("last day of this month 23:59:59");
        $year = date('Y');
        $months = array();
        if( !empty($get['year']) ){
            if( $get['year'] < $year ) {
                $end = strtotime('12/31/'.$get['year'].' 23:59:59');
                $year = $get['year'];
            }
            elseif( $get['year'] != $year ) {
                throw new ForbiddenHttpException('Указан год больше допустимого');
            }
        }
        $months = range(1, date('n', $end) );
        $user_id = Yii::$app->user->id;
        $role = Module::getRoleUser($user_id);
        if($role == 'admin'){
            $data_month = array();
            foreach ($months as $month) {
                $_start = strtotime($month.'/1/'.$year.' 04:00:00');
                $_end = strtotime($month.'/1/'.$year.' 23:59:59 + 1 month -1 day');
                $managers = User::findManagerForSalary($_end);
                $data_month[$month] = [
                    'start' => $_start,
                    'end' => $_end,
                    'month' => $get['month'] ?? date('n'),
                    'month_note' => date('m', $_end),
                    'year' => $get['year'] ?? date('Y'),
                    'requests' => Requests::getListRequestCreated($_start,$_end),
                    'seccessed' => Requests::getListRequestsSuccessDate($_start,$_end),
                    'received' => RequestsPayment::getTotalMonth($_start,$_end),
                    'premium' => new UserPremiums(),
                    'managers' => $managers,
                    'user_notes' => !empty($managers)
                        ? UserSalary::findSalaryNotesForUsers(array_column($managers,'ID'), date('Ym', $_end))
                        : ''
                ];
            }

            return $this->render('admin/year',['data' => $data_month, 'year' => $year, 'months' => $months]);
        }
        else{
            throw new ForbiddenHttpException('У вас нет прав для просмотра данного раздела');
        }
    }

}