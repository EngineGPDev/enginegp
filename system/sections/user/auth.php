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

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!defined('EGP')) {
    exit(header('Refresh: 0; URL=http://' . $_SERVER['HTTP_HOST'] . '/404'));
}

// Проверка на авторизацию
sys::auth();

// Генерация новой капчи
if (isset($url['captcha'])) {
    sys::captcha('auth', $uip);
}

// Авторизация
if ($go) {
    $nmch = 'go_auth_' . $uip;

    if ($mcache->get($nmch)) {
        sys::outjs(['e' => sys::text('other', 'mcache')], $nmch);
    }

    $mcache->set($nmch, 1, false, 15);

    // Проверка капчи
    if (!isset($_POST['captcha']) || sys::captcha_check('auth', $uip, $_POST['captcha'])) {
        sys::outjs(['e' => sys::text('other', 'captcha')], $nmch);
    }

    $aData = [];

    $aData['login'] = $_POST['login'] ?? '';
    $aData['passwd'] = $_POST['passwd'] ?? '';

    // Проверка входных данных
    foreach ($aData as $val) {
        if ($val == '') {
            sys::outjs(['e' => sys::text('input', 'all')], $nmch);
        }
    }

    // Проверка логина/почты на валидность
    if (sys::valid($aData['login'], 'other', $aValid['mail']) and sys::valid($aData['login'], 'other', $aValid['login'])) {
        $out = 'login';

        // Если в логине указана почта
        if (sys::ismail($aData['login'])) {
            $out = 'mail';
        }

        sys::outjs(['e' => sys::text('input', $out . '_valid')], $nmch);
    }

    $sql_q = '`login`';

    // Если в логине указана почта
    if (sys::ismail($aData['login'])) {
        $sql_q = '`mail`';
    }

    // Проверка существования пользователя
    $sql->query('SELECT `id`, `login`, `mail`, `security_ip`, `security_code`, `passwd` FROM `users` WHERE ' . $sql_q . '="' . $aData['login'] . '" LIMIT 1');
    if (!$sql->num()) {
        sys::outjs(['e' => sys::text('input', 'auth')], $nmch);
    }

    $user = $sql->get();

    // Проверка пароля
    if (!sys::passwdverify($aData['passwd'], $user['passwd'])) {
        sys::outjs(['e' => sys::text('input', 'auth')], $nmch);
    }

    $subnetwork = sys::whois($uip);

    // Если включена защита по ip
    if ($user['security_ip']) {
        $sql->query('SELECT `id` FROM `security` WHERE `user`="' . $user['id'] . '" AND `address`="' . $uip . '" LIMIT 1');

        if (!$sql->num()) {
            if ($subnetwork != 'не определена') {
                $sql->query('SELECT `id` FROM `security` WHERE `user`="' . $user['id'] . '" AND `address`="' . $subnetwork . '" LIMIT 1');

                if (!$sql->num()) {
                    sys::outjs(['e' => 'Ваш ip адрес не найден в числе указаных адресов для авторизации.'], $nmch);
                }
            } else {
                sys::outjs(['e' => 'Ваш ip адрес не найден в числе указаных адресов для авторизации.'], $nmch);
            }
        }
    }

    // Если включена защита по коду
    if ($user['security_code']) {
        $code = $_POST['code'] ?? '';

        if ($code == '' || $code != $mcache->get('auth_code_security_' . $user['id'])) {
            $ncod = sys::code();

            // Отправка сообщения на почту
            if (sys::mail('Авторизация', sys::updtext(sys::text('mail', 'security_code'), ['site' => $cfg['name'], 'code' => $ncod]), $user['mail'])) {
                $mcache->set('auth_code_security_' . $user['id'], $ncod, false, 180);

                if ($code == '') {
                    sys::outjs(['i' => 'На вашу почту отправлено письмо с кодом подтверждения.', 'mail' => sys::mail_domain($user['mail'])], $nmch);
                }

                sys::outjs(['i' => 'На вашу почту отправлено письмо с кодом подтверждения снова.', 'mail' => sys::mail_domain($user['mail'])], $nmch);
            }

            // Выхлоп: не удалось отправить письмо
            sys::outjs(['e' => sys::text('error', 'mail')], $nmch);
        }
    }

    $_SERVER['HTTP_USER_AGENT'] = mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 200);

    // Обновление информации о пользователе
    $sql->query('UPDATE `users` set `ip`="' . $uip . '", `browser`="' . sys::browser($_SERVER['HTTP_USER_AGENT']) . '", `time`="' . $start_point . '" WHERE `id`="' . $user['id'] . '" LIMIT 1');

    // Логирование ip
    $sql->query('INSERT INTO `auth` set `user`="' . $user['id'] . '", `ip`="' . $uip . '", `date`="' . $start_point . '", `browser`="' . sys::hb64($_SERVER['HTTP_USER_AGENT']) . '"');

    // Вход успешен, создаем JWT токен
    $payload = [
        'id' => $user['id'],
        'iat' => $start_point,
        'exp' => $start_point + 86400 * 30,
    ];

    // Генерация JWT токена
    $refreshToken = JWT::encode($payload, $_ENV['JWT_KEY'], 'HS256');

    // Установка токена в куки
    setcookie('refresh_token', $refreshToken, [
        'expires' => $start_point + 86400 * 30,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'samesite' => 'Strict',
    ]);

    // // Выхлоп удачной авторизации
    sys::outjs(['s' => 'ok'], $nmch);
}

$html->get('auth', 'sections/user');
$html->pack('main');
