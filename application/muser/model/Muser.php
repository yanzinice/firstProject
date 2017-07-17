<?php

namespace app\muser\model;
use think\Loader;
use think\Model;
use think\Validate;
use think\Exception;
/**
 * 用户表
 * 
 * @author cuiyan
 */
class Muser extends Model{
	protected $tableName = "muser";
	//protected $table = 'm_user';
	protected $rule = [
		'name'  => 'require|unique:muser',
		'passwd'   => 'require'
	];
	protected $message  =   [
		'name.require' => '用户名必须',
		'name.unique' => '用户名必须唯一',
		//'name.max'     => '名称最多不能超过25个字符',
	];

	public function userLogin($name,$passwd){

		$data = db('muser')
			->where('name',$name)
			->where('passwd',$passwd)
			->select();

		if(empty($data)){
			$data = [];
		}
		return $data;
	}

	/**
	 * 添加用户
	 * 
	 * @param $data array
	 *        	插入数据表的键值对
	 */
	public function addUser($data = []) {
		if (empty ( $data )) {

			throw new Exception('插入数据不能为空！',100006);
		}
		$muser = new Muser();
		/*$result = $muser->validate($this->rule,$this->message);//->save($data);
		$result   = $validate->check($data);*/
		$validate =new Validate($this->rule,$this->message);
		$res   =$validate->check($data);

		if(false === $res){
			// 验证失败 输出错误信息
			//return $validate->getError();
			throw new Exception($validate->getError(),100006);
		}
		/*$result = $muser->validate($this->rule,$this->message)->save($data);
		if(false === $result){
			// 验证失败 输出错误信息
			return $muser->getError();
		}*/
		/*$res = $muser->validate($this->rule,$this->message)->save($data);
		
		if(false === $res){
			// 验证失败 输出错误信息
			return $muser->getError();
		}*/

		//$validate = validate('User');
		/*$validate =new Validate($this->rule,$this->message);
		$res   =$validate->check($data);
		if(!$res){
			return $validate->getError();
		}*/

		$result = db('muser')->insert($data);

		if ($result !== false) {
			return $result;
		} else {
			throw new Exception('添加失败',100006);
		}
	}


}