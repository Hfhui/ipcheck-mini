<?php
// TODO:Verify the identity of the administrator

// get request parameters
$request_parameter = $_GET;
if (empty($request_parameter['menu'])) {
    $request_parameter['menu'] = 'recent_record';
}

/**
 * get the data output
 */
require dirname(__FILE__) . '/DataRender.php';
$data_render = new DataRender();
$html_body = '';
switch ($request_parameter['menu']) {
    case 'recent_record': {
        $html_body = $data_render->recentRecord($request_parameter['page']);
    }
        break;
    case 'total_record': {
        $html_body = $data_render->totalRecord($request_parameter['page']);
    }
        break;
    case 'ban_record': {
        $html_body = $data_render->banRecord();
    }
        break;
}

require dirname(__FILE__) . '/admin.html';
