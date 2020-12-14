<?php

  /***
   * 3commas max bot per Account
   * @author   xyvran@nwan.de
   * @version  0.4 20201214
   * @donation BTC      1N2HJBrcjRgRh1e3hEuG1s3JT4TwHENvoE
   *           USDT     TFTkHHAwZqy6XemHWXtALWFgPWv8GyuGFA (TRC20)
   *           BTC/USDT 0xf02490bad03a17753b38c3e8acccf8a70f4fcd22 (ERC20)
   * @telegram @Xyvran
   * @github   https://github.com/Xyvran/3commasmaxbot
   *
   * Need following api rights: BOTS_READ, BOTS_WRITE
   *
   * Start on console with:
   *   php ./3commasmaxbot.php
   */

  include_once './api/3commas.php';
  include_once './config.php';

  if (!isset($config)) {
    printf("Config not found\n");
    exit;
  }

  if (!isset($config['accounts'])) {
    printf("Account not set\n");
    exit;
  }

  $commas = new threecommasapi;
  $commas->setConfig($config);

  foreach ($config['accounts'] as $account) {

    $commas->DebugOutput(sprintf('[%s] Looking for bots...', $account['name']));
    $postdata['account_id'] = $account['3commasid'];
    // (Permission: BOTS_READ, Security: SIGNED)
    $url = sprintf("/ver1/bots");
    $botsdata = $commas->postv2($url, $postdata, 'GET');

    $counter['long'] = 0;
    $counter['short'] = 0;
    foreach ($botsdata as $bot) {
      if ($bot['active_deals_count'] == 1) {
        if ($bot['strategy'] == 'long') {
          $counter['long']++;
        } else {
          $counter['short']++;
        }
      }
    }

    $max_active_deals = 0;
    $max_active_deals_long = 0;
    $max_active_deals_short = 0;
    if (isset($account['max_active_deals']) && is_numeric($account['max_active_deals'])) {
      $max_active_deals = $account['max_active_deals'];
    }
    if (isset($account['max_active_deals_long']) && is_numeric($account['max_active_deals_long'])) {
      $max_active_deals_long = $account['max_active_deals_long'];
    }
    if (isset($account['max_active_deals_short']) && is_numeric($account['max_active_deals_short'])) {
      $max_active_deals_short = $account['max_active_deals_short'];
    }

    $commas->DebugOutput(sprintf('[%s] Active trading entities: %d (max %d)  Long: %d (max %d)  Short: %d (max %d)',
      $account['name'],
      $counter['long'] + $counter['short'], $max_active_deals,
      $counter['long'], $max_active_deals_long,
      $counter['short'], $max_active_deals_short));

    $commas->DebugOutput(sprintf('[%s] Looking for bots...', $account['name']));
    foreach ($botsdata as $bot) {

      $setstate = false;
      if ($counter['long'] + $counter['short'] < $max_active_deals) {
        if ($bot['strategy'] == 'long') {
          if ($counter['long'] < $max_active_deals_long) {
            $setstate = true;
          }
        } else {
          if ($counter['short'] < $max_active_deals_short) {
            $setstate = true;
          }
        }
      }

      $commas->DebugOutput(sprintf('[%s] %s %s Bot id %s.',
        $account['name'],
        ($bot['strategy'] == 'long' ? 'Long ' : 'Short'),
        ($setstate ? 'Enable ' : 'Disable'),
        $bot['id']));

      if ($bot['is_enabled'] != $setstate) {
        if ($setstate) {
          $url = sprintf("/ver1/bots/%s/enable", $bot['id']);
        } else {
          $url = sprintf("/ver1/bots/%s/disable", $bot['id']);
        }
        // Disable/Enable bot (Permission: BOTS_WRITE, Security: SIGNED)
        $commas->get($url, 'POST');
      }
    }
  }
  $commas->DebugOutput('END!');
