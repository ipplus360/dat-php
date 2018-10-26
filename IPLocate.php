<?php

$file = "IP_basic_2018W42_single_WGS84.dat";
$myfile = fopen($file, "r") or die("Unable to open file!");
$data = fread($myfile, filesize("IP_basic_2018W42_single_WGS84.dat"));
fclose($myfile);
$offset_addr = unpack('V1', substr($data, 0, 8)) [1];
$offset_owner = unpack('V1', substr($data, 8, 8)) [1];
$offset_info = substr($data, 16);
$base_len = 64;
$record_min = 0;
$record_max = intval($offset_addr / $base_len) - 1;
$record_mid = intval(($record_min + $record_max) / 2);
$regexp = '/^((25[0-5]|2[0-4]\d|[01]?\d\d?)\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)$/';

function locateip($nip, $data) {
    global $regexp;
    global $offset_addr;
    global $offset_owner;
    global $offset_info;
    global $base_len;
    global $record_min;
    global $record_max;
    global $record_mid;
    if (preg_match($regexp, $nip)) {
        $nip = sprintf('%u', ip2long($nip));
    } else {
        return ['Error IP'];
    };
    while ($record_max - $record_min >= 0) {
        $mult_re_ba = intval($record_mid * $base_len);
        $aa = substr($offset_info, $mult_re_ba, 4);
        $minip = sprintf('%u', unpack('V1', substr($offset_info, $mult_re_ba, 4)) [1]);
        $maxip = sprintf('%u', unpack('V1', substr($offset_info, $mult_re_ba + 4, 4)) [1]);
        if ($nip < $minip) {
            $record_max = $record_mid - 1;
        } elseif (($nip == $minip) || ($nip > $minip && $nip < $maxip) || ($nip == $maxip)) {
            $addr_begin = sprintf('%u', unpack('V1', substr($offset_info, $mult_re_ba + 8, 8)) [1]);
            $addr_length = sprintf('%u', unpack('V1', substr($offset_info, $mult_re_ba + 16, 8)) [1]);
            $owner_begin = sprintf('%u', unpack('V1', substr($offset_info, $mult_re_ba + 24, 8)) [1]);
            $owner_length = sprintf('%u', unpack('V1', substr($offset_info, $mult_re_ba + 32, 8)) [1]);
            $wgs_lon = substr($offset_info, $mult_re_ba + 40, 12);
            $wgs_lat = substr($offset_info, $mult_re_ba + 52, 12);
            $addr_bundle = substr($offset_info, $addr_begin, $addr_length);
            $addr = explode("|", $addr_bundle);
            $owner = substr($offset_info, $owner_begin, $owner_length);
            $tmp_list = array(
                strval($minip) ,
                strval($maxip) ,
                $addr[0],
                $addr[1],
                $addr[2],
                $addr[3],
                $addr[4],
                $addr[5],
                $addr[6],
                strval($wgs_lon) ,
                strval($wgs_lat) ,
                strval($owner)
            );
            $res_list = array();
            foreach ($tmp_list as & $item) {
                array_push($res_list, $item);
            };
            return $res_list;
        } elseif ($nip > $maxip) {
            $record_min = $record_mid + 1;
        } else {
            return ["Error Case"];
        }
        $record_mid = intval(($record_min + $record_max) / 2);
    };
    return ['Not Found.'];
}
echo date("Y-m-d H:i:s");
echo "\n";
for ($x = 0; $x < 1; $x++) {
    echo json_encode(locateip("0.0.0.0", $data));
}
echo "\n";
echo date("Y-m-d H:i:s");
?> 
