#!/usr/bin/php -q
<?

    $args = $_SERVER['argv'];
    $file = $args[1];
    $output = $args[2];

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
        foreach ($csv as $c) {
          $arr[] = array_combine($csvShift, $c);
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
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=$address';
        $json = file_get_contents($url);
        if($json){
            $geo = json_decode($json, true);
            return $geo['results'][0]['geometry']['location'];
        }else{
            return false;
        }
    }


    function export($files){
        $outputFile;
        foreach($files as $k => $t){
            $outputFile = fopen($k, "w") or die("Unable to open file!");
            fwrite($outputFile, "$t");
        }
    }

    function convert2Csv($array){
        $csv = '';
        $firstRow = '';
        foreach($array as $key => $row) {
            if($key == 0){
                $firstRow = implode(',', array_keys($row));
            }
            $csv .= implode(',', $row) . "\r\n";
        }

        return $firstRow . "\r\n" . $csv;
    }

    function init($file){
        $originalCsv = array();
        $newCsv = array();
        $export = array();

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
            $newCsv = convert2Csv($newCsv);


            $export['retailers1.csv'] = $originalCsv;
            $export['output.csv'] = $newCsv;
            export($export);
        }
    }

    init($file);
?>
