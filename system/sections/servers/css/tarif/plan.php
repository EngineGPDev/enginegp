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

use EngineGP\System;

if (!defined('EGP')) {
    exit(header('Refresh: 0; URL=http://' . $_SERVER['HTTP_HOST'] . '/404'));
}

if (!isset($nmch)) {
    $nmch = false;
}

$plan = isset($url['plan']) ? System::int($url['plan']) : System::outjs(['e' => 'Переданые не все данные'], $nmch);

$aPrice = explode(':', $tarif['price']);
$aTICK = explode(':', $tarif['tickrate']);

// Проверка плана
if (array_search($plan, $aTICK) === false) {
    System::outjs(['e' => 'Переданы неверные данные'], $nmch);
}

if ($plan == $server['tickrate']) {
    System::outjs(['e' => 'Смысла в этой операции нет'], $nmch);
}

if (!tarif::price($tarif['price'])) {
    System::outjs(['e' => 'Чтобы изменить тариф, перейдите в настройки запуска'], $nmch);
}

if ($server['time'] < $start_point + 86400) {
    $time = $server['time'];
} else {
    // Цена за 1 день аренды (по новому тарифному плану)
    $price = $aPrice[array_search($plan, $aTICK)] / 30 * $server['slots'];

    // Цена за 1 день аренды (по старому тарифному плану)
    $price_old = $aPrice[array_search($server['tickrate'], $aTICK)] / 30 * $server['slots'];

    // Остаток дней аренды
    $days = ($server['time'] - $start_point) / 86400;

    $time = date('H:i:s', $server['time']);
    $date = date('d.m.Y', round($start_point + $days * $price_old / $price * 86400 - 86400));

    $aDate = explode('.', $date);
    $aTime = explode(':', $time);

    $time = mktime($aTime[0], $aTime[1], $aTime[2], $aDate[1], $aDate[0], $aDate[2]);
}

// Выполнение смена тарифного плана
if ($go) {
    System::benefitblock($id, $nmch);

    $sql->query('UPDATE `servers` set `time`="' . $time . '", `tickrate`="' . $plan . '" WHERE `id`="' . $id . '" LIMIT 1');

    if (in_array($server['status'], ['working', 'start', 'restart', 'change'])) {
        include(LIB . 'games/' . $server['game'] . '/action.php');

        action::start($id, 'restart');
    }

    // Запись логов
    $sql->query('INSERT INTO `logs_sys` set `user`="' . $user['id'] . '", `server`="' . $id . '", `text`="' . System::text('syslogs', 'change_plan') . '", `time`="' . $start_point . '"');

    System::outjs(['s' => 'ok'], $nmch);
}

// Выхлоп информации
System::outjs(['s' => date('d.m.Y - H:i', $time) . ' (' . sys::date('min', $time) . ')'], $nmch);
