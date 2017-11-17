<?php

$pattern = "/(?:(?'rval'[^\ ]+=[^\ ]*))|(?:\s\((?'aval'.*?)\)(?:\s|$))/";
$data = 'result=1 result=2 (222) result=3';
$parse_result = preg_match_all($pattern, $data, $parsed_line,PREG_SET_ORDER);
//$parse_result = preg_match_all($pattern, $data, $parsed_line,PREG_PATTERN_ORDER);

var_dump($pattern, $data, $parsed_line);