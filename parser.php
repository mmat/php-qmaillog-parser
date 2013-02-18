<?php

if ($argc != 2) {
    echo "Usage:".PHP_EOL;
    exit;
}

if (!is_readable($argv[1])) {
    echo "no readable".PHP_EOL;
    exit;
}

$data = array();
$key  = array();

$fp = fopen($argv[1], "r");
while (($buffer = fgets($fp)) !== false) {

    if (preg_match('/(?P<month>\w+?) *(?P<day>\d+?) (?P<time>.*?) .* new msg (?P<message_id>\d+)/', $buffer, $m)) {
        $data[$m['message_id']] = array(
            'start_time' => date('Y-m-d H:i:s', strtotime($m['month'].' '.$m['day'].' '.$m['time'])),
            'message_id' => $m['message_id']
        );
        continue;
    }

    if (preg_match('/ info msg (?P<message_id>\d+): .* from <(?P<from>.*?)> /', $buffer, $m)) {
        $data[$m['message_id']]['from'] = $m['from'];
        continue;
    }

    if (preg_match('/(?P<month>\w+?) *(?P<day>\d+?) (?P<time>.*?) .* starting delivery (?P<delivery_id>\d+): msg (?P<message_id>\d+) to \w+ (?P<to_address>.*)/', $buffer, $m)) {
        $key[$m['delivery_id']] = $m['message_id'];
        $data[$m['message_id']][$m['delivery_id']]['time'] = date('Y-m-d H:i:s', strtotime($m['month'].' '.$m['day'].' '.$m['time']));
        $data[$m['message_id']][$m['delivery_id']]['to']   = $m['to_address'];
        continue;
    }

    if (preg_match('/\d delivery (?P<delivery_id>\d+): (?P<status>\w+): (?P<msg>.*)/', $buffer, $m)) {
        $message_id = $key[$m['delivery_id']];
        unset($key[$m['delivery_id']]);
        $data[$message_id][$m['delivery_id']]['status']  = $m['status'];
        $data[$message_id][$m['delivery_id']]['message'] = $m['msg'];
        continue;
    }

    if (preg_match('/ end msg (?P<message_id>\d+)/', $buffer, $m)) {
        if (isset($data[$m['message_id']])) {
            echo json_encode($data[$m['message_id']]).PHP_EOL;
            unset($data[$m['message_id']]);
        }
        continue;
    }
}

foreach ($data as $val) {
    echo json_encode($val).PHP_EOL;
}