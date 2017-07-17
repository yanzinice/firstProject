<?php

namespace   app\muser\controller;

//use Common\Cls\WrapController;
use think\Controller;
use app\muser\model;

class Muser extends Controller{
    
    //添加新用户
    public function addUser($data){

        //$data =array_map_recursive('htmlspecialchars',$_REQUEST);
        $userData = [

            'name'=>$data['name'],
            'telphone'=>$data['telphone'],
            'create_time'=>date('Y-m-d H:i:s'),
            'passwd'=>md5($data['passwd'])
        ];
      
        $user = new model\Muser();
        $res = $user ->addUser($userData);

        if($res){
            return ['info'=>'添加成功','status'=>1];
            //echo json_encode(array('info'=>'添加成功','status'=>1),JSON_UNESCAPED_UNICODE);
        }else{
            return ['info'=>'添加失败','status'=>0];
            //echo json_encode(array('info'=>'添加失败','status'=>0),JSON_UNESCAPED_UNICODE);
        }
    }


    //登陆
    public function login(){

        //$_REQUEST="{\"name\":\"赵晓倩\",\"passwd\":\"11010\"}";


        //var_dump($_REQUEST);die;
        //$aa = json_decode($_REQUEST['data'],true);
        //$aa = json_decode($_REQUEST['data'],true);
        //file_put_contents('loglog.log',var_dump($_REQUEST),true);
        $data =array_map_recursive('htmlspecialchars',$_REQUEST);

        $user = new model\Muser();
        $name = $data['name'];
        $passwd = $data['passwd'];
        $res = $user->userLogin($name,$passwd);
        if(empty($res)){
            $status=0;
        }else{
            $status=1;
        }
        return ['status'=>$status,'info'=>'','data'=>$res[0]];
        //$user->getUserByName($data);
    }


    public function getAddUser(){

        $data = [

            'name'=>'小米',
            'telphone'=>'18612694562',
            'create_time'=>date('Y-m-d H:i:s'),
            'passwd'=>md5('123456')
        ];
        $res = $this->addUser($data);
        return $res;
    }
}