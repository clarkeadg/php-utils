<?php
    function batchImport($table, $data) {
        $error = false;
        do {
            $rs                 = mysql_query("show variables like 'max_allowed_packet' ");
            $max_packets        = mysql_fetch_row($rs);
            $max_packet_size    = (int) $max_packets[1];
            $n                  = 0;
            $insert_values      = '';
            $last_insert_values = '';
            $update_keys        = '';
            $update_amount      = array();
            $update_items       = array();
            if (!empty($data)) {
                $item = $data[0];
                $keys = array();
                foreach ($item as $key => $value) {
                    $keys[] = '`' . $key . '`';
                }
                $update_keys = '(' . implode(',', $keys) . ')';
                for ($i = 0, $c = count($data); $i < $c; $i++) {
                    $CurrentItem =& $data[$i];
                    $vals = array();
                    foreach ($CurrentItem as $key => &$val) {
                        $vals[] = '"' . mysql_real_escape_string($val) . '"';
                    }
                    $last_insert_values = $insert_values;
                    $insert_values .= '(' . implode(',', $vals) . ')';
                    $string_size = strlen($insert_values);
                    $n++;
                    if (($string_size + 1000) > $max_packet_size) {
                        $update_items[]  = $last_insert_values;
                        $update_amount[] = $n;
                        $n               = 1;
                        $insert_values   = '(' . implode(',', $vals) . ')';
                    }
                    $insert_values .= ',';
                }
                if ($insert_values != '') {
                    $update_items[]  = $insert_values;
                    $update_amount[] = $n;
                }
                for ($i = 0, $c = count($update_items); $i < $c; $i++) {
                    $values = $update_items[$i];
                    if ($values[0] == ',') {
                        $values = substr($values, 1);
                    }
                    $last_id = strlen($values) - 1;
                    if ($values[$last_id] == ',') {
                        $values = substr($values, 0, -1);
                    }
                    $update_string = '';
                    for ($k = 0; $k < count($keys); $k++) {
                        if ($k > 0)
                            $update_string .= ',';
                        $update_string .= $keys[$k] . '=VALUES(' . $keys[$k] . ')';
                    }
                    $update_keys = '(' . implode(',', $keys) . ')';
                    $number_rows = $update_amount[$i];
                    $query       = "INSERT INTO $table $update_keys VALUES $values ON DUPLICATE KEY UPDATE $update_string";
                    $result      = mysql_query($query);
                    if (!$result) {
                        $error = 'Invalid query: ' . mysql_error();
                        break;
                    }
                }
            }
        } while (false);
        return $error;
    }
?>