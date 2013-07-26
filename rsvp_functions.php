<?php
    /**
     * Sorts an array of objects by the value of one of the object properties or array keys
     *
     * @param array $array
     * @param key value $id
     * @param boolean $sort_ascending
     * @param boolean $is_object_array
     * @return array
     * lifted from http://php.net/manual/en/function.sort.php (david wh thomas at gm at 1l dot c0m). Function renamed to arraysort from vsort
     */
 function arraysort($array, $id="id", $sort_ascending=true, $is_object_array = false) {
        $temp_array = array();
        while(count($array)>0) {
            $lowest_id = 0;
            $index=0;
            if($is_object_array){
                foreach ($array as $item) {
                    if (isset($item->$id)) {
                                        if ($array[$lowest_id]->$id) {
                        if ($item->$id<$array[$lowest_id]->$id) {
                            $lowest_id = $index;
                        }
                        }
                                    }
                    $index++;
                }
            }else{
                foreach ($array as $item) {
                    if (isset($item[$id])) {
                        if ($array[$lowest_id][$id]) {
                        if ($item[$id]<$array[$lowest_id][$id]) {
                            $lowest_id = $index;
                        }
                        }
                                    }
                    $index++;
                }                              
            }
            $temp_array[] = $array[$lowest_id];
            $array = array_merge(array_slice($array, 0,$lowest_id), array_slice($array, $lowest_id+1));
        }
                if ($sort_ascending) {
            return $temp_array;
                } else {
                    return array_reverse($temp_array);
                }
    }


?>