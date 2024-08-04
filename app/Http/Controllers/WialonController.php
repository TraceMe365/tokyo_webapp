<?php

namespace App\Http\Controllers;

use App\Models\ReportOne;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WialonController extends Controller
{
    protected $token;
    protected $eid;
    protected $locations;

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
        $this->locations($responseDecoded['items']);
    }

    public function getReportOne(Request $request)
    {   
        try {
            $data = $request->all();
            $from = Carbon::parse($data['from'])->timestamp;
            $to   = Carbon::parse($data['to'])->timestamp;
            // Get plant and vehicle details for duration
            if($to>$from){
                DB::table('report_one')->truncate();
                $this->reportOnePlantDurations($from,$to);
                $this->reportOneSiteDurations($from,$to);
                return response()->json(['data'=>ReportOne::get()]);    
            }
            else{
                return response()->json(['message'=>'Please enter a valid date range']);    
            }
        } catch (Exception $e) {
            return response()->json(['Error'=>$e->getMessage(),"Line"=>$e->getLine()]);
        }
        
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
        CURLOPT_POSTFIELDS => 'params={"reportResourceId":16798032,"reportTemplateId":43,"reportTemplate":null,"reportObjectId":16798032,"reportObjectSecId":"4","interval":{"flags":16777216,"from":'.$from.',"to":'.$to.'}}&sid='.$this->eid,
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
                    // print_r($siteOutTime->diffForHumans($plantOutTime));
                    // die();
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
}
