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

$sql->query('SELECT `uid`, `address`, `game`, `status` FROM `control_servers` WHERE `id`="' . $sid . '" LIMIT 1');
$server = $sql->get();

ctrl::nav($server, $id, $sid, 'plugins');

include(ctrl::route($server, 'plugins', $go));
