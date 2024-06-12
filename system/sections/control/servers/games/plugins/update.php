<?php
/*
 * EngineGP   (https://enginegp.ru or https://enginegp.com)
 *
 * @copyright Copyright (c) 2018-present Solovev Sergei <inbox@seansolovev.ru>
 *
 * @link      https://github.com/EngineGPDev/EngineGP for the canonical source repository
 *
 * @license   https://github.com/EngineGPDev/EngineGP/blob/main/LICENSE MIT License
 */

if (!DEFINED('EGP'))
    exit(header('Refresh: 0; URL=http://' . $_SERVER['HTTP_HOST'] . '/404'));

if (!$go)
    exit();

$pid = isset($url['plugin']) ? sys::int($url['plugin']) : exit;

$sql->query('SELECT `id` FROM `plugins_update` WHERE `plugin`="' . $pid . '" ORDER BY `id` DESC LIMIT 1');

if (!$sql->num())
    exit();

$plugin = $sql->get();

// Проверка установки плагина
$sql->query('SELECT `id` FROM `control_plugins_install` WHERE `server`="' . $sid . '" AND `plugin`="' . $pid . '" LIMIT 1');
if (!$sql->num())
    exit();

// Проверка установки обновления плагина
$sql->query('SELECT `id` FROM `control_plugins_install` WHERE `server`="' . $sid . '" AND `plugin`="' . $pid . '" AND `upd`="' . $plugin['id'] . '" LIMIT 1');
if ($sql->num())
    sys::outjs(array('e' => 'Данный плагин уже обновлен'));

// Данные обновления
$sql->query('SELECT `id`, `cfg`, `incompatible`, `required` FROM `plugins_update` WHERE `id`="' . $plugin['id'] . '" LIMIT 1');

$plugin = $sql->get();

include(LIB . 'control/plugins.php');

// Проверка на наличие несовместимости с уже установленными плагинами
plugins::incompatible($sid, $plugin['incompatible'], $nmch);

// Проверка на наличие необходимых установленых плагинов для устанавливаемого плагина
plugins::required($sid, $plugin['required'], $nmch);

$sql->query('SELECT `address`, `passwd` FROM `control` WHERE `id`="' . $id . '" LIMIT 1');
$unit = $sql->get();

if (!isset($ssh))
    include(LIB . 'ssh.php');

if (!$ssh->auth($unit['passwd'], $unit['address']))
    sys::outjs(array('e' => sys::text('error', 'ssh')), $nmch);

// Директория игр. сервера
$dir = '/servers/' . $server['uid'] . '/';

// Установка файлов на сервер
$ssh->set('cd ' . $dir . ' && screen -dmS update_' . $start_point . ' sudo -u server' . $server['uid'] . ' sh -c "wget --no-check-certificate ' . $cfg['plugins'] . 'update/' . $plugin['id'] . '.zip && unzip -o ' . $plugin['id'] . '.zip; rm ' . $plugin['id'] . '.zip"');

// Удаление файлов
$sql->query('SELECT `file` FROM `plugins_delete` WHERE `update`="' . $plugin['id'] . '"');
while ($delete = $sql->get())
    $ssh->set('sudo -u server' . $server['uid'] . ' rm ' . $dir . $delete['file']);

unset($delete);

// Удаление текста из файлов
$sql->query('SELECT `text`, `file`, `regex` FROM `plugins_clear` WHERE `update`="' . $plugin['id'] . '"');
while ($clear = $sql->get())
    plugins::clear($clear, $server['uid'], $dir);

unset($clear);

// Добавление текста в файлы
$sql->query('SELECT `text`, `file`, `top` FROM `plugins_write` WHERE `update`="' . $plugin['id'] . '" ORDER BY `id` ASC');
while ($write = $sql->get())
    plugins::write($write, $server['uid'], $dir);

// Обновление данных в базе
$sql->query('UPDATE `control_plugins_install` set `upd`="' . $plugin['id'] . '", `time`="' . $start_point . '" WHERE `server`="' . $sid . '" AND `plugin`="' . $pid . '" LIMIT 1');

// Очистка кеша
$mcache->delete('server_plugins_' . $id);

if ($plugin['cfg'])
    sys::outjs(array('s' => 'cfg'), $nmch);

sys::outjs(array('s' => 'ok'), $nmch);
