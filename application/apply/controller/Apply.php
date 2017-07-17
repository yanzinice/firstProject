<?php
namespace   app\apply\controller;

//use Common\Cls\WrapController;
use app\muser\model\Muser;
use think\Controller;
use app\apply\model;
//use app\apply\model\Apply;

class Apply extends Controller {

    //预定会议室
    public function applyMetting(){

        $data = array_map_recursive('htmlspecialchars',$_REQUEST);
        $mettingData=[

            'user_id'=>$data['user_id'],
            'metting_id'=>$data['metting_id'],
            'date'=>$data['date'],
            'starttime'=>$data['starttime'],
            'endtime'=>$data['endtime'],
            'number'=>$data['number'],
            'title'=>$data['title'],
            'create_time'=>date('Y-m-d H:i:s')
        ];
        $apply = new model\Apply();
        $res = $apply->applyMetting($mettingData);
        if($res){
            return array('info'=>'申请成功','status'=>1);
        }else{
            return array('info'=>'申请失败','status'=>0);
        }
    }

    //获取所有会议室
    public function getAllMetting(){

        $data = array_map_recursive('htmlspecialchars',$_REQUEST);
        if(!isset($data['date'])|| empty($data['date'])){
            return array('status'=>0,'info'=>'请选择日期','data'=>'');
        }
        $date = $data['date'];
        
        $apply = new model\Apply();
        $res = $apply->getAllMettingInfo($date);

        if($res){
            $res = $this->getMettingStatus($res,$date);
        }
        $data['list']=$res;
        return ['status'=>1,'info'=>'','data'=>$data];
    }

    //会议室是否已满
    public function getMettingStatus($data,$date){

        $arr =[];

        $curDate=date('H:i:s');
        $curTime = date('H:i:s');
        $maxTime='18:30:00';
        if($date>$curDate){
            $leftTime = strtotime($maxTime)-strtotime('09:30:00');
        }else{
            $leftTime = strtotime($maxTime)-strtotime($curTime);
        }

        foreach ($data as $k=>$v){
            //if(strtotime($v['endtime'])>strtotime($curTime)){
                if(isset($arr[$v['metting_id'].$v['date']])){
                    array_push($arr[$v['metting_id'].$v['date']],$v);
                }else{

                    $arr[$v['metting_id'].$v['date']][]=$v;
                }
            //}
        }

        $applyTime=0;
        $newArr = [];
        foreach ($arr as $k1=>$v1){

            foreach ($v1 as $k2=>$v2){
                if($v2['date']>$curDate){
                    $applyTime += strtotime($v2['endtime'])-strtotime($v2['starttime']);
                }else{
                    if(strtotime($v2['endtime'])>strtotime($curTime)){
                        $applyTime += strtotime($v2['endtime'])-strtotime($v2['starttime']);
                    }
                }
            }
            //var_dump($applyTime/60);
            if($leftTime-$applyTime-30*60>=0){

                $metting_status=1;
            }else{
                $metting_status=0;
            }
            $newArr[]=[
                'metting_id'=>$v1[0]['metting_id'],
                'date'=>$v1[0]['date'],
                'metting_status'=>$metting_status,
                'metting_name'=>$v1[0]['metting_name']
            ];

        }
        return $newArr;
    }

    //获取某个会议室申请情况 todo
    public function getApplyMetting(){
        $data = array_map_recursive('htmlspecialchars',$_REQUEST);
        if(!isset($data['date'])|| empty($data['date'])){
            return ['status'=>0,'info'=>'所传日期不能为空','data'=>''];
        }
        if(!isset($data['metting_id'])|| empty($data['metting_id'])){
            return ['status'=>0,'info'=>'所选会议室不能为空','data'=>''];
        }
        $condition=[
            'date'=>$data['date'],
            'metting_id'=>$data['metting_id']
        ];

        $apply = new model\Apply();
        $res = $apply ->getApplyByCondition($condition);

        if($res){
            $res = $this->formatMetting($res,$data['date']);
        }
        $arr['list']=$res;
        return ['status'=>1,'info'=>'','data'=>$arr];
    }

    //其他日期的会议室预定状况
    private function otherDateMetting($data){

        $curTime =date('H:i');
        $curTimes = strtotime($curTime);
        $maxTime = strtotime('18:30');
        $minTime = strtotime('09:30');
        $minTime1 = '09:30';
        $maxTime1 = '18:30';

        $curDate = date('Y-m-d');
        $len = count($data);
        $empArr=[];
        if($len==1){
            if($data[0]['starttime1']-$minTime>=30*60){
                $empArr[]=[
                    'starttime'=>$minTime1,
                    'endtime'=>$data[0]['starttime'],
                    'status'=>0,//代表该时段会议室可用
                    "name"=> "",
                    "metting_name"=> $data[0]['metting_name'],
                    "user_id"=> 0,
                    "metting_id"=>$data[0]['metting_id'],
                    "id"=> 0,
                    "date"=> '',
                    "number"=> 0,
                    "title"=>"",
                    'date1'=>'',
                    'starttime1'=>$minTime,
                    'endtime1'=>$data[0]['starttime1']
                ];
            }
        }else{
            for($i=0;$i<$len-1;$i++){

                if($data[$i]['starttime1']-$minTime>=30*60){
                    $empArr[]=[
                        'starttime'=>$minTime1,
                        'endtime'=>$data[$i]['starttime'],
                        'status'=>0,//代表该时段会议室可用
                        "name"=> "",
                        "metting_name"=> $data[$i]['metting_name'],
                        "user_id"=> 0,
                        "metting_id"=>$data[$i]['metting_id'],
                        "id"=> 0,
                        "date"=> '',
                        "number"=> 0,
                        "title"=>"",
                        'date1'=>'',
                        'starttime1'=>$minTime,
                        'endtime1'=>$data[$i]['starttime1']
                    ];
                }else if($data[$i+1]['starttime1']-$data[$i]['endtime1']>=30*60){
                    $empArr[]=[
                        'starttime'=>$data[$i]['endtime'],
                        'endtime'=>$data[$i+1]['starttime'],
                        'status'=>0,//代表该时段会议室可用
                        "name"=> "",
                        "metting_name"=> $data[$i]['metting_name'],
                        "user_id"=> 0,
                        "metting_id"=>$data[$i]['metting_id'],
                        "id"=> 0,
                        "date"=> '',
                        "number"=> 0,
                        "title"=>"",
                        'date1'=>'',
                        'starttime1'=>$data[$i]['endtime1'],
                        'endtime1'=>$data[$i+1]['starttime1']
                    ];
                }
            }
        }

        if(($maxTime-$data[$len-1]['endtime1']>30*60) ){

            $empArr[]=[
                'starttime'=>$data[$len-1]['endtime'],
                'endtime'=>$maxTime1,
                'status'=>0,
                "name"=> "",
                "metting_name"=> $data[$len-1]['metting_name'],
                "user_id"=> 0,
                "metting_id"=>$data[$len-1]['metting_id'],
                "id"=> 0,
                "date"=> '',
                "number"=> 0,
                "title"=>"",
                'date1'=>'',
                'starttime1'=>$data[$len-1]['endtime1'],
                'endtime1'=>$maxTime
            ];
        }

        if($empArr){
            foreach ($empArr as $key=>$value){

                array_push($data,$value);
            }
        }
        return $data;
    }

    private function aaa($starttime,$endtime,$mettingName,$mettingId,$starttime1,$endtime1){

        $empArr=[
            'starttime'=>$starttime,
            'endtime'=>$endtime,
            'status'=>0,
            "name"=> "",
            "metting_name"=> $mettingName,
            "user_id"=> 0,
            "metting_id"=>$mettingId,
            "id"=> 0,
            "date"=> '',
            "number"=> 0,
            "title"=>"",
            'date1'=>'',
            'starttime1'=>$starttime1,
            'endtime1'=>$endtime1
        ];
        return $empArr;
    }
    //获取会议室可用时间段
    public function formatMetting($data,$date){
        $curTime =date('H:i');
        $curTimes = strtotime($curTime);
        $maxTime = strtotime('18:30');
        $minTime = strtotime('09:30');
        $minTime1 = '09:30';
        $maxTime1 = '18:30';

        $curDate = date('Y-m-d');
        $len = count($data);
        $empArr=[];

        if($date>$curDate){

            //其他日期的会议室预定状况
            //$data = $this->otherDateMetting($data);
            if($len==1){
                if($data[0]['starttime1']-$minTime>=30*60){
                    /*$empArr[]=[
                        'starttime'=>$minTime1,
                        'endtime'=>$data[0]['starttime'],
                        'status'=>0,//代表该时段会议室可用
                        "name"=> "",
                        "metting_name"=> $data[0]['metting_name'],
                        "user_id"=> 0,
                        "metting_id"=>$data[0]['metting_id'],
                        "id"=> 0,
                        "date"=> '',
                        "number"=> 0,
                        "title"=>"",
                        'date1'=>'',
                        'starttime1'=>$minTime,
                        'endtime1'=>$data[0]['starttime1']
                    ];*/
                    $empArr[]=$this->aaa($minTime1,$data[0]['starttime'],$data[0]['metting_name'],$data[0]['metting_id'],$minTime,$data[0]['starttime1']);
                }
            }else{
                for($i=0;$i<$len-1;$i++){

                    if($data[$i]['starttime1']-$minTime>=30*60){
                        /*$empArr[]=[
                            'starttime'=>$minTime1,
                            'endtime'=>$data[$i]['starttime'],
                            'status'=>0,//代表该时段会议室可用
                            "name"=> "",
                            "metting_name"=> $data[$i]['metting_name'],
                            "user_id"=> 0,
                            "metting_id"=>$data[$i]['metting_id'],
                            "id"=> 0,
                            "date"=> '',
                            "number"=> 0,
                            "title"=>"",
                            'date1'=>'',
                            'starttime1'=>$minTime,
                            'endtime1'=>$data[$i]['starttime1']
                        ];*/
                        $empArr[]=$this->aaa($minTime1,$data[$i]['starttime'],$data[$i]['metting_name'],$data[$i]['metting_id'],$minTime,$data[$i]['starttime1']);
                    }else if($data[$i+1]['starttime1']-$data[$i]['endtime1']>=30*60){
                        /*$empArr[]=[
                            'starttime'=>$data[$i]['endtime'],
                            'endtime'=>$data[$i+1]['starttime'],
                            'status'=>0,//代表该时段会议室可用
                            "name"=> "",
                            "metting_name"=> $data[$i]['metting_name'],
                            "user_id"=> 0,
                            "metting_id"=>$data[$i]['metting_id'],
                            "id"=> 0,
                            "date"=> '',
                            "number"=> 0,
                            "title"=>"",
                            'date1'=>'',
                            'starttime1'=>$data[$i]['endtime1'],
                            'endtime1'=>$data[$i+1]['starttime1']
                        ];*/
                        $empArr[]=$this->aaa($data[$i]['endtime'],$data[$i+1]['starttime'],$data[$i]['metting_name'],$data[$i]['metting_id'],$data[$i]['endtime1'],$data[$i+1]['starttime1']);
                    }
                }
            }

            if(($maxTime-$data[$len-1]['endtime1']>30*60) ){

                /*$empArr[]=[
                    'starttime'=>$data[$len-1]['endtime'],
                    'endtime'=>$maxTime1,
                    'status'=>0,
                    "name"=> "",
                    "metting_name"=> $data[$len-1]['metting_name'],
                    "user_id"=> 0,
                    "metting_id"=>$data[$len-1]['metting_id'],
                    "id"=> 0,
                    "date"=> '',
                    "number"=> 0,
                    "title"=>"",
                    'date1'=>'',
                    'starttime1'=>$data[$len-1]['endtime1'],
                    'endtime1'=>$maxTime
                ];*/
                $empArr[]=$this->aaa($data[$len-1]['endtime'],$maxTime1,$data[$len-1]['metting_name'],$data[$len-1]['metting_id'],$data[$len-1]['endtime1'],$maxTime);
            }

            if($empArr){
                foreach ($empArr as $key=>$value){

                    array_push($data,$value);
                }
            }
        }else{
            if($len==1){
                if(($data[0]['starttime1']-$minTime>=30*60) && ($data[0]['starttime1']-$curTimes>=30*60) ){
                    $empArr[]=[
                        'starttime'=>$minTime1,
                        'endtime'=>$data[0]['starttime'],
                        'status'=>0,//代表该时段会议室可用
                        "name"=> "",
                        "metting_name"=> $data[0]['metting_name'],
                        "user_id"=> 0,
                        "metting_id"=>$data[0]['metting_id'],
                        "id"=> 0,
                        "date"=> '',
                        "number"=> 0,
                        "title"=>"",
                        'date1'=>'',
                        'starttime1'=>$minTime,
                        'endtime1'=>$data[0]['starttime1']
                    ];
                }
            }else{
                for($i=0;$i<$len-1;$i++){

                    //按预定会议室时间排序，第一条申请开始时间大于当前时间半小时，如果当前时间小于工作开始最小时间（9:30）9:30到第一个申请会议之前还可以预定，
                    //如果当前时间大于9:30 分钟数大于30分钟，就从下个整点时间开始到第一个申请会议之前开始时间还可以预定，小于30分钟就从该时间的的半点开始到第一个申请会议之前
                    //第一条申请开始时间小于当前时间半小时，第二条记录开始时间大于当前时间半小时且第二条开始时间大于第一条结束时间半小时 可预订时间为第一条结束时间到第二条开始时间
                    if($data[$i]['starttime1']-$curTimes>=30*60){

                        if($minTime-$curTimes>0){
                            $empArr[]=[
                                'starttime'=>$minTime1,
                                'endtime'=>$data[$i]['starttime'],
                                'status'=>0,
                                "name"=> "",
                                "metting_name"=> $data[$i]['metting_name'],
                                "user_id"=> 0,
                                "metting_id"=>$data[$i]['metting_id'],
                                "id"=> 0,
                                "date"=> '',
                                "number"=> 0,
                                "title"=>"",
                                'date1'=>'',
                                'starttime1'=>$minTime,
                                'endtime1'=>$data[$i]['starttime1']
                            ];
                        }else{
                            $time = explode(':',$curTime);
                            if($time[1]>=30){
                                //$time[0]+1;
                                $empArr[]=[
                                    'starttime'=>($time[0]+1).':00',
                                    'endtime'=>$data[$i]['starttime'],
                                    'status'=>0,
                                    "name"=> "",
                                    "metting_name"=> $data[$i]['metting_name'],
                                    "user_id"=> 0,
                                    "metting_id"=>$data[$i]['metting_id'],
                                    "id"=> 0,
                                    "date"=> '',
                                    "number"=> 0,
                                    "title"=>"",
                                    'date1'=>'',
                                    'starttime1'=>strtotime(($time[0]+1).':00'),
                                    'endtime1'=>$data[$i]['starttime1']
                                ];
                            }else{
                                $empArr[]=[
                                    'starttime'=>($time[0]).':30',
                                    'endtime'=>$data[$i]['starttime'],
                                    'status'=>0,
                                    "name"=> "",
                                    "metting_name"=> $data[$i]['metting_name'],
                                    "user_id"=> 0,
                                    "metting_id"=>$data[$i]['metting_id'],
                                    "id"=> 0,
                                    "date"=> '',
                                    "number"=> 0,
                                    "title"=>"",
                                    'date1'=>'',
                                    'starttime1'=>strtotime(($time[0]).':30'),
                                    'endtime1'=>$data[$i]['starttime1']
                                ];
                            }
                        }
                    }else if($data[$i+1]['starttime1']-$curTimes>=30*60){

                        if($data[$i+1]['starttime1']-$data[$i]['endtime1']>=30*60){
                            $empArr[]=[
                                'starttime'=>$data[$i]['endtime'],
                                'endtime'=>$data[$i+1]['starttime'],
                                'status'=>0,
                                "name"=> "",
                                "metting_name"=> $data[$i]['metting_name'],
                                "user_id"=> 0,
                                "metting_id"=>$data[$i]['metting_id'],
                                "id"=> 0,
                                "date"=> '',
                                "number"=> 0,
                                "title"=>"",
                                'date1'=>'',
                                'starttime1'=>$data[$i]['endtime1'],
                                'endtime1'=>$data[$i+1]['starttime1']
                            ];
                        }
                    }
                }
            }

            if(($maxTime-$data[$len-1]['endtime1']>30*60)&& ($maxTime-$curTimes>30*60) ){

                $empArr[]=[
                    'starttime'=>$data[$len-1]['endtime'],
                    'endtime'=>$maxTime,
                    'status'=>0,
                    "name"=> "",
                    "metting_name"=> $data[$len-1]['metting_name'],
                    "user_id"=> 0,
                    "metting_id"=>$data[$len-1]['metting_id'],
                    "id"=> 0,
                    "date"=> '',
                    "number"=> 0,
                    "title"=>"",
                    'date1'=>'',
                    'starttime1'=>$data[$len-1]['endtime1'],
                    'endtime1'=>$maxTime
                ];
            }
            if($empArr){
                foreach ($empArr as $key=>$value){

                    array_push($data,$value);
                }
            }
        }

        return $data;
    }
    //格式化会议室数据
    public function testformatMetting($data){

        $curTime =date('H:i:s');
        $maxTime = '18:30:00';
        $minTime = '09:30:00';
        /*foreach ($data as $k=>$v){
            foreach ($)
            if(strtotime($v['']))
        }*/
        $len = count($data);
        $empArr=[];
        $time=[];
        for($i=0;$i<$len-1;$i++){

            //按预定会议室时间排序，第一条申请开始时间大于当前时间半小时，如果当前时间小于工作开始最小时间（9:30）9:30到第一个申请会议之前还可以预定，
            //如果当前时间大于9:30 分钟数大于30分钟，就从下个整点时间开始到第一个申请会议之前开始时间还可以预定，小于30分钟就从该时间的的半点开始到第一个申请会议之前
            //第一条申请开始时间小于当前时间半小时，第二条记录开始时间大于当前时间半小时且第二条开始时间大于第一条结束时间半小时 可预订时间为第一条结束时间到第二条开始时间
                if(strtotime($data[$i]['starttime'])-strtotime($curTime)>=30*60){

                    if(strtotime($minTime)-strtotime($curTime)>0){
                        $empArr[]=[
                                'starttime'=>$minTime,
                                'endtime'=>$data[$i]['starttime']
                            ];
                    }else{
                        $time = explode(':',$curTime);
                        if($time[1]>=30){
                            //$time[0]+1;
                            $empArr[]=[
                                'starttime'=>($time[0]+1).'00:00',
                                'endtime'=>$data[$i]['starttime']
                            ];
                        }else{
                            $empArr[]=[
                                'starttime'=>($time[0]).'30:00',
                                'endtime'=>$data[$i]['starttime']
                            ];
                        }
                    }
                }else if(strtotime($data[$i+1]['starttime'])-strtotime($curTime)>=30*60){

                    if(strtotime($data[$i+1]['starttime'])-strtotime($data[$i]['endtime'])>=30*60){
                        $empArr[]=[
                            'starttime'=>$data[$i]['endtime'],
                            'endtime'=>$data[$i+1]['starttime']
                        ];
                    }
                }
            }

        if((strtotime($maxTime)-strtotime($data[$len-1]['endtime'])>30*60)&& (strtotime($maxTime)-strtotime($curTime)>30*60) ){

            $empArr[]=[
                'starttime'=>$data[$len-1]['endtime'],
                'endtime'=>$maxTime
            ];
        }
        if($empArr){
            foreach ($empArr as $key=>$value){

                array_push($data,$value);
            }
        }

        return $data;

    }

    //获取我预定的会议室
    public function getMyMetting(){
        $data = array_map_recursive('htmlspecialchars',$_REQUEST);
        if(!isset($data['date']) || empty($data['date'])){
            return ['status'=>0,'info'=>'请选择时间','data'=>[]];
        }
        if(!isset($data['user_id']) || empty($data['user_id'])){
            return ['status'=>0,'info'=>'请登录','data'=>[]];
        }
        $condition =[
            'date'=>$data['date'],
            'user_id'=>$data['user_id']
        ];
        $apply = new model\Apply();
        $res = $apply -> getApplyByCondition($condition);

        $result['list']=$res;
        return ['status'=>1,'info'=>'','data'=>$result];
    }

    //取消会议室
    public function cancleMetting(){
        $data = array_map_recursive('htmlspecialchars',$_REQUEST);
        $id = intval($data['id']);
        $apply = new model\Apply();
        $res = $apply -> cancleMettingById($id);
        if($res!==false){
            return ['info'=>'取消成功','status'=>1];
        }else{
            return ['info'=>'取消失败','status'=>0];
        }
    }

    //编辑会议室
    public function editMetting(){
        $data = array_map_recursive('htmlspecial',$_REQUEST);
        $id = intval($data['id']);
        unset($data['id']);
        $apply = new model\Apply();
        $res = $apply -> editMettingById($id,$data);
        if($res!==false){
            return ['info'=>'取消成功','status'=>1];
        }else{
            return ['info'=>'取消失败','status'=>0];
        }
    }


//测试获取会议室 TODO
    public function testgetApplyMetting(){
        $condition=[
            'user_id'=>13,
            'metting_id'=>1,
            'date'=>'2017-02-22'
        ];
        $data = $this->getApplyMetting($condition);
        $aa['list']=$data;
        return ['status'=>1,'info'=>'','data'=>$aa];
    }

    public function testapplyMetting(){

        $mettingData=[

            'user_id'=>1,
            'metting_id'=>1,
            'date'=>'2017-02-24',
            'starttime'=>'09:30:00',
            'endtime'=>'10:30:00',
            'number'=>5,
            'create_time'=>date('Y-m-d H:i:s')
        ];
        $res = $this->applyMetting($mettingData);
        return $res;
    }
}