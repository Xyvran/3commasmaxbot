<?php

  /***
   * Class threecommasapi
   * @author   xyvran@nwan.de
   * @version  0.3 20201214
   * @donation BTC      1N2HJBrcjRgRh1e3hEuG1s3JT4TwHENvoE
   *           USDT     TFTkHHAwZqy6XemHWXtALWFgPWv8GyuGFA (TRC20)
   *           BTC/USDT 0xf02490bad03a17753b38c3e8acccf8a70f4fcd22 (ERC20)
   * @telegram @Xyvran
   * @github   https://github.com/Xyvran/3commasmaxbot
   */

  class threecommasapi {
    private $apientryendpoint = "https://api.3commas.io";
    private $apientryendpointurl = "/public/api";
    private $debug = 0;

    private $apiKey = "";
    private $secretKey = "";

    function setConfig($aConfig) {
      if (!isset($aConfig['3commas'])) {
        printf("3commas section not found\n");
        exit;
      }
      if (!isset($aConfig['3commas']['apiKey'])) {
        printf("apiKey not set\n");
        exit;
      }
      if (!isset($aConfig['3commas']['secretKey'])) {
        printf("apiKey not set\n");
        exit;
      }
      $this->apiKey = $aConfig['3commas']['apiKey'];
      $this->secretKey = $aConfig['3commas']['secretKey'];

      if (isset($aConfig['system']) && isset($aConfig['system']['debug'])) {
        $this->debug = $aConfig['system']['debug'];
      }

    }

    static function isExtensionLoaded($aextension_name){
      return extension_loaded($aextension_name);
    }

    function __construct() {
      if (!self::isExtensionLoaded('curl')) {
        echo "Curl extension not found.\n";
        exit;
      }
    }

    function DebugOutput($data, $level = 0) {
      if ($this->debug > $level) {
        printf("%s - %s\n", date("Y-m-d H:i:s"), $data);
      }
    }

    function HMACSHA256($aQuery) {
      $signature = hash_hmac('sha256', $this->apientryendpointurl . $aQuery, $this->secretKey);

      return $signature;
    }

    function check3CommasError($data) {
      if (preg_match('/"error":"api_key_invalid_or_expired"/s', $data)) {
        printf("Curl API Key Error: %s!\n", $data);
        exit;
      }
    }

    function get($aParam, $aMethod = 'GET', $aReturn = 'JSON') {
      $signature = $this->HMACSHA256($aParam);

      $this->DebugOutput(sprintf("curl -H \"APIKEY: %s\" -H \"Signature: %s\" -X %s '%s%s%s'", $this->apiKey, $signature, $aMethod, $this->apientryendpoint, $this->apientryendpointurl, $aParam), 10);

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, sprintf("%s%s%s", $this->apientryendpoint, $this->apientryendpointurl, $aParam));
      if ($aMethod == "POST") {
        curl_setopt($ch, CURLOPT_POST, 1);
      }
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $headers[] = sprintf("APIKEY: %s", $this->apiKey);
      $headers[] = sprintf("Signature: %s", $signature);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      $data = curl_exec($ch);
      curl_close($ch);

      $this->check3CommasError($data);

      if ($aReturn == 'RAW') {
        return $data;
      } else {
        return json_decode($data, true);
      }
    }

    function postv2($aURL, $aParam, $aMethod = 'POST', $aReturn = 'JSON') {

      $ch = curl_init();
      $aParam_string = "";
      if ($aMethod == 'GET') {
        foreach ($aParam as $key => $value) {
          if ($aParam_string != '') {
            $aParam_string .= '&';
          }
          $aParam_string .= $key . '=' . $value;
        }
        if ($aParam_string != '') {
          $aParam_string = '?' . $aParam_string;
        }

      }

      curl_setopt($ch, CURLOPT_URL, sprintf("%s%s%s%s", $this->apientryendpoint, $this->apientryendpointurl, $aURL, $aParam_string));
      curl_setopt($ch, CURLOPT_POST, 1);

      if ($aMethod != 'POST') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $aMethod);
      }

      if ($aMethod != 'GET') {
        $jsonDataEncoded = json_encode($aParam);
      } else {
        $jsonDataEncoded = $aParam_string;
      }
      $signature = $this->HMACSHA256($aURL . $jsonDataEncoded);
      if ($aMethod != 'GET') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);  //Post Fields
      }

      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $headers[] = sprintf("APIKEY: %s", $this->apiKey);
      $headers[] = sprintf("Signature: %s", $signature);
      if ($aMethod != 'GET') {
        $headers[] = sprintf("Content-Type: application/json");
        $headers[] = sprintf("Content-Length: " . strlen($jsonDataEncoded));
      }
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

      $data = curl_exec($ch);
      curl_close($ch);

      $this->check3CommasError($data);

      if ($aReturn == 'RAW') {
        return $data;
      } else {
        return json_decode($data, true);
      }
    }

    function currency_rates($market_code, $pair) {
      return $this->get(sprintf("/ver1/accounts/currency_rates?market_code=%s&pair=%s", $market_code, $pair));
    }

    function account_table_data($account, $currency_code = '') {
      $data = $this->get(sprintf("/ver1/accounts/%s/account_table_data", $account), 'POST');
      if ($currency_code == '') {
        return $data;
      } else {
        foreach ($data as $key) {
          if ($key['currency_code'] == $currency_code) {
            return $key;
          }
        }

        return null;
      }
    }


    function getdealV1($account_id, $deal_id, $scope = 'any') {
      if ($deal_id == null) {
        $data = $this->get(sprintf("/ver1/deals?account_id=%s&scope=%s&order=closed_at&limit=200", $account_id, $scope));

        return $data;
      } else {
        $data = $this->get(sprintf("/ver1/deals?account_id=%s&scope=%s&order=closed_at", $account_id, $scope));
      }
      foreach ($data as $key) {
        if ($key['id'] == $deal_id) {
          return $key;
        }
      }

      return null;
    }

    function getdealsV1($aParams) {
      $searchstring = '';
      if (isset($aParams['limit'])) {
        if ($searchstring != '') $searchstring .= '&';
        $searchstring .= sprintf('limit=%d', $aParams['limit']);
      }
      if (isset($aParams['offset'])) {
        if ($searchstring != '') $searchstring .= '&';
        $searchstring .= sprintf('offset=%d', $aParams['offset']);
      }
      if (isset($aParams['account_id'])) {
        if ($searchstring != '') $searchstring .= '&';
        $searchstring .= sprintf('account_id=%d', $aParams['account_id']);
      }
      if (isset($aParams['bot_id'])) {
        if ($searchstring != '') $searchstring .= '&';
        $searchstring .= sprintf('bot_id=%d', $aParams['bot_id']);
      }
      if (isset($aParams['scope'])) {
        if ($searchstring != '') $searchstring .= '&';
        $searchstring .= sprintf('scope=%s', $aParams['scope']);
      }
      if (isset($aParams['order'])) {
        if ($searchstring != '') $searchstring .= '&';
        $searchstring .= sprintf('order=%s', $aParams['order']);
      }

      return($this->get(sprintf("/ver1/deals?%s", $searchstring)));
    }

    function getActiveDealFromBot($aBot, $aDeals = null) {
      assert(!isset($aBot));
      assert(!isset($aBot['id']));

      if ($aDeals == null) {
        $params['bot_id'] = $aBot['id'];
        $aDeals[] = $this->getdealsV1($params);
      }

      if (isset($aDeals[0]) && isset($aDeals[0]['id'])) {
        foreach ($aDeals as $deal) {
          if ($deal['bot_id'] == $aBot['id']) {
            return $deal;
          }
        }
      }
      return null;
    }

    function getAccountData($account_id) {
      $return = $this->get(sprintf("/ver1/accounts/%s/account_table_data", $account_id), 'POST');

      return $return;
    }
  }
