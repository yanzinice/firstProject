<?php

namespace app\metting\model;

use think\Model;
use think\Exception;
use think\Validate;

/**
 * 会议室
 *
 * @author cuiyan
 */
class Metting extends Model {
    protected $tableName = "metting";

    protected $rule = [
        'metting_name'  =>  'require|unique:metting',
    ];

    protected $message = [
        'metting_name.require'  =>  '会议室必须',
        'metting_name.unique'  =>  '会议室名称不能重复',
    ];

    /**
     * 添加用户
     *
     * @param $data array
     *        	插入数据表的键值对
     */
    public function addMetting($data = []) {
        if (empty ( $data )) {
            throw new Exception('插入数据不能为空！',100006);
        }

        /*$metting = new Metting();
        $result = $metting->validate($this->rule,$this->message)->save($data);*/
        //$result = $metting->table($this->tableName)->insert($data);
        $validate =new Validate($this->rule,$this->message);
        $res   =$validate->check($data);
        if(false === $res){
            // 验证失败 输出错误信息
            //return $validate->getError();
            throw new Exception($validate->getError(),100006);
        }
        $result =$this->insert($data);
        if ($result !== false) {
            return $result;
        } else {
            throw new Exception('添加失败',100006);
        }
    }

}