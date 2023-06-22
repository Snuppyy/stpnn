<?php
namespace app\components;

use Yii;

class SendWhatsAppMess
{
  public function __construct($phone = null, $name = null, $mannager = null, $card = null, $phoneManager = null)
  {

    if (!empty($phone) && !empty($name) && !empty($mannager)) {
      $message_json = json_encode([
        "recipient" => $phone,
        "body" => "Здравствуйте " . $name . ",\nВаш персональный менеджер: " . $mannager . "\n" . $phoneManager
      ]);

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, 'https://wappi.pro/api/sync/message/send?profile_id=19b40dc2-c947');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $message_json);

      $headers = array();
      $headers[] = 'Accept: application/json';
      $headers[] = 'Authorization: 1a2bcf0447631c68dd7ab70d5af7209539231e6f';
      $headers[] = 'Content-Type: application/json';
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

      $result = curl_exec($ch);

      $result = null;

      $message_json = null;

      $message_json = json_encode([
        "url" => $card,
        "recipient" => $phone
      ]);

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, 'https://wappi.pro/api/sync/message/file/url/send?profile_id=19b40dc2-c947');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $message_json);

      $headers = array();
      $headers[] = 'Accept: application/json';
      $headers[] = 'Authorization: 1a2bcf0447631c68dd7ab70d5af7209539231e6f';
      $headers[] = 'Content-Type: application/json';
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

      $result = curl_exec($ch);
    }

    return false;
  }
}