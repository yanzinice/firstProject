<?php

/**
 * 权限管理
 * @author songbin
 */
namespace User\Service;

use Common\Cls\WrapException;
use Marketing\Model\CouponUserCondModel;

use User\Model\UserModel;
use User\Model\UserBusinessModel;
use User\Model\UserStoreModel;
use User\Model\UserMarketModel;
use Store\Service\StoreService;
use Store\Model\StoreModel;

class UserService {
	public function getOneUserById($userId){
		$model = new UserModel();
		$row = $model->where(['uid' => $userId])->select();
		if(count($row) != 1){
			throwErrMsg('用户[' . $userId . ']不存在！');
		}
		return $row[0];
	}

    /**
     *
     * 根据用户 id 获取用户信息
     *
     * @param array $uids 用户 id，可以是整形或者数组
     * @param string $field 字段，默认为 'uid, type_user, user, name, phone, isadmin'
     *
     * @return array
     *
     * @throws \Common\Cls\WrapException
     */
    public function getSomeUsersByUids($uids, $field = 'uid, type_user, user, name, phone, isadmin') {

        // 参数验证
        if (empty($uids) || !is_int($uids) && !is_array($uids)) {

            throwErrMsg('参数错误');
        } else if (is_int($uids)) {

            $uids = (array)$uids;
        } else {

            $uids = array_unique($uids);
        }

        $model = new UserModel();

        try {
            $user = $model->selectUser(['uid' => ['in', $uids]], $field);

            return $user;
        } catch (\Common\Cls\WrapException $e) {

            throwErrMsg('查询失败');
        }
    }

	/**
	 * 通过用户标识获取与商家的关联数据
	 * @param int $userId
	 * @return array
	 */
	public function getUserBusinessByUserId($userId){
		if (empty($userId)){
			return [];
		}
		
		$condition = [];
		$condition['uid'] = $userId;
		
		$userBusinessModel = new UserBusinessModel();
		$userBusiness = $userBusinessModel -> selectUserBusiness($condition);
		return $userBusiness;
	}
	
	/**
	 * 获取一个商家或者门店用户所对应的门店
	 * @param unknown $userId
	 */
	public function getStoreByUserId($userId){
		$userModel = new UserModel();
		$condition['uid'] = $userId;
		$data = $userModel->selectUser($condition);
		//如果是商家管理员，则返回所有门店
		if($data[0]['type_user'] == USER_TYPE_COMMERCIAL && $data[0]['isadmin'] == 1){
			$userBusiness = $this->getUserBusinessByUserId($userId);
			$busId = $userBusiness[0]['bus_id'];
			$storeModel = new StoreModel();
			$storeData = $storeModel->where(['bus_id'=>$busId])->field("store_id")->select();
			$storeIds = [];
			foreach ($storeData as $v){
				$storeIds[] = $v['store_id'];
			}
			return $storeIds;
		}else{
			$storeUserModel = new UserStoreModel();
			$storeData = $storeUserModel->selectUserStore(['uid'=>$userId],'store_id');
			$storeIds = [];
			foreach ($storeData as $v){
				$storeIds[] = $v['store_id'];
			}
			return $storeIds;
		}
	}

    /**
     * 根据用户获取云超市
     *
     * @param $uid int
     * @return array
     */
    public function getMarketByUserId($userId) {
        if (empty($userId)) {
            return [];
        }

        $condition = [];
        $condition['uid'] = $userId;

        $userMarketModel = new UserMarketModel();
        $userMarket = $userMarketModel->selectUserMarket($condition);
        return $userMarket;
    }

    /**
     * 添加一些用户所属云超市
     *
     * @param $data array
     * @return int
     */
    public function addSomeUserMarkets($data = []) {
        $userMarketModel = new UserMarketModel();

        $return = $userMarketModel->addSomeUserMarkets($data);

        return $return;
    }

    /**
     * 添加一些用户所属商家
     *
     * @param $data array
     * @return int
     */
    public function addSomeUserBusinesses($data = []) {
        $userMarketModel = new UserBusinessModel();

        $return = $userMarketModel->addSomeUserBusinesses($data);

        return $return;
    }

    /**
     * 添加一些用户所属门店
     *
     * @param $data array
     * @return int
     */
    public function addSomeUserStores($data = []) {
        $userMarketModel = new UserStoreModel();

        $return = $userMarketModel->addSomeUserStores($data);

        return $return;
    }

    /**
     * 根据用户删除云超市
     *
     * @param $uid int
     * @return array
     */
    public function delMarketByUserId($userId) {
        if (empty($userId)) {
            return [];
        }

        $condition = [];
        $condition['uid'] = $userId;

        $userMarketModel = new UserMarketModel();
        $return = $userMarketModel->deleteUserMarket($condition);
        return $return;
    }

    /**
     * 根据用户删除商家
     *
     * @param $uid int
     * @return array
     */
    public function delBusinessByUserId($userId){
        if (empty($userId)) {
            return [];
        }

        $condition = [];
        $condition['uid'] = $userId;

        $userMarketModel = new UserBusinessModel();
        $return = $userMarketModel->deleteUserBusiness($condition);
        return $return;
    }

    /**
     * 根据用户删除门店
     *
     * @param $uid int
     * @return array
     */
    public function delStoreByUserId($userId){
        if (empty($userId)) {
            return [];
        }

        $condition = [];
        $condition['uid'] = $userId;

        $userMarketModel = new UserStoreModel();
        $return = $userMarketModel->deleteUserStore($condition);
        return $return;
    }

    /**
     * 获取一些用户信息
     * Model 层的入口
     *
     * @param string $condition 条件
     * @param string $fields    字段
     * @param string $order     排序
     * @param string $limit     限制
     *
     * @return array 包含结果集的二维数组
     * @throws WrapException 查询失败时抛出
     */
    public function getSomeUser($condition = '', $fields = '*', $order = '', $limit = '') {

        $user = [];
        try {

            $user = (new UserModel())->selectUser($condition, $fields, $order, $limit);
        } catch (WrapException $e) {

            throwErrMsg($e->getMessage());
        }

        return $user;
    }

    /**
     * @param $listData array 优惠券信息
     * @return $listData array 向原来的数组中添加的姓名
     */
    public function getCouponOperator($listData){

        if(empty($listData)){

            return [];
        }
        $uidArr = array_column($listData,'uid_create');

        if($uidArr){

            //根据uid获取用户姓名
            $uidArr = array_unique($uidArr);

            $userModel = new UserModel();
            $userData = $userModel -> selectUser(['uid' => ['in', $uidArr]], 'uid,name');

            $userData = array_column($userData,'name','uid');

            foreach($listData as $k => &$v){

                $v['userName'] = $userData[$v['uid_create']];
            }
        }

        return $listData;
    }

    /**
     * @param $timeType int 时间类型
     * @param $startDate time 开始时间
     * @param $endDate time 结束时间
     * @param $orderCount int 订单次数
     * @param $totalMoney float 消费金额
     * @param $goodsId int 商品id
     * @param $goodsNum int 商品数量
     * @param $lastDay int 距离最后下单时间天数
     * @param $phone
     *
     * @return array|mixed
     * @throws WrapException
     */
    public function getUserAndOrderInfo($timeType,$startDate,$endDate,$orderCount,$totalMoney,$goodsId,$goodsNum,$lastDay,$phone){

        if(empty($timeType)){

            throwErrMsg('所传时间类型不能为空');
        }
        if(empty($startDate) && empty($endDate)){

            throwErrMsg('所传时间不能为空');
        }

        $userModel = new UserModel();
        $data = $userModel -> getUserAndOrderInfo($timeType,$startDate,$endDate,$orderCount,$totalMoney,$goodsId,$goodsNum,$lastDay,$phone);
        if(empty($data)){
            return [];
        }
        return $data;
    }

    /**
     * @param $couponIdArr array 优惠券标识数组 一维
     * @param $data array 前台传的数据需要整理添加到coupon_user_cond表中的数据
     *
     * @return array ['status'=>1,'info'=>'成功']
     * @throws WrapException
     */
    public function addCouponUserQueryCond($couponIdArr,$data,$couponTplId,$sendTime){

        if(empty($couponIdArr) || empty($data) || !is_array($couponIdArr) || empty($couponTplId)){

            throwErrMsg('所传参数有问题');
        }

        $param = [
            'startDate' =>$data['startDate'],
            'endDate' => $data['endDate'],
            'orderCount' => $data['orderCount'],//下单次数
            'totalMoney' => $data['totalMoney'],
            'goodsId' => $data['goodsId'],
            'goodsNum' => $data['goodsNum'],
            'lastDay' => $data['lastDay'],
            'phone' => $data['phone'],
            'timeType'=> $data['timeType'],
            'sentType'=> $data['sendType']
        ];

        $couponIds = implode(',',$couponIdArr);
        $arr['coupon_tpl_id'] = $couponTplId;
        $arr['couponIds'] = $couponIds;
        $arr['param'] = json_encode($param, JSON_UNESCAPED_UNICODE);
        $arr['create_time'] = getTime();
        $arr['user_count'] = count($data['custId']);
        $arr['remarks'] = $data['remarks'];
        $arr['send_time'] = $sendTime;

        $couponUserCondModel = new CouponUserCondModel();
        $result = $couponUserCondModel -> addCouponUserCond($arr);

        if(empty($result)){
            return [];
        }
        return $result;
    }
}
