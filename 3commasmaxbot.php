<?php

  /***
   * 3commas max bot per Account
   * @author   xyvran@nwan.de
   * @version  0.5 20201216
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

  function is_blacklisted($aBot, $aBlacklist) {
    $returnvalue = false;
    if ($aBlacklist != '') {
      if (isset($aBot['name'])) {
        $returnvalue = preg_match(sprintf("/%s/", $aBlacklist), $aBot['name']) == 1;
      }
    }
    return $returnvalue;
  }

  $commas = new threecommasapi;
  $commas->setConfig($config);

  foreach ($config['accounts'] as $account) {

    $disableall = false;
    if (isset($account['usd_amount_min']) && $account['usd_amount_min'] > 0) {
      $commas->DebugOutput(sprintf('[%s] Looking for account...', $account['name']), 2);
      $accoutdetails = $commas->get(sprintf("/ver1/accounts/%s", $account['3commasid']), 'GET');
      if (isset($accoutdetails['usd_amount'])) {
        if ($accoutdetails['usd_amount'] < $account['usd_amount_min']) {
          $commas->DebugOutput(sprintf('[%s] Disable all bots. Only %.2f$ from min %.2f$ left', $account['name'], $accoutdetails['usd_amount'], $account['usd_amount_min']));
          $disableall = true;
        }
      }
    }

    $botblacklist = '';
    if (isset($account['blacklist'])) {
      $botblacklist = $account['blacklist'];
    }

    $deals = array();
    if (isset($account['ignoreactivatedttp']) && $account['ignoreactivatedttp']) {
      $commas->DebugOutput(sprintf('[%s] Looking for deals...', $account['name']), 2);
      $DealsParams = array();
      $DealsParams['account_id'] = $account['3commasid'];
      $DealsParams['scope'] = 'active';
      $deals = $commas->getdealsV1($DealsParams);
    }

    $commas->DebugOutput(sprintf('[%s] Looking for bots...', $account['name']), 2);

    $postdata['account_id'] = $account['3commasid'];
    // (Permission: BOTS_READ, Security: SIGNED)
    $url = sprintf("/ver1/bots");
    $botsdata = $commas->postv2($url, $postdata, 'GET');

    $counter['long'] = 0;
    $counter['short'] = 0;
    foreach ($botsdata as $bot) {
      if (isset($account['ignoreactivatedttp']) && $account['ignoreactivatedttp']) {
        $activedeal = $commas->getActiveDealFromBot($bot, $deals);
        if (isset($activedeal)) {
          if ($activedeal['status'] == 'ttp_activated') {
            continue;
          }
        }
      }
      if (!is_blacklisted($bot, $botblacklist)) {
        if ($bot['active_deals_count'] == 1) {
          if ($bot['strategy'] == 'long') {
            $counter['long']++;
          } else {
            $counter['short']++;
          }
        }
      }

      // Panic sell option
      if (isset($account['panicsellaftermissedtrailing']) && $account['panicsellaftermissedtrailing']) {
        if (!is_blacklisted($bot, $botblacklist)) {
          $activedeal = $commas->getActiveDealFromBot($bot, $deals);
          if (isset($activedeal)) {
            $commas->DebugOutput(sprintf('[%s] Panic sell check deal id %d...', $account['name'], $activedeal['id']), 2);
            if ($activedeal['active_safety_orders_count'] == 0
              && $activedeal['max_safety_orders'] > $activedeal['completed_safety_orders_count']
              && $activedeal['status'] == 'bought') {
              $commas->DebugOutput(sprintf('[%s] Panic sell deal id: %d  bot id: %d', $account['name'], $activedeal['id'], $bot['id']), 1);
              $commas->DealPanicSell($activedeal);
            }
          }
        }
      }

    }

    $max_active_deals = 999;
    $max_active_deals_long = 999;
    $max_active_deals_short = 999;
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

    $commas->DebugOutput(sprintf('[%s] Looking for bot status...', $account['name']), 2);
    foreach ($botsdata as $bot) {
      if (!is_blacklisted($bot, $botblacklist)) {
        $setstate = false;
        if ($disableall == false) {
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
        }

        if ($bot['is_enabled'] != $setstate) {
          $commas->DebugOutput(sprintf('[%s] %s %s Bot id %s.',
            $account['name'],
            ($bot['strategy'] == 'long' ? 'Long ' : 'Short'),
            ($setstate ? 'Enable ' : 'Disable'),
            $bot['id']), 2);

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
  }
  $commas->DebugOutput('END!', 2);
