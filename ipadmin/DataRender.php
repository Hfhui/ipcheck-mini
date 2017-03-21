<?php
require dirname(__FILE__) . '/RedisSingle.php';
require dirname(__FILE__) . '/AccessDenied.php';

/*
 * Obtain and rendering data
 */

class DataRender
{
    public $redis = '';

    public function __construct()
    {
        // get Redis connection
        $this->redis = RedisSingle::getRedis();
        $this->redis || exit;

        // set the default timezone
        date_default_timezone_set('Asia/Shanghai');
    }

    /**
     * Return the HTML code of the recent record
     * @param string $page
     * @return string
     */
    public function recentRecord($page = '1')
    {
        $html_body = <<<HTML
<table>
    <tr>
        <th>NO.</th>
        <th>IP ADDRESS</th>
        <th>LAST ACCESS TIME</th>
    </tr>
HTML;

        $list_count = $this->redis->lLen('ips:access_record');
        if (empty($page) || !is_numeric($page) || $page <= 0) {
            $page = 0;
        } else {
            $page -= 1;
        }

        $recent_record = $this->redis->lRange('ips:access_record', $page * 15, ($page + 1) * 15 - 1);

        // loop output the rows of table
        foreach ($recent_record as $key => $value) {
            if (++$key % 2 == 0) {
                $tr_class = 'double_tr';
            } else {
                $tr_class = 'single_tr';
            }

            $id = $key + $page * 15;

            // get the access time for IP
            $ip_info = json_decode($this->redis->hGet('ips:info', $value), true);
            $time = date('Y-m-d H:i:s', $ip_info['REQUEST_TIME']);

            $html_body .= <<<HTML
    <tr class="{$tr_class}">
        <td>{$id}</td>
        <td>{$value}</td>
        <td>{$time}</td>
    </tr>
HTML;
        }

        $html_body .= <<<HTML
</table>
HTML;

        return $html_body . $this->getPageSelector($page + 1, ceil($list_count / 15), 'admin.php?menu=recent_record');
    }

    /**
     * Return the HTML code of the total record
     * @param string $page
     * @return string
     */
    public function totalRecord($page = '1')
    {
        $html_body = <<<HTML
<table>
    <tr>
        <th>NO.</th>
        <th>IP ADDRESS</th>
        <th>ACCESS TIMES</th>
        <th>LAST ACCESS TIME</th>
        <th>LAST REQUEST FILE</th>
    </tr>
HTML;

        $record_count = $this->redis->zCard('ips:access_times');
        if (empty($page) || !is_numeric($page) || $page <= 0) {
            $page = 0;
        } else {
            $page -= 1;
        }

        $access_times = $this->redis->zRevRange('ips:access_times', $page * 15, ($page + 1) * 15 - 1, true);
        $count = count($access_times);

        for ($i = 0; $i < $count; $i++) {
            $id = $i + 1 + $page * 15;
            if ($id % 2 == 0) {
                $tr_class = 'double_tr';
            } else {
                $tr_class = 'single_tr';
            }

            $ip_address = key($access_times);
            $ip_access_times = $access_times[$ip_address];
            next($access_times);

            $ip_info = json_decode($this->redis->hGet('ips:info', $ip_address), true);
            $last_access_time = date('Y-m-d H:i:s', $ip_info['REQUEST_TIME']);
            $last_request_script = $ip_info['SCRIPT_NAME'];

            $html_body .= <<<HTML
    <tr class="{$tr_class}">
        <td>{$id}</td>
        <td>{$ip_address}</td>
        <td>{$ip_access_times}</td>
        <td>{$last_access_time}</td>
        <td>{$last_request_script}</td>
    </tr>
HTML;
        }

        $html_body .= <<<HTML
</table>
HTML;

        return $html_body . $this->getPageSelector($page + 1, ceil($record_count / 15), 'admin.php?menu=total_record');
    }

    /**
     * Return the HTML code of the access count
     * @return string
     */
    public function accessCount() {
        /*
         * get access records for seven days from now
         */
        for ($i = 0; $i < 7; $i++){
            $date[$i] = date('y-m-d', time() - $i * 86400);
            $res = $this->redis->zScore('ips:effective_access', $date[$i]);
            $res ? $effective_access[$i] = $res : $effective_access[$i] = 0;
            $res = $this->redis->zScore('ips:invalid_access', $date[$i]);
            $res ? $invalid_access[$i] = $res : $invalid_access[$i] = 0;
            $total_access[$i] = $effective_access[$i] + $invalid_access[$i];
        }

        $html_body = <<<HTML
<div id="echarts" style="width: 700px;height: 400px;margin: 20px auto;">
    <script type="text/javascript">
        var myChart = echarts.init(document.getElementById('echarts'));
        var option = {
            title: {
                text: 'ACCESS COUNT'
            },
            tooltip: {},
            legend: {
                data:['Total access','Effective access','Invalid access']
            },
            xAxis: {
                data: ['{$date[6]}','{$date[5]}','{$date[4]}','{$date[3]}','{$date[2]}','{$date[1]}','{$date[0]}']
            },
            yAxis: {},
            series: [{
                name: 'Total access',
                type: 'bar',
                data: [
                    {$total_access[6]},{$total_access[5]},{$total_access[4]},{$total_access[3]},
                    {$total_access[2]},{$total_access[1]},{$total_access[0]}
                ]
            },{
                name: 'Effective access',
                type: 'bar',
                data: [
                    {$effective_access[6]},{$effective_access[5]},{$effective_access[4]},{$effective_access[3]},
                    {$effective_access[2]},{$effective_access[1]},{$effective_access[0]}
                ]
            },{
                name: 'Invalid access',
                type: 'bar',
                data: [
                    {$invalid_access[6]},{$invalid_access[5]},{$invalid_access[4]},{$invalid_access[3]},
                    {$invalid_access[2]},{$invalid_access[1]},{$invalid_access[0]}
                ]
            }]
        };
        myChart.setOption(option);
    </script>
</div>
HTML;

        return $html_body;
    }

    /**
     * Return the HTML code of the ban record
     * @return string
     */
    public function banRecord()
    {
        if (!empty($_POST['ips'])) {
            $ips = (new AccessDenied($this->redis))->updateBanIPs($_POST['ips']);
        } else {
            $ips = (new AccessDenied($this->redis))->getBanIps();
        }

        $html_body = <<<HTML
<div class="ban_record_example">
Access Denied Example :<br />
    <div>
        127.0.0.1<br />
        10.0.0.2<br />
        172.16.0.1<br />
        192.168.0.1<br />
    </div>
</div>
<div class="ban_record_text">
    <form action="" method="post">
        <textarea name="ips" rows="22" cols="40">{$ips}</textarea>
        <input type="submit" value="submit" />
    </form>
</div>
HTML;

        return $html_body;
    }

    /**
     * Rendering the output of Page-Selector
     * @param $current_page
     * @param $total_page
     * @param $href
     * @return string
     */
    public function getPageSelector($current_page, $total_page, $href)
    {
        $page_selector_html = <<<HTML
<div class="page_selector">
    <ul>
HTML;

        for ($i = 1; $i <= $total_page; $i++) {
            if ($i == $current_page) {
                $select_class = 'class="page_select"';
            } else {
                $select_class = '';
            }

            $next_href = $href . '&page=' . $i;

            $page_selector_html .= <<<HTML
        <li {$select_class}><a href="{$next_href}">$i</a></li>
HTML;
        }

        $page_selector_html .= <<<HTML
        <li><span>total {$total_page} pages</span></li>
    </ul>
</div>
HTML;

        return $page_selector_html;
    }

    public function __destruct()
    {
        // close Redis
        RedisSingle::closeRedis();
    }
}
