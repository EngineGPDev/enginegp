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

if (!defined('EGP'))
    exit(header('Refresh: 0; URL=http://' . $_SERVER['HTTP_HOST'] . '/404'));

class ssh
{
    var $conn;
    var $stream;
    private $alternativeInterfaces = ['enp3s0', 'enp0s31f6', 'enp0s3', 'ens3', 'eth0'];

    public function auth($passwd, $address)
    {
        if ($this->connect($address) and $this->auth_pwd('root', $passwd))
            return true;

        return false;
    }

    public function connect($address)
    {
        if (strpos($address, ':') !== false) {
            list($host, $port) = explode(':', $address);
        } else {
            $host = $address;
            $port = 22;
        }

        ini_set('default_socket_timeout', '3');

        if ($this->conn = ssh2_connect($host, $port)) {
            ini_set('default_socket_timeout', '180');

            return true;
        }

        return false;
    }

    public function setfile($localFile, $remoteFile, $permision)
    {
        if (@ssh2_scp_send($this->conn, $localFile, $remoteFile, $permision))
            return true;

        return false;
    }

    public function getfile($remoteFile, $localFile)
    {
        if (@ssh2_scp_recv($this->conn, $remoteFile, $localFile))
            return true;

        return false;
    }

    public function set($cmd)
    {
        $this->stream = ssh2_exec($this->conn, $cmd);

        stream_set_blocking($this->stream, true);
    }

    public function auth_pwd($u, $p)
    {
        if (@ssh2_auth_password($this->conn, $u, $p))
            return true;

        return false;
    }

    public function get($cmd = false)
    {
        if ($cmd) {
            $this->stream = ssh2_exec($this->conn, $cmd);

            stream_set_blocking($this->stream, true);
        }

        $line = '';

        while ($get = fgets($this->stream))
            $line .= $get;

        return $line;
    }

    public function esc()
    {
        if (function_exists('ssh2_disconnect'))
            ssh2_disconnect($this->conn);
        else {
            @fclose($this->conn);
            unset($this->conn);
        }

        return NULL;
    }

    public function getInternalIp()
    {
        foreach ($this->alternativeInterfaces as $interface) {
            $command = "ip addr show $interface | grep 'inet ' | awk '{print $2}' | cut -d/ -f1";
            $internal_ip = $this->get($command);
            if (!empty(trim($internal_ip))) {
                return trim($internal_ip);
            }
        }
    }
}

$ssh = new ssh;
