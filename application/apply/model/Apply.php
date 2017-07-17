<?php

namespace app\apply\model;

use think\Model;
use think\Exception;
use think\Validate;

class Apply extends Model{

    protected $tableName = "apply";

    protected $rule = [
        'metting_id'  =>  'require',
        'user_id' =>  'require',
        "starttime"=>'require',
        "endtime"=>'require',
        "date"=>'require',
    ];

    protected $message = [
        'metting_id.require'  =>  '会议室必须',
        'user_id.require'  =>  '申请者必须',
        "starttime.require"=>'会议室预定开始时间必填！',
        "endtime.require"=>'会议室预定结束时间必填！',
        "date.require"=>'会议室预定日期必填！',
    ];


    /**
     * 申请会议室
     */
    public function applyMetting($data = []) {
        if (empty ( $data )) {
            throw new Exception('插入数据不能为空！',100006);
        }
        /*$Apply = new Apply();
        $res = $Apply->validate($this->rule,$this->message)->save($data);*/

        $validate =new Validate($this->rule,$this->message);
        $res   =$validate->check($data);
        if(false === $res){
            // 验证失败 输出错误信息
            //return $validate->getError();
            throw new Exception($validate->getError(),100006);
        }

        //$result = $this->table($this->tableName)->insert($data);
        $result = $this->insert($data);
        if ($result !== false) {
            return $result;
        } else {
            throw new Exception('添加失败',100006);
        }
    }

    //获取会议室信息
    public function getApplyByCondition($condition){

        $where="1=1";
        if(isset($condition['date']) && $condition['date'] ){
            $where .=" and apply.date='{$condition["date"]}' ";
        }
        if(isset($condition['metting_id']) && $condition['metting_id'] ){
            $where .=" and apply.metting_id={$condition['metting_id']} ";
        }
        if(isset($condition['user_id']) && $condition['user_id']){
            $where .=" and apply.user_id={$condition['user_id']} ";
        }

         $sql="select 
            muser.name,metting.metting_name,`muser`.user_id,metting.metting_id,
            apply.id,apply.date,apply.starttime,apply.endtime,
            apply.number,apply.status,apply.title
            from `apply`
            join `muser`
            on `apply`.user_id=`muser`.user_id
            join `metting`
            on `apply`.metting_id= `metting`.metting_id
            where {$where}
            order BY apply.starttime
        ";
        $data = $this->query($sql);
        if(empty($data)){
            return [];
        }
     
        foreach ($data as $k=> &$v){
            $v['starttime']=date('H:i',strtotime($v['starttime']));
            $v['endtime']=date('H:i',strtotime($v['endtime']));
            $v['date1']=strtotime($v['date']);
            $v['starttime1']=strtotime($v['starttime']);
            $v['endtime1']=strtotime($v['endtime']);
        }
        return $data;
    }
    //获取所有的会议室信息
    public function getAllMettingInfo($date){
        $where ='';
        /*$curTime=date('H:i:s');
        $where=" apply.endtime>'{$curTime}'";*/
        if($date){
            $where =" where apply.date='{$date}'";
        }

         $sql="
        select apply.metting_id,apply.starttime,apply.endtime,apply.date,
        metting.metting_name
        from apply
        join metting
        on apply.metting_id = metting.metting_id
        {$where} 
        ";
        $data = $this->query($sql);
        if(empty($data)){
            return [];
        }
        return $data;
    }

    //取消会议室
    public function cancleMettingById($id){

        $res = $this->where('id',$id)->update(['status'=>2]);
        return $res;
    }

    //编辑会议室
    public function editMettingById($id,$data){
        $res = $this->where('id',$id)->update($data);
        return $res;
    }
}