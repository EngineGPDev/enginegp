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

// Если фикс. значение слот
if ($tarif['slots_min'] == $tarif['slots_max']) {
    sys::outjs(['e' => 'На данном тарифе нельзя изменить количество слот.'], $nmch);
}

$slots = isset($url['slots']) ? sys::int($url['slots']) : sys::outjs(['e' => 'Переданы не все данные.'], $nmch);

$aPrice = explode(':', $tarif['price']);
$aFPS = explode(':', $tarif['fps']);

$overdue = $server['time'] < $start_point;

if ($cfg['change_slots'][$server['game']]['days'] || $overdue) {
    // Цена за 1 день 1 слота
    $price = $aPrice[array_search($server['fps'], $aFPS)] / 30;

    // Цена аренды за остаток дней (с текущим кол-вом слот)
    $price_old = ($server['time'] - $start_point) / 86400 * $price * $server['slots'];
}

$max = $tarif['slots_max'] - $server['slots'];

// Сумма за добавляемые слоты
$sum = round(($server['time'] - $start_point) / 86400 * ($aPrice[array_search($server['fps'], $aFPS)] / 30) * $slots, 2);

include(SEC . 'servers/games/tarif/slots.php');
