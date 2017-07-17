<?php
namespace   app\metting\controller;

use think\Controller;
use app\metting\model\Metting as MettingModel;

class Metting extends Controller{
    
    //添加会议室
    public function addMetting($data){

        //$data = array_map_recursive('htmlspecialchars',$_REQUEST);
        $mettingData = [

            'metting_name'=>$data['metting_name'],
            'number'=>$data['number'],
            'create_time'=>date('Y-m-d H:i:s'),
        ];
        $user = new MettingModel();
        $res = $user ->addMetting($mettingData);
        if($res){
            return array('info'=>'添加成功','status'=>1);
        }else{
            return array('info'=>'添加失败','status'=>0);
        }
    }

    //测试
    public function testaddMetting(){

        $mettingData = [

            'metting_name'=>'楚楚街',
            'number'=>10,
            'create_time'=>date('Y-m-d H:i:s'),
        ];
        $res = $this->addMetting($mettingData);
        return $res;
    }
}