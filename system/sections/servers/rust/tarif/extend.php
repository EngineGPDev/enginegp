<?php

/*
 * Copyright 2018-2025 Solovev Sergei
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

$aData = [];

// Если не расчетный период
if (!$cfg['settlement_period']) {
    $aData['time'] = isset($_POST['time']) ? sys::int($_POST['time']) : sys::outjs(['e' => 'Переданы не все данные'], $nmch);

    // Проверка периода
    if (!in_array($aData['time'], explode(':', $tarif['timext']))) {
        sys::outjs(['e' => 'Переданы неверные данные'], $nmch);
    }

}

$aData['promo'] = $_POST['promo'] ?? '';
$aData['address'] = $_POST['address'] ?? false;
$aData['server'] = $id;
$aData['user'] = $server['user'];
$aData['tarif'] = $server['tarif'];
$aData['tickrate'] = $server['tickrate'];
$aData['slots'] = $server['slots'];

// Цена за выделенный адрес
$add_sum = tarifs::address_add_sum($aData['address'], $server);

$aPrice = explode(':', $tarif['price']);

// Цена за 30 дней 1 слота
$price = $aPrice[array_search($server['tickrate'], explode(':', $tarif['tickrate']))];

// Если расчетный период
if ($cfg['settlement_period']) {
    $aData['time'] = $server['time'];
}

// Цена аренды
$sum = games::define_sum($tarif['discount'], $price, $server['slots'], $aData['time'], 'extend') + $add_sum;

// Если расчетный период
if ($cfg['settlement_period']) {
    $aData['time'] = games::define_period('extend', params::$aDayMonth, $server['time']);
}

$days = params::$aDayMonth[date('n', $server['time'])] == $aData['time'] ? 'месяц' : games::parse_day($aData['time'], true);

include(SEC . 'servers/games/tarif/extend.php');
