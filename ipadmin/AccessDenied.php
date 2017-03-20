<?php

class AccessDenied
{
    public $redis = '';

    public function __construct($redis = '')
    {
        // Initialize the connection of Redis Server
        $this->redis = $redis;
    }

    /**
     * To judge whether the IP is disabled
     * @param $redis
     * @param $ip
     * @return boolean true indicates that the IP is disabled
     */
    public static function isBanIP($redis, $ip)
    {
        return $redis->sIsMember('ips:ban_list', $ip);
    }

    /**
     * Get the Ban-IP list
     * @return string
     */
    public function getBanIPs()
    {
        $ban_list = $this->redis->sMembers('ips:ban_list');
        $res = '';
        foreach ($ban_list as $value) {
            $res .= $value . PHP_EOL;
        }
        return $res;
    }

    /**
     * Update the old Ban-IP list and return the new
     * @param string $ips
     * @return string return the new Ban-IP list
     */
    public function updateBanIPs($ips = '')
    {
        // delete the key 'ips:ban_list' if exists
        $this->redis->exists('ips:ban_list') && $this->redis->del('ips:ban_list');

        // update the ban list for IP
        $ips = explode(PHP_EOL, trim($ips));
        foreach ($ips as $value) {
            empty($value) ? '' : $this->redis->sAdd('ips:ban_list', trim($value));
        }
        return $this->getBanIps();
    }

}