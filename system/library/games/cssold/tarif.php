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

class tarif extends tarifs
{
    public static function extend($options, $server, $tarif_name, $sid)
    {
        global $cfg, $sql, $html, $start_point;

        tarifs::extend_address($server['game'], $sid);

        $html->get('extend', 'sections/servers/games/tarif');

        if (isset($html->arr['extend_address'])) {
            $html->unit('extend_address', 1);
            $html->set('extend_address', $html->arr['extend_address']);
        } else {
            $html->unit('extend_address');
        }

        $html->set('id', $sid);
        $html->set('time', System::date('min', $server['time']));
        $html->set('options', '<option value="0">Выберите период продления</option>' . $options);
        $html->set('slots', $server['slots']);
        $html->set('info', $server['tickrate'] . ' TickRate / ' . $server['fps'] . ' FPS');
        $html->set('tarif', $tarif_name);
        $html->set('cur', $cfg['currency']);

        $html->pack('main');

        return null;
    }

    public static function extend_sp($server, $tarif, $sid)
    {
        global $cfg, $sql, $html, $start_point;

        tarifs::extend_address($server['game'], $sid);

        $aPrice = System::b64djs($tarif['price']);

        $sum = $tarif['slots'] ? $aPrice[$server['tickrate'] . '_' . $server['fps']] : $aPrice[$server['tickrate'] . '_' . $server['fps']] * $server['slots'];

        $html->get('extend_sp', 'sections/servers/games/tarif');

        if (isset($html->arr['extend_address'])) {
            $html->unit('extend_address', 1);
            $html->set('extend_address', $html->arr['extend_address']);
        } else {
            $html->unit('extend_address');
        }

        $html->set('id', $sid);
        $html->set('time', System::date('min', $server['time']));
        $html->set('date', $server['time'] > $start_point ? 'Сервер продлен до: ' . date('d.m.Y', $server['time']) : 'Текущая дата: ' . date('d.m.Y', $start_point));
        $html->set('options', '<option value="0">Выберите период продления</option>' . $options);
        $html->set('slots', $server['slots']);
        $html->set('info', $server['tickrate'] . ' TickRate / ' . $server['fps'] . ' FPS');
        $html->set('tarif', $tarif['name']);
        $html->set('sum', $sum);
        $html->set('cur', $cfg['currency']);

        $html->pack('main');

        return null;
    }

    public static function plan($server, $tarif_name, $sid)
    {
        global $cfg, $sql, $html;

        $sql->query('SELECT `fps`, `tickrate`, `price` FROM `tarifs` WHERE `id`="' . $server['tarif'] . '" LIMIT 1');

        if (!$sql->num()) {
            return null;
        }

        $tarif = $sql->get();

        $options = '';

        $aPrice = System::b64djs($tarif['price']);

        unset($aPrice[$server['tickrate'] . '_' . $server['fps']]);

        foreach ($aPrice as $param => $price) {
            [$tickrate, $fps] = explode('_', $param);

            $options .= '<option value="' . $param . '">'
                . $tickrate . ' TickRate / '
                . $fps . ' FPS '
                . '(' . $price . ' ' . $cfg['currency'] . '/слот | '
                . ($price * $server['slots']) . ' ' . $cfg['currency'] . '/месяц)'
                . '</option>';
        }

        if ($options == '') {
            return null;
        }

        $html->get('plan', 'sections/servers/games/tarif');

        $html->set('id', $sid);
        $html->set('options', '<option value="0">Выберите тарифный план</option>' . $options);
        $html->set('info', $server['tickrate'] . ' TickRate / ' . $server['fps'] . ' FPS');
        $html->set('tarif', $tarif_name);

        $html->pack('main');

        return null;
    }

    public static function unit($server, $unit_name, $tarif_name, $sid)
    {
        global $cfg, $sql, $html;

        if (!$cfg['change_unit'][$server['game']]) {
            return null;
        }

        $tarifs = $sql->query('SELECT `unit`, `fps`, `tickrate`, `price` FROM `tarifs` WHERE `game`="' . $server['game'] . '" AND `name`="' . $tarif_name . '" AND `id`!="' . $server['tarif'] . '" AND `show`="1" ORDER BY `unit`');
        if (!$sql->num($tarifs)) {
            return null;
        }

        $units = 0;

        $options = '<option value="0">Выберите новую локацию</option>';

        while ($tarif = $sql->get($tarifs)) {
            if (!array_key_exists($server['tickrate'] . '_' . $server['fps'], System::b64djs($tarif['price']))) {
                continue;
            }

            $sql->query('SELECT `id`, `name` FROM `units` WHERE `id`="' . $tarif['unit'] . '" AND `show`="1" LIMIT 1');
            if (!$sql->num()) {
                continue;
            }

            $unit = $sql->get();

            $options .= '<option value="' . $unit['id'] . '">' . $unit['name'] . '</option>';

            $units += 1;
        }

        if (!$units) {
            return null;
        }

        $html->get('unit', 'sections/servers/games/tarif');

        $html->set('id', $sid);
        $html->set('options', $options);
        $html->set('slots', $server['slots']);
        $html->set('info', $server['tickrate'] . ' TickRate / ' . $server['fps'] . ' FPS');
        $html->set('unit', $unit_name);
        $html->set('tarif', $tarif_name);

        $html->pack('main');

        return null;
    }

    public static function unit_new($tarif, $unit, $server, $mcache)
    {
        global $ssh, $sql, $user, $start_point;

        // Проверка ssh соединения с локацией
        if (!$ssh->auth($unit['passwd'], $unit['address'])) {
            System::outjs(['e' => System::text('error', 'ssh')]);
        }

        // Директория сборки
        $path = $tarif['path'] . $tarif['pack'];

        // Директория игрового сервера
        $install = $tarif['install'] . $server['uid'];

        // Пользователь сервера
        $uS = 'server' . $server['uid'];

        $ssh->set('mkdir ' . $install . ';' // Создание директории
            . 'useradd -d ' . $install . ' -g servers -u ' . $server['uid'] . ' ' . $uS . ';' // Создание пользователя сервера на локации
            . 'chown ' . $uS . ':servers ' . $install . ';' // Изменение владельца и группы директории
            . 'cd ' . $install . ' && sudo -u ' . $uS . ' tmux new-session -ds i_' . $server['uid'] . ' cp -r ' . $path . '/. .'); // Копирование файлов сборки для сервера

        // Обновление данных нового сервера
        $sql->query('UPDATE `servers` set
				`unit`="' . $tarif['unit'] . '",
				`tarif`="' . $tarif['id'] . '",
				`address`="' . $server['address'] . '",
				`port`="' . $server['port'] . '",
				`status`="install",
				`name`="' . $tarif['hostname'] . '",
				`pack`="' . $tarif['pack'] . '",
				`map_start`="' . $tarif['map'] . '",
				`vac`="1",
				`hdd`="' . $tarif['hdd'] . '",
				`time`="' . $tarif['time'] . '",
				`autostop`="' . $tarif['autostop'] . '",
				`reinstall`="' . $start_point . '" WHERE `id`="' . $server['id'] . '" LIMIT 1');

        // Запись установленных плагинов
        if ($tarif['plugins']) {
            // Массив идентификаторов плагинов
            $aPlugins = System::b64js($tarif['plugins_install']);

            if (isset($aPlugins[$tarif['pack']])) {
                $plugins = explode(',', $aPlugins[$tarif['pack']]);

                foreach ($plugins as $plugin) {
                    if ($plugin) {
                        $sql->query('INSERT INTO `plugins_install` set `server`="' . $server['id'] . '", `plugin`="' . $plugin . '", `time`="' . $start_point . '"');
                    }
                }
            }
        }

        return null;
    }

    public static function price($plan)
    {
        $aPrice = array_values(System::b64djs($plan));

        $check = $aPrice[0];

        unset($aPrice[0]);

        if (!count($aPrice)) {
            return false;
        }

        foreach ($aPrice as $price) {
            if ($check != $price) {
                return true;
            }
        }

        return false;
    }
}
