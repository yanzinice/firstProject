<?php
namespace  app\user\validate;
use think\Validate;

class User extends Validate{
    protected $rule = [
        'name'  =>  'require|max:25',
        'pass'=>'require',
    ];

    protected $message = [
        'name.require'  =>  '用户名必须',
        'pass.require'=>'密码必须',
    ];

    protected $scene = [
        'add'   =>  ['name','pass'],
        'edit'  =>  ['name'],
    ];
}