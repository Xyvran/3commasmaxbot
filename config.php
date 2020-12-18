<?php
  /***
   * 3commas config
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
   */

  // Show some more ;)
  // 0  = cronjob / quite
  // 1  = some information
  // 2  = some more output
  // 10 = curl debug
  $config['system']['debug'] = 1;

  // Your 3commas API Key:
  $config['3commas']['apiKey'] = 'yourkey';
  // Your 3commas API Secret Key:
  $config['3commas']['secretKey'] = 'yourSecret';

  // --- Account 1 ---
  // Account Name
  $account['name'] = 'Binance Future Account 1';
  // 3Commas Account ID
  $account['3commasid'] = 12345678;
  // Total Max Deals (Long + Short)
  $account['max_active_deals'] = 15;
  // Max Long Deals
  $account['max_active_deals_long'] = 10;
  // Max Short Deals
  $account['max_active_deals_short'] = 8;
  // Blacklist, to ignore Bots (regex)
  $account['blacklist'] = 'BTC';
  // Ignore activated TTP bots
  $account['ignoreactivatedttp'] = true;
  // USD minimum amount on account
  // 0 = disabled
  $account['usd_amount_min'] = 250;
  // Panic sell after missed trailing in 3commas
  // Something the trailing don't hit and reverted to "initial status". But there are no SO active anymore. Now we can
  // panic sell. It's a market sell that hit the last SO if not all SOs were placed.
  // Error Message from 3commas:
  // The bot was unable to close the deal as the price moved below the profitable area too fast. The trailing has been disabled and the deal reverted to the initial status, now it is waiting for trailing activation condition again.
  $account['panicsellaftermissedtrailing'] = false;

  $config['accounts'][] = $account;
  unset($account);

  // --- Account 2 ---
  $account['name'] = 'Binance Future Account 2';
  $account['3commasid'] = 87654321;
  $account['max_active_deals'] = 20;
  $account['max_active_deals_long'] = 12;
  $account['max_active_deals_short'] = 14;

  $config['accounts'][] = $account;
  unset($account);

  // --- Account 3 ---
  $account['name'] = 'FTX Account 3';
  $account['3commasid'] = 12341234;
  $account['max_active_deals'] = 8;
  $account['max_active_deals_long'] = 8;
  $account['max_active_deals_short'] = 8;

  $config['accounts'][] = $account;
  unset($account);
