<?php

namespace App\Http\Controllers;

use App\Models\ReportOne;
use App\Models\ReportOneFiltered;
use App\Models\ReportThree;
use App\Models\ReportTwo;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WialonController extends Controller
{
    protected $token;
    protected $eid;
    protected $locations;
    protected $allLocations;
    protected $plants;

    protected $allGeofences;

    public function __construct(Request $request)
    {
        ini_set('max_execution_time', 600);
        $this->token = 'bb4a51dbe579347b6844c4dbf145ab7f729D06D4498EF2E85C4A40CEAAF38955F22D553F';
    }

    public function getSessionEID()
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://hst-api.wialon.com/wialon/ajax.html?svc=token/login&params={"token":"'.$this->token.'"}',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded'
        ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $responseDecoded = json_decode($response);    
        $this->eid = $responseDecoded->eid;
    }

    public function getAllLocations()
    {
        $this->getSessionEID();
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://hst-api.wialon.com/wialon/ajax.html?svc=core/search_items&params={"spec":{"itemsType":"avl_resource","propName":"zones_library,sys_id","propType":"propitemname","propValueMask":"*","sortType":"sys_name"},"force":4,"flags":4097,"from":0,"to":0}&sid='.$this->eid,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $responseDecoded = json_decode($response,1);
        if(!empty($responseDecoded['items'])){
            foreach($responseDecoded['items'] as $location){
                if($location["nm"]=="tokyo_super"){
                    $data = [];
                    foreach($location["zl"] as $zone){
                        $record['id'] = $zone['id'];
                        $record['name'] = $zone['n'];
                        $data[] = $record;
                    }
                    $this->allGeofences = $data;
                    return $this->allGeofences;
                }
            }
            return $responseDecoded['items'];
        }
    }

    public function getPlants()
    {
        $plants = [];
        $this->getGeofenceGroups();
        if($this->locations>0){
            foreach($this->locations as $group){
                if($group['group'] == 'Plants'){
                    $plants = $group['locations'];
                }
            }
        }
        if(!empty($plants)){
            $allLocations = $this->getAllLocations();
            $data = [];
            foreach($allLocations as $location){
                if(isset($location['zl'])){
                    $data[] = $location['zl'];
                }
            }
            return $data;
        }
    }

    public function getReportOneSingleLocation()
    {
        try {
            $plants = [];
            $this->getGeofenceGroups();
            if($this->locations>0){
                foreach($this->locations as $group){
                    if($group['group'] == 'Plants'){
                        $plants = $group;
                    }
                }
            }
            if(!empty($plants)){
                
            }
        } catch (Exception $e) {
            return response()->json(['Line'=>$e->getLine(),'Message'=>$e->getMessage()]);
        }
       
    }

    public function getGeofenceGroups()
    {
        $this->getSessionEID();
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://hst-api.wialon.com/wialon/ajax.html?svc=core/search_items&params={"spec":{"itemsType":"avl_resource","propName":"sys_name","propValueMask":"*","sortType":"sys_name"},"force":1,"flags":1048576,"from":0,"to":0}&sid='.$this->eid,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $data = [];
        $locations = json_decode($response,1);
        foreach($locations['items'] as $location){
            if(isset($location['zg'])){
                foreach($location['zg'] as $key=>$perLocation){
                    $dat[$key]['group'] = $perLocation['n'];
                    $dat[$key]['locations'] = $perLocation['zns'];
                    array_push($data,$dat[$key]);
                }
            }
        }
        $this->locations = $data;
        return $this->locations;
    }

    public function getReportOne(Request $request)
    {   
        try {
            $data = $request->all();
            $from = $data['from'];
            $to   = $data['to'];
            $plant = isset($data['plant']) ? $data['plant'] : null;
            // Get plant and vehicle details for duration
            if($to>$from && $plant!=null){
                if($plant=='peliyagoda'){
                    $plants = ['Peliyagoda Yard','Tokyo SUERMIX Peliyagoda','Tokyo_Rathmalana Plant'];
                    DB::table('report_one')->truncate();
                    ReportOne::truncate();
                    $result = $this->getPeliyagodaYardData($from,$to);
                    if(isset($result['reportResult']['tables'][0]['rows'])){
                        $rows = $result['reportResult']['tables'][0]['rows'] > 0 ? $result['reportResult']['tables'][0]['rows'] : 0;
                        if($rows>0){
                            $dataFinal = [];
                            for($i=0; $i<$rows; $i++){
                                $records = $this->reportGetRecords($i);
                                $records = json_decode($records,1);
                                $sI = 0;
                                foreach($records as $record){
                                    // Filter to only insert Mixer Truck records
                                    if(strpos($record['c'][1],'MT')!==false){
                                        // Filter plant to plant records
                                        if((in_array($record['c'][2], $plants) && !in_array($record['c'][4], $plants)) || 
                                        (!in_array($record['c'][2], $plants) && in_array($record['c'][4], $plants))){    
                                            $data[$sI]['tokyo_vehicle_id']                = $record['uid'] ?? null;
                                            $data[$sI]['tokyo_vehicle_name']              = $record['c'][1] ?? null;
                                            $data[$sI]['tokyo_location_name']             = $record['c'][2] ?? null;
                                            $data[$sI]['tokyo_plant_in_time']             = null;
                                            $data[$sI]['tokyo_plant_out_time']            = $record['c'][3]['t'] ?? null;
                                            $data[$sI]['tokyo_plant_duration']            = null;
                                            $data[$sI]['tokyo_site_name']                 = $record['c'][4] ?? null;
                                            $data[$sI]['tokyo_site_in_time']              = $record['c'][5]['t'] ?? null;
                                            $data[$sI]['tokyo_site_out_time']             = null;
                                            $data[$sI]['tokyo_site_duration']             = null;
                                            $data[$sI]['tokyo_site_out_plan_in_duration'] = null;
                                            array_push($dataFinal, $data[$sI]);
                                            $sI++;
                                        }
                                    }
                                }
                            }                     
                            foreach($dataFinal as $dataFinalRow){
                                ReportOne::create([
                                    'tokyo_vehicle_id'                => $dataFinalRow['tokyo_vehicle_id'],
                                    'tokyo_vehicle_name'              => $dataFinalRow['tokyo_vehicle_name'],
                                    'tokyo_location_name'             => $dataFinalRow['tokyo_location_name'],
                                    'tokyo_plant_in_time'             => $dataFinalRow['tokyo_plant_in_time'],
                                    'tokyo_plant_out_time'            => $dataFinalRow['tokyo_plant_out_time'],
                                    'tokyo_site_name'                 => $dataFinalRow['tokyo_site_name'],
                                    'tokyo_site_in_time'              => $dataFinalRow['tokyo_site_in_time'],
                                    'tokyo_site_out_time'             => $dataFinalRow['tokyo_site_out_time'],
                                    'tokyo_site_duration'             => $dataFinalRow['tokyo_site_duration'],
                                    'tokyo_site_out_plan_in_duration' => $dataFinalRow['tokyo_site_out_plan_in_duration'],
                                ]);
                            }
                            // Further Filter
                            $reportOneData = ReportOne::get()->toArray();
                            for($i=0;$i<count($reportOneData)-1;$i+=2){
                                $newRecord = [];
                                if($reportOneData[$i]['tokyo_location_name'] == $reportOneData[$i+1]['tokyo_location_name']){
                                    $id = $reportOneData[$i]['id'];
                                    ReportOne::where('id', $id)->delete();
                                }
                            }
                            // Get New Records
                            $reportOneData = ReportOne::get()->toArray();
                            ReportOneFiltered::truncate();
                            for($i=0;$i<count($reportOneData)-1;$i+=2){
                                $newRecord = [];
                                if(in_array($reportOneData[$i]['tokyo_location_name'],$plants)){
                                    if(($reportOneData[$i]['tokyo_location_name'] == $reportOneData[$i+1]['tokyo_site_name'])
                                    && ($reportOneData[$i]['tokyo_site_name'] == $reportOneData[$i+1]['tokyo_location_name'])){
                                        // First Record
                                        $firstRecord                       = $reportOneData[$i];
                                        $newRecord['tokyo_vehicle_id']     = $firstRecord['tokyo_vehicle_id'];
                                        $newRecord['tokyo_vehicle_name']   = $firstRecord['tokyo_vehicle_name'];
                                        $newRecord['tokyo_location_name']  = $firstRecord['tokyo_location_name'];
                                        $newRecord['tokyo_plant_out_time'] = $firstRecord['tokyo_plant_out_time'];
                                        $newRecord['tokyo_site_name']      = $firstRecord['tokyo_site_name'];
                                        $newRecord['tokyo_site_in_time']   = $firstRecord['tokyo_site_in_time'];
                                        
                                        // Second Record 
                                        $secondRecord = $reportOneData[$i+1];
                                        $newRecord['tokyo_plant_in_time']  = $secondRecord['tokyo_site_in_time'];
                                        $newRecord['tokyo_site_out_time']  = $secondRecord['tokyo_plant_out_time'];
                                        
                                        // Site Idle Time
                                        $site_in = $firstRecord['tokyo_site_in_time'];
                                        $site_out =$secondRecord['tokyo_plant_out_time'];
                                        $newRecord['tokyo_site_duration'] = $this->getTimeDifference($site_out,$site_in);
                                        
                                        // Site Out Plant In
                                        $plant_in_2 = $secondRecord['tokyo_site_in_time'];
                                        $site_out_2 = $firstRecord['tokyo_plant_out_time'];
                                        $newRecord['tokyo_site_out_plan_in_duration']  = $this->getTimeDifference($plant_in_2,$site_out_2);

                                        // Plant In Site Out
                                        $plant_out = $firstRecord['tokyo_plant_out_time'];
                                        $site_in = $firstRecord['tokyo_site_in_time'];
                                        $newRecord['tokyo_site_plant_out_site_in_duration'] = $this->getTimeDifference($site_in,$plant_out);

                                        // Plant Out Site Out (Real Idling Time)
                                        $plant_out = $firstRecord['tokyo_plant_out_time'];
                                        $site_out_2 = $secondRecord['tokyo_plant_out_time'];
                                        $newRecord['tokyo_site_out_plan_out_duration'] = $this->getTimeDifference($site_out_2,$plant_out);

                                        ReportOneFiltered::create($newRecord);
                                    }
                                }
                            }
                            $data = ReportOneFiltered::get();
                            return response()->json([
                                    'data'   => $data,
                                    'status' => 200,
                                    'code'   => 1
                            ]);
                        }
                    }
                }
                else if($plant=='kandy'){
                    $plants = ['Tokyo Kandy Plant','Tokyo SUPERMIX, Kandy'];
                    DB::table('report_one')->truncate();
                    ReportOne::truncate();
                    $result = $this->getKandyPlantData($from,$to);
                    if(isset($result['reportResult']['tables'][0]['rows'])){
                        $rows = $result['reportResult']['tables'][0]['rows'] > 0 ? $result['reportResult']['tables'][0]['rows'] : 0;
                        if($rows>0){
                            $dataFinal = [];
                            for($i=0; $i<$rows; $i++){
                                $records = $this->reportGetRecords($i);
                                $records = json_decode($records,1);
                                $sI = 0;
                                foreach($records as $record){
                                    // Filter to only insert Mixer Truck records
                                    if(strpos($record['c'][1],'MT')!==false){
                                        // Filter plant to plant records
                                        if((in_array($record['c'][2], $plants) && !in_array($record['c'][4], $plants)) || 
                                        (!in_array($record['c'][2], $plants) && in_array($record['c'][4], $plants))){    
                                            $data[$sI]['tokyo_vehicle_id']                = $record['uid'] ?? null;
                                            $data[$sI]['tokyo_vehicle_name']              = $record['c'][1] ?? null;
                                            $data[$sI]['tokyo_location_name']             = $record['c'][2] ?? null;
                                            $data[$sI]['tokyo_plant_in_time']             = null;
                                            $data[$sI]['tokyo_plant_out_time']            = $record['c'][3]['t'] ?? null;
                                            $data[$sI]['tokyo_plant_duration']            = null;
                                            $data[$sI]['tokyo_site_name']                 = $record['c'][4] ?? null;
                                            $data[$sI]['tokyo_site_in_time']              = $record['c'][5]['t'] ?? null;
                                            $data[$sI]['tokyo_site_out_time']             = null;
                                            $data[$sI]['tokyo_site_duration']             = null;
                                            $data[$sI]['tokyo_site_out_plan_in_duration'] = null;
                                            array_push($dataFinal, $data[$sI]);
                                            $sI++;
                                        }
                                    }
                                }
                            }                     
                            foreach($dataFinal as $dataFinalRow){
                                ReportOne::create([
                                    'tokyo_vehicle_id'                => $dataFinalRow['tokyo_vehicle_id'],
                                    'tokyo_vehicle_name'              => $dataFinalRow['tokyo_vehicle_name'],
                                    'tokyo_location_name'             => $dataFinalRow['tokyo_location_name'],
                                    'tokyo_plant_in_time'             => $dataFinalRow['tokyo_plant_in_time'],
                                    'tokyo_plant_out_time'            => $dataFinalRow['tokyo_plant_out_time'],
                                    'tokyo_site_name'                 => $dataFinalRow['tokyo_site_name'],
                                    'tokyo_site_in_time'              => $dataFinalRow['tokyo_site_in_time'],
                                    'tokyo_site_out_time'             => $dataFinalRow['tokyo_site_out_time'],
                                    'tokyo_site_duration'             => $dataFinalRow['tokyo_site_duration'],
                                    'tokyo_site_out_plan_in_duration' => $dataFinalRow['tokyo_site_out_plan_in_duration'],
                                ]);
                            }
                            // Further Filter
                            $reportOneData = ReportOne::get()->toArray();
                            for($i=0;$i<count($reportOneData)-1;$i+=2){
                                $newRecord = [];
                                if($reportOneData[$i]['tokyo_location_name'] == $reportOneData[$i+1]['tokyo_location_name']){
                                    $id = $reportOneData[$i]['id'];
                                    ReportOne::where('id', $id)->delete();
                                }
                            }
                            // Get New Records
                            $reportOneData = ReportOne::get()->toArray();
                            ReportOneFiltered::truncate();
                            for($i=0;$i<count($reportOneData)-1;$i+=2){
                                $newRecord = [];
                                if(in_array($reportOneData[$i]['tokyo_location_name'],$plants)){
                                    if(($reportOneData[$i]['tokyo_location_name'] == $reportOneData[$i+1]['tokyo_site_name'])
                                    && ($reportOneData[$i]['tokyo_site_name'] == $reportOneData[$i+1]['tokyo_location_name'])){
                                        // First Record
                                        $firstRecord                       = $reportOneData[$i];
                                        $newRecord['tokyo_vehicle_id']     = $firstRecord['tokyo_vehicle_id'];
                                        $newRecord['tokyo_vehicle_name']   = $firstRecord['tokyo_vehicle_name'];
                                        $newRecord['tokyo_location_name']  = $firstRecord['tokyo_location_name'];
                                        $newRecord['tokyo_plant_out_time'] = $firstRecord['tokyo_plant_out_time'];
                                        $newRecord['tokyo_site_name']      = $firstRecord['tokyo_site_name'];
                                        $newRecord['tokyo_site_in_time']   = $firstRecord['tokyo_site_in_time'];
                                        
                                        // Second Record 
                                        $secondRecord = $reportOneData[$i+1];
                                        $newRecord['tokyo_plant_in_time']  = $secondRecord['tokyo_site_in_time'];
                                        $newRecord['tokyo_site_out_time']  = $secondRecord['tokyo_plant_out_time'];
                                        
                                        // Site Idle Time
                                        $site_in = $firstRecord['tokyo_site_in_time'];
                                        $site_out =$secondRecord['tokyo_plant_out_time'];
                                        $newRecord['tokyo_site_duration'] = $this->getTimeDifference($site_out,$site_in);
                                        
                                        // Site Out Plant In
                                        $plant_in_2 = $secondRecord['tokyo_site_in_time'];
                                        $site_out_2 = $firstRecord['tokyo_plant_out_time'];
                                        $newRecord['tokyo_site_out_plan_in_duration']  = $this->getTimeDifference($plant_in_2,$site_out_2);

                                        // Plant In Site Out
                                        $plant_out = $firstRecord['tokyo_plant_out_time'];
                                        $site_in = $firstRecord['tokyo_site_in_time'];
                                        $newRecord['tokyo_site_plant_out_site_in_duration'] = $this->getTimeDifference($site_in,$plant_out);

                                        // Plant Out Site Out (Real Idling Time)
                                        $plant_out = $firstRecord['tokyo_plant_out_time'];
                                        $site_out_2 = $secondRecord['tokyo_plant_out_time'];
                                        $newRecord['tokyo_site_out_plan_out_duration'] = $this->getTimeDifference($site_out_2,$plant_out);

                                        ReportOneFiltered::create($newRecord);
                                    }
                                }
                            }
                            $data = ReportOneFiltered::get();
                            return response()->json([
                                    'data'   => $data,
                                    'status' => 200,
                                    'code'   => 1
                            ]);
                        }
                    }
                }
                
            }
            else{
                return response()->json(['message'=>'Please enter a valid date range']);    
            }
        } catch (Exception $e) {
            return response()->json(['Error'=>$e->getMessage(),"Line"=>$e->getLine()]);
        }
        
    }

    public function getTimeDifference($time1,$time2){
        $time1 = Carbon::parse($time1);
        $time2 = Carbon::parse($time2);

        $diffInSeconds = $time1->diffInSeconds($time2);
        $hours = floor($diffInSeconds / 3600);
        $minutes = floor(($diffInSeconds % 3600) / 60);
        $seconds = $diffInSeconds % 60;

        $timeDiff = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        return $timeDiff;
    }

    public function reportOnePlantDurations($from,$to)
    {
        $this->getSessionEID();
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://hst-api.wialon.com/wialon/ajax.html?svc=report/exec_report',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => 'params={"reportResourceId":16798032,"reportTemplateId":43,"reportTemplate":null,"reportObjectId":16798032,"reportObjectSecId":"2","interval":{"flags":16777216,"from":'.$from.',"to":'.$to.'}}&sid='.$this->eid,
        CURLOPT_HTTPHEADER => array(
            'accept: */*',
            'accept-language: en-GB,en-US;q=0.9,en;q=0.8',
            'content-type: application/x-www-form-urlencoded',
            'dnt: 1',
            'origin: https://hst-api.wialon.com',
            'priority: u=1, i',
            'referer: https://hst-api.wialon.com/wialon/post.html',
            'sec-ch-ua: "Not)A;Brand";v="99", "Google Chrome";v="127", "Chromium";v="127"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36'
        ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $responseDecoded = json_decode($response);    
        $rows = 0;
        isset($responseDecoded->reportResult->tables[0]->rows)? $rows = $responseDecoded->reportResult->tables[0]->rows : 0 ;
        if($rows!=0){
            $dataFinal = [];
            for($i=0; $i<$rows; $i++){
                $records = $this->reportGetRecords($i);
                $records = json_decode($records,1);
                $sI = 0;
                foreach($records as $record){
                    $data[$sI]['vehicle_id'] = $record['uid'] ?? null;
                    $data[$sI]['index'] = $record['c'][0] ?? null;
                    $data[$sI]['vehicle'] = $record['c'][2] ?? null;
                    $data[$sI]['plant'] = $record['c'][3] ?? null;
                    $data[$sI]['plant_in'] = $record['c'][4]['t'] ?? null;
                    $data[$sI]['plant_out'] = $record['c'][5]['t'] ?? null;
                    $data[$sI]['plant_duration'] = $record['c'][6] ?? null;
                    array_push( $dataFinal, $data[$sI]);
                    $sI++;
                }
            }
            foreach($dataFinal as $dataFinalRow){
                ReportOne::create([
                    'tokyo_location_name' => $dataFinalRow['plant'],
                    'tokyo_vehicle_name'=> $dataFinalRow['vehicle'],
                    'tokyo_vehicle_id'=> $dataFinalRow['vehicle_id'],
                    'tokyo_plant_in_time'=> $dataFinalRow['plant_in'],
                    'tokyo_plant_out_time'=> $dataFinalRow['plant_out'],
                    'tokyo_plant_duration'=> $dataFinalRow['plant_duration'],
                ]);
            }
            return response()->json([
                    'data'   => $dataFinal,
                    'status' => 200,
                    'code'   => 1
            ]);
        }   
        else{
            return response()->json([
                    'data'    => '',
                    'status'  => 200,
                    'code'    => 0
            ]);
        }
        
    }
    
    public function reportOneSiteDurations($from,$to)
    {
        $this->getSessionEID();
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://hst-api.wialon.com/wialon/ajax.html?svc=report/exec_report',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => 'params={"reportResourceId":16798032,"reportTemplateId":43,"reportTemplate":null,"reportObjectId":16798032,"reportObjectSecId":"6","interval":{"flags":16777216,"from":'.$from.',"to":'.$to.'}}&sid='.$this->eid,
        CURLOPT_HTTPHEADER => array(
            'accept: */*',
            'accept-language: en-GB,en-US;q=0.9,en;q=0.8',
            'content-type: application/x-www-form-urlencoded',
            'dnt: 1',
            'origin: https://hst-api.wialon.com',
            'priority: u=1, i',
            'referer: https://hst-api.wialon.com/wialon/post.html',
            'sec-ch-ua: "Not)A;Brand";v="99", "Google Chrome";v="127", "Chromium";v="127"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36'
        ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $responseDecoded = json_decode($response);    
        $rows = 0;
        isset($responseDecoded->reportResult->tables[0]->rows)? $rows = $responseDecoded->reportResult->tables[0]->rows : 0 ;
        $data = [];
        if($rows>0){
            for($i=0;$i<$rows;$i++){
                $records = $this->reportGetRecords($i);
                $records = json_decode($records,1);
                $sI = 0;
                foreach($records as $record){
                    $dat = [];
                    $dat[$sI]['vehicle_id']          = $record['uid'] ?? null;
                    $dat[$sI]['tokyo_site_name']     = $record['c'][3] ?? null;
                    $dat[$sI]['tokyo_site_in_time']  = $record['c'][4]['t'] ?? null;
                    $dat[$sI]['tokyo_site_out_time'] = $record['c'][5]['t'] ?? null;
                    array_push($data,$dat[$sI]);
                }
            }
            foreach($data as $dataRow){
                $records = ReportOne::where('tokyo_vehicle_id', $dataRow['vehicle_id'])->get();

                foreach ($records as $record) {
                    $record->tokyo_site_name = $dataRow['tokyo_site_name'];
                    $record->tokyo_site_in_time = $dataRow['tokyo_site_in_time'];
                    $record->tokyo_site_out_time = $dataRow['tokyo_site_out_time'];
                    $siteOutTime = Carbon::parse($dataRow['tokyo_site_out_time']);
                    $plantOutTime = Carbon::parse($record['tokyo_plant_out_time']);
                    $record->tokyo_site_out_plan_in_duration = $siteOutTime->diffForHumans($plantOutTime);
                    $record->save();
                }
            }
        }   
        else{
            return response()->json(
                [
                    'message' => 'No Data',
                    'status' => 200,
                    'code'=>0
                ]
            );
        }
    }

    public function reportGetRecords($row)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://hst-api.wialon.com/wialon/ajax.html?svc=report/select_result_rows&sid='.$this->eid.'&params={"tableIndex":0,"config":{"type":"row","data":{"rows":["'.$row.'"],"level":0,"unitInfo":1}}}',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    // Report 02
    public function getReportTwo(Request $request)
    {
        try {
            $data = $request->all();
            // $from = Carbon::parse($data['from'])->timestamp;
            // $to   = Carbon::parse($data['to'])->timestamp;
            $from = $data['from'];
            $to   = $data['to'];
            if($to>$from){
                DB::table('report_two')->truncate();
                $this->getReportTwoPumpCarPlantTimes($from,$to);
                $this->getReportTwoPumpCarSiteInTimes($from,$to);
                $this->reportTwoFilterData();
                $this->getReportTwoLocationIds();
                $this->getReportTwoFirstTruckInTime();
                return response()->json(['data'=>ReportTwo::get()]);
            }
            else{
                return response()->json(['message'=>'Please enter a valid date range']);    
            }
        } catch (Exception $th) {
            return response()->json([
                'message' => $th->getMessage(),
                'line' => $th->getLine()
            ]);
        }
    }

    // Report Three
    public function getReportThree(Request $request)
    {
        try {
            $data = $request->all();
            // $from = Carbon::parse($data['from'])->timestamp;
            // $to   = Carbon::parse($data['to'])->timestamp;
            $from = $data['from'];
            $to   = $data['to'];
            if($to>$from){
                DB::table('report_three')->truncate();
                return $this->getReportThreePlants($from,$to);
            }
            else{
                return response()->json(['message'=>'Please enter a valid date range']);    
            }
        } catch (Exception $th) {
            return response()->json([
                'message' => $th->getMessage(),
                'line' => $th->getLine()
            ]);
        }
    }

    public function getReportThreePlants($from,$to)
    {
        $this->getSessionEID();
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://hst-api.wialon.com/wialon/ajax.html?svc=report/exec_report',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => 'params={"reportResourceId":16798032,"reportTemplateId":43,"reportTemplate":null,"reportObjectId":16798032,"reportObjectSecId":"2","interval":{"flags":16777216,"from":'.$from.',"to":'.$to.'}}&sid='.$this->eid,
        CURLOPT_HTTPHEADER => array(
            'accept: */*',
            'accept-language: en-GB,en-US;q=0.9,en;q=0.8',
            'content-type: application/x-www-form-urlencoded',
            'dnt: 1',
            'origin: https://hst-api.wialon.com',
            'priority: u=1, i',
            'referer: https://hst-api.wialon.com/wialon/post.html',
            'sec-ch-ua: "Not)A;Brand";v="99", "Google Chrome";v="127", "Chromium";v="127"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36'
        ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $responseDecoded = json_decode($response);    
        $rows = 0;
        isset($responseDecoded->reportResult->tables[0]->rows)? $rows = $responseDecoded->reportResult->tables[0]->rows : 0 ;
        if($rows!=0){
            $dataFinal = [];
            for($i=0; $i<$rows; $i++){
                $records = $this->reportGetRecords($i);
                $records = json_decode($records,1);
                $sI = 0;
                foreach($records as $record){
                    $data[$sI]['date'] = $record['c'][1] ?? null;
                    $data[$sI]['plant'] = $record['c'][3] ?? null;
                    array_push( $dataFinal, $data[$sI]);
                    $sI++;
                }
            }
            foreach($dataFinal as $dataFinalRow){
                $exists = ReportThree::where('plant',$dataFinalRow['plant'])->first();
                if(empty($exists)){
                    ReportThree::create([
                        'date' => $dataFinalRow['date'],
                        'plant'=> $dataFinalRow['plant'],
                    ]);
                }
            }
            return response()->json([
                    'data'   => ReportThree::get(),
                    'status' => 200,
                    'code'   => 1
            ]);
        }   
        else{
            return response()->json([
                    'data'    => '',
                    'status'  => 200,
                    'code'    => 0
            ]);
        }
    }


    public function getReportTwoPumpCarPlantTimes($from,$to)
    {
        $this->getSessionEID();
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://hst-api.wialon.com/wialon/ajax.html?svc=report/exec_report',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => 'params={"reportResourceId":16798032,"reportTemplateId":45,"reportTemplate":null,"reportObjectId":16798032,"reportObjectSecId":"2","interval":{"flags":16777216,"from":'.$from.',"to":'.$to.'}}&sid='.$this->eid,
        CURLOPT_HTTPHEADER => array(
            'accept: */*',
            'accept-language: en-GB,en-US;q=0.9,en;q=0.8',
            'content-type: application/x-www-form-urlencoded',
            'dnt: 1',
            'origin: https://hst-api.wialon.com',
            'priority: u=1, i',
            'referer: https://hst-api.wialon.com/wialon/post.html',
            'sec-ch-ua: "Not)A;Brand";v="99", "Google Chrome";v="127", "Chromium";v="127"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36'
        ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $responseDecoded = json_decode($response);   
        $rows = 0;
        isset($responseDecoded->reportResult->tables[0]->rows)? $rows = $responseDecoded->reportResult->tables[0]->rows : 0 ;
        $data = [];
        if($rows>0){
            for($i=0;$i<$rows;$i++){
                $records = $this->reportGetRecords($i);
                $records = json_decode($records,1);
                $sI = 0;
                foreach($records as $record){
                    $dat = [];
                    $dat[$sI]['tokyo_pump_car_id']    = $record['uid'] ?? null;
                    $dat[$sI]['tokyo_pump_car_name']  = $record['c'][2] ?? null;
                    $dat[$sI]['tokyo_location_name']  = $record['c'][3] ?? null;
                    $dat[$sI]['tokyo_plant_out_time'] = $record['c'][4]['t'] ?? null;
                    array_push($data,$dat[$sI]);
                }
            }
            foreach($data as $dataRow){
                ReportTwo::create([
                    'tokyo_pump_car_id'    => $dataRow['tokyo_pump_car_id'],
                    'tokyo_pump_car_name'  => $dataRow['tokyo_pump_car_name'],
                    'tokyo_location_name'  => $dataRow['tokyo_location_name'],
                    'tokyo_plant_out_time' => $dataRow['tokyo_plant_out_time']
                ]);
            }
        }   
        else{
            return response()->json(
                [
                    'message' => 'No Data',
                    'status' => 200,
                    'code'=>0
                ]
            );
        }
    }

    public function getReportTwoPumpCarSiteInTimes($from,$to)
    {
        $this->getSessionEID();
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://hst-api.wialon.com/wialon/ajax.html?svc=report/exec_report',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => 'params={"reportResourceId":16798032,"reportTemplateId":47,"reportTemplate":null,"reportObjectId":16798032,"reportObjectSecId":"6","interval":{"flags":16777216,"from":'.$from.',"to":'.$to.'}}&sid='.$this->eid,
        CURLOPT_HTTPHEADER => array(
            'accept: */*',
            'accept-language: en-GB,en-US;q=0.9,en;q=0.8',
            'content-type: application/x-www-form-urlencoded',
            'dnt: 1',
            'origin: https://hst-api.wialon.com',
            'priority: u=1, i',
            'referer: https://hst-api.wialon.com/wialon/post.html',
            'sec-ch-ua: "Not)A;Brand";v="99", "Google Chrome";v="127", "Chromium";v="127"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36'
        ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $responseDecoded = json_decode($response);    
        $rows = 0;
        isset($responseDecoded->reportResult->tables[0]->rows)? $rows = $responseDecoded->reportResult->tables[0]->rows : 0 ;
        $data = [];
        if($rows>0){
            for($i=0;$i<$rows;$i++){
                $records = $this->reportGetRecords($i);
                $records = json_decode($records,1);
                $sI = 0;
                foreach($records as $record){
                    $dat = [];
                    $dat[$sI]['vehicle_id']         = $record['uid'] ?? null;
                    $dat[$sI]['tokyo_site_name']    = $record['c'][3] ?? null;
                    $dat[$sI]['tokyo_site_in_time'] = $record['c'][4]['t'] ?? null;
                    array_push($data,$dat[$sI]);
                }
            }
            foreach($data as $dataRow){
                $records = ReportTwo::where('tokyo_pump_car_id', $dataRow['vehicle_id'])->get();
                foreach ($records as $record) {
                    $record->tokyo_site_in_time = $dataRow['tokyo_site_in_time'];
                    $record->tokyo_site_name = $dataRow['tokyo_site_name'];
                    $record->save();
                }
            }
        }   
        else{
            return response()->json(
                [
                    'message' => 'No Data',
                    'status' => 200,
                    'code'=>0
                ]
            );
        }
    }

    public function getReportTwoLocationIds()
    {
        $locations = $this->getAllLocations();
        $report = ReportTwo::get();
        foreach($report as $record){
            foreach($locations as $location){
                if($location['name']==$record['tokyo_site_name']){
                    $record->tokyo_pump_site_id = $location['id'];
                    $record->save();
                }
            }
        }
    }

    public function reportTwoFilterData() : JsonResponse
    {
        $data = ReportTwo::get();
        $vehicle_data = ReportTwo::select("tokyo_pump_car_id")->distinct()->get();
        $vehicles = [];
        foreach($vehicle_data as $vehicl){
            $vehicles[] = $vehicl['tokyo_pump_car_id'];
        }
        // Get records by vehicle
        foreach($vehicles as $vehicle){
            $data = ReportTwo::get()->where("tokyo_pump_car_id");
            foreach($data as $dat){
                $plant_out = Carbon::parse($dat->tokyo_plant_out_time);
                $site_in = is_null($dat->tokyo_site_in_time)?null:Carbon::parse($dat->tokyo_site_in_time);
                if($plant_out->greaterThan($site_in) || $site_in==null){
                    $dat->delete();
                }
                if ($plant_out->diffInDays($site_in, false) > 1) {
                    $dat->delete();
                }
            }
        }
        return response()->json([
            "message"=> "Success"
        ]);
    }

    public function getReportTwoFirstTruckIn($from,$to)
    {
        $this->getAllLocations();
        $geofences = $this->allGeofences;
        $templocations = ReportTwo::select("tokyo_site_name")->get();
        foreach($templocations as $templocation){
            foreach($geofences as $geofence){
                if($templocation['tokyo_site_name']==$geofence['name']){
                    $this->getSessionEID();
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://hst-api.wialon.com/wialon/ajax.html?svc=report/exec_report',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => 'params={"reportResourceId":16798032,"reportTemplateId":46,"reportTemplate":null,"reportObjectId":16798032,"reportObjectSecId":"'.$geofence['id'].'","interval":{"flags":16777216,"from":'.$from.',"to":'.$to.'}}&sid='.$this->eid,
                    CURLOPT_HTTPHEADER => array(
                        'accept: */*',
                        'accept-language: en-GB,en-US;q=0.9,en;q=0.8',
                        'content-type: application/x-www-form-urlencoded',
                        'dnt: 1',
                        'origin: https://hst-api.wialon.com',
                        'priority: u=1, i',
                        'referer: https://hst-api.wialon.com/wialon/post.html',
                        'sec-ch-ua: "Not)A;Brand";v="99", "Google Chrome";v="127", "Chromium";v="127"',
                        'sec-ch-ua-mobile: ?0',
                        'sec-ch-ua-platform: "Windows"',
                        'sec-fetch-dest: empty',
                        'sec-fetch-mode: cors',
                        'sec-fetch-site: same-origin',
                        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36'
                    ),
                    ));

                    $response = curl_exec($curl);
                    curl_close($curl);
                    $responseDecoded = json_decode($response);   
                    $rows = 0;
                    isset($responseDecoded->reportResult->tables[0]->rows)? $rows = $responseDecoded->reportResult->tables[0]->rows : 0 ;
                    $data = [];
                    if($rows>0){
                        for($i=0;$i<$rows;$i++){
                            $records = $this->reportGetRecords($i);
                            $records = json_decode($records,1);
                            $sI = 0;
                            foreach($records as $record){
                                $dat = [];
                                print_r($record);
                                $dat[$sI]['tokyo_pump_car_id']    = $record['uid'] ?? null;
                                $dat[$sI]['tokyo_pump_car_name']  = $record['c'][2] ?? null;
                                $dat[$sI]['tokyo_location_name']  = $record['c'][3] ?? null;
                                $dat[$sI]['tokyo_plant_out_time'] = $record['c'][4]['t'] ?? null;
                                array_push($data,$dat[$sI]);
                            }
                        }
                }
                }
            }
        }
    }

    public function getReportTwoFirstTruckInTime()
    {
        $report = ReportTwo::get();
        $this->getSessionEID();
        $this->setTimeZone();
        foreach($report as $record){

            $from            = $record->tokyo_site_in_time;
            $fromUnix        = Carbon::parse($record->tokyo_site_in_time,'GMT+5:30')->timestamp;
            $to              = Carbon::parse($from)->addHours(6);
            $toUnix          = strtotime($to);
            $result          = $this->getReportTwoTruckTimesFromAPI($record->tokyo_pump_site_id,$fromUnix,$toUnix);
            $responseDecoded = json_decode($result);

            if(isset($responseDecoded->reportResult)){
                if(!empty($responseDecoded->reportResult->tables)){
                    isset($responseDecoded->reportResult->tables[0]->rows)? $rows = $responseDecoded->reportResult->tables[0]->rows : 0 ;
                    if($rows>0){
                        $reportResult = $this->reportGetAllRecords();
                        $reportResult = json_decode($reportResult,true);
                        foreach($reportResult as $reportRow){
                            if(isset($reportRow['c'][2]['t'])){
                                $time = $reportRow['c'][2]['t'];
                                $timeParsed = Carbon::parse($time);
                                $fromTimePumpCar = Carbon::parse($record->tokyo_site_in_time);
                                if($timeParsed->greaterThan($fromTimePumpCar)){
                                    $record->tokyo_first_truck_in_name = $reportRow['c'][1];
                                    $record->tokyo_first_truck_in_time = $time;
                                    // Idle Time
                                    $diffInSeconds = $timeParsed->diffInSeconds($fromTimePumpCar);
                                    $diffDateTime = Carbon::createFromTimestampUTC($diffInSeconds)->format('H:i:s');
                                    $record->tokyo_pump_idle_time = $diffDateTime;
                                    $record->save();
                                    break;
                                } 
                            }
                        }
                    }
                }
            }
        }
    }

    public function reportGetAllRecords()
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://hst-api.wialon.com/wialon/ajax.html?svc=report/select_result_rows&sid='.$this->eid.'&params={"tableIndex":0,"config":{"type":"range","data":{"from":0,"to":19,"level":0,"unitInfo":1}}}',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function getReportTwoTruckTimesFromAPI($geofence,$from,$to)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://hst-api.wialon.com/wialon/ajax.html?svc=report/exec_report&params={"reportResourceId":16798032,"reportTemplateId":46,"reportTemplate":null,"reportObjectId":16798032,"reportObjectSecId":"'.$geofence.'","interval":{"flags":16777216,"from":'.$from.',"to":'.$to.'}}&sid='.$this->eid,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    
    public function setTimeZone()
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://hst-api.wialon.com/wialon/ajax.html?svc=render%2Fset_locale&params={%22tzOffset%22%3A19800%2C%22language%22%3A%22en%22}&sid='.$this->eid,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        ));
        $response = curl_exec($curl);
        curl_close($curl);
    }

    public function getPeliyagodaYardData($from,$to)
    {
        $this->getSessionEID();
        $this->setTimeZone();
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://hst-api.wialon.com/wialon/ajax.html?svc=report/exec_report&params={"reportResourceId":16798032,"reportTemplateId":50,"reportTemplate":null,"reportObjectId":16798326,"reportObjectSecId":0,"interval":{"flags":16777216,"from":'.$from.',"to":'.$to.'},"reportObjectIdList":[]}&sid='.$this->eid,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($response,true);
        return $result;
    }

    public function getKandyPlantData($from,$to)
    {
        $this->getSessionEID();
        $this->setTimeZone();
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://hst-api.wialon.com/wialon/ajax.html?svc=report/exec_report&params={"reportResourceId":16798032,"reportTemplateId":51,"reportTemplate":null,"reportObjectId":17739115,"reportObjectSecId":0,"interval":{"flags":16777216,"from":'.$from.',"to":'.$to.'},"reportObjectIdList":[]}&sid='.$this->eid,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($response,true);
        return $result;
    }
}
