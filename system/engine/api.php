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

$key = $url['key'] ?? sys::outjs(['e' => 'ключ не указан']);
$action = $url['action'] ?? sys::outjs(['e' => 'метод не указан']);

if (sys::valid($key, 'md5')) {
    sys::outjs(['e' => 'ключ имеет неправильный формат']);
}

$sql->query('SELECT `id`, `server` FROM `api` WHERE `key`="' . $key . '" LIMIT 1');
if (!$sql->num()) {
    sys::outjs(['e' => 'ключ не найден']);
}

$api = $sql->get();

$id = $api['server'];

include(LIB . 'games/games.php');
include(LIB . 'api.php');

if (in_array($action, ['start', 'restart', 'stop', 'change', 'reinstall', 'update'])) {
    $sql->query('SELECT `id` FROM `servers` WHERE `id`="' . $id . '" LIMIT 1');
    if (!$sql->num()) {
        sys::outjs(['e' => 'сервер не найден']);
    }

    include(SEC . 'servers/action.php');
}

switch ($action) {
    case 'data':
        sys::outjs(api::data($id));

        // no break
    case 'load':
        sys::outjs(api::load($id));

        // no break
    case 'console':
        $cmd = $url['command'] ?? false;
        sys::outjs(api::console($id, $cmd));
}

sys::outjs(['e' => 'Метод не найден']);
