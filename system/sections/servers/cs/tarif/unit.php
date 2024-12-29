<?php

/*
 * Copyright 2018-2024 Solovev Sergei
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

if (!defined('EGP')) {
    exit(header('Refresh: 0; URL=http://' . $_SERVER['HTTP_HOST'] . '/404'));
}

if (!isset($nmch)) {
    $nmch = false;
}

$uid = isset($url['uid']) ? sys::int($url['uid']) : sys::outjs(['e' => 'Переданы не все данные.'], $nmch);

if (!$cfg['change_unit'][$server['game']] || $server['time'] < $start_point + 86400 || $server['test']) {
    exit;
}

$sql->query('SELECT `id`, `unit`, `packs`, `fps`, `price` FROM `tarifs` WHERE `unit`="' . $uid . '" AND `game`="' . $server['game'] . '" AND `name`="' . $tarif['name'] . '" AND `id`!="' . $server['tarif'] . '" AND `show`="1" ORDER BY `unit`');
if (!$sql->num()) {
    sys::outjs(['e' => 'Не найден подходящий тариф.'], $nmch);
}

$oldTarif = $tarif;

$tarif = $sql->get();

$sql->query('SELECT `address`, `passwd`, `sql_login`, `sql_passwd`, `sql_port`, `sql_ftp` FROM `units` WHERE `id`="' . $server['unit'] . '" LIMIT 1');
$oldUnit = $sql->get();

$aPriceold = explode(':', $oldTarif['price']);
$aFPSold = explode(':', $oldTarif['fps']);

$sql->query('SELECT `id` FROM `units` WHERE `id`="' . $tarif['unit'] . '" AND `show`="1" LIMIT 1');
if (!$sql->num()) {
    sys::outjs(['e' => 'Выбранная локация не доступна.'], $nmch);
}

$aPrice = explode(':', $tarif['price']);
$aFPS = explode(':', $tarif['fps']);

if (!in_array($server['fps'], $aFPS)) {
    sys::outjs(['e' => 'Не найден подходящий тарифный план.'], $nmch);
}

// Цена за 1 день (при новом тарифном плане)
$price = $aPrice[array_search($server['fps'], $aFPS)] / 30 * $server['slots'];

// Цена аренды за остаток дней (с текущим тарифным планом)
$oldprice = ($server['time'] - $start_point) / 86400 * ($aPriceold[array_search($server['fps'], $aFPSold)] / 30 * $server['slots']);

$date = date('H.i.s.d.m.Y', round($start_point + $oldprice / $price * 86400 - 86400));

$aDate = explode('.', $date);

$time = mktime($aDate[0], $aDate[1], $aDate[2], $aDate[4], $aDate[3], $aDate[5]);

include(SEC . 'servers/games/tarif/unit.php');
