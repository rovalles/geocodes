#!/usr/bin/php -q
<?

    $args = $_SERVER['argv'];
    $file = $args[1];
    $output = $args[2];
    $csvKeyCount = 0;

    function getCsv($file){
        if(preg_match("/\.csv$/", $file)){
            return file($file);
        }else{
            return false;
        }
    }

    function csv2Array($csv){
        $csv = array_map('str_getcsv', $csv);
        $csvShift = array_shift($csv);
        $arr = array();

        // count($csvShift);
        foreach ($csv as $c) {
            if(count($csvShift) == count($c)){
                $arr[] = array_combine($csvShift, $c);
            }
        }

        return $arr;
    }

    function returnAddress($arr){
        $exclude = array('Customer#', 'Customer Name', 'Phone#');
        foreach( $exclude as $e){
            unset($arr[$e]);
        }

        return str_replace(' ', '+', join('+', array_values($arr) ));
    }

    function getGeoLocation($address){
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=$address";
        $json = file_get_contents($url);
        if($json){
            $geo = json_decode($json, true);
            return $geo['results'][0]['geometry']['location'];
        }else{
            return false;
        }
    }

    function convert2Csv($array, $allowKeys = false){
        $csv = '';
        $firstRow = '';
        foreach($array as $key => $row) {
            if($key == 0 && $allowKeys){
                $firstRow = implode(',', array_keys($row)) . "\r\n";
            }
            $csv .= implode(',', $row) . "\r\n";
        }

        return $firstRow . $csv;
    }

    function export($fileName, $content, $allowKeys = false){
        if( $allowKeys ){
            file_put_contents($fileName, "$content", FILE_APPEND);
        }else{
            file_put_contents($fileName, "$content");
        }
    }

    function init($file, $output){
        $originalCsv = array();
        $newCsv = array();
        $export = array();
        $allowKeys = count(file($output)) == 0 ? true : false;

        $csv = getCsv($file);
        if($csv){
            $csvArr = csv2Array($csv);
            foreach($csvArr as $k => $c){
                $address = returnAddress($c);
                $geo = getGeoLocation($address);

                if($geo){
                    $csvArr[$k]['lat'] = $geo['lat'];
                    $csvArr[$k]['lng'] = $geo['lng'];
                    array_push($newCsv, $csvArr[$k]);
                }else{
                    array_push($originalCsv, $csvArr[$k]);
                }
            }

            $originalCsv = convert2Csv($originalCsv);
            $newCsv = convert2Csv($newCsv, $allowKeys);

            export($file, $originalCsv);
            export($output, $newCsv, !$allowKeys);
        }
    }

    if(count(file($file)) == 0){
        init($file, $output);
    }
?>
