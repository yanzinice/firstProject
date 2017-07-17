<?php

/**
 * 权限管理
 * @author songbin
 */
namespace User\Service;

use Store\Service\StoreService;
use User\Model\AccessModel;
use User\Model\UserModel;
use User\Model\UserMarketModel;
use Common\Lib\sdk\Subject;

class AuthorityService {
	/**
	 * 获取菜单列表
	 */
	public function getMenuList($role_id, $type_user = 0) {
		// 读取该角色拥有权限
		$accessModel = D ( 'User/Access' );
		$result = $accessModel->selectAccess ( "role_id IN ({$role_id})" );
		if (empty ( $result )) {
			$result = [];
		}
		switch ($type_user) {
			case USER_TYPE_PLATFORM :
				$menuData = C ( 'MENU_PLATFORM' );
				break;
			case USER_TYPE_COMMERCIAL :
				$menuData = C ( 'MENU_BUSINESS' );
				break;
			case USER_TYPE_STORE :
				$menuData = C ( 'MENU_BUSINESS' );
				break;
		}
		$menuList = [];
		if (! empty ( $menuData )) {
			$menuList = $this->createMenuByRecursion ( $menuData, $result, $type_user );
		}
		return $menuList;
	}
	/**
	 * 使用递归生成菜单
	 *
	 * @param $menuData arr
	 *        	生成菜单原数据
	 * @param $access arr
	 *        	权限
	 */
	public function createMenuByRecursion($menuData, $access, $type_user) {
		$menuList = [];
		foreach ( $menuData as $k => $v ) {
			$tmp = [];
			if (isset ( $v ['name'] )) {
				$tmp ['text'] = $v ['name'];
			} else {
				throwErrMsg ( "菜单配置项有误" );
			}
			if (isset ( $v ['url'] )) {
				$tmp ['id'] = $v ['url'];
				if (deep_in_array ( $tmp ['id'], $access )) {
					$tmp ['checked'] = true;
				}
			}
			if (isset ( $v ['cls'] )) {
				$tmp ['iconCls'] = $v ['cls'];
			}
			if (isset ( $v ['sub'] )) {
				$sub = $this->createMenuByRecursion ( $v ['sub'], $access, $type_user );
				$tmp ['children'] = $sub;
			}
			if (isset ( $v ['user_type'] )) {
				if ($type_user == USER_TYPE_PLATFORM) {
					array_push ( $menuList, $tmp );
				} elseif ($type_user == USER_TYPE_COMMERCIAL) {
					if (in_array ( USER_TYPE_COMMERCIAL, $v ['user_type'] )) {
						array_push ( $menuList, $tmp );
					}
				} else {
					if (in_array ( USER_TYPE_STORE, $v ['user_type'] )) {
						array_push ( $menuList, $tmp );
					}
				}
			} else {
				array_push ( $menuList, $tmp );
			}
		}
		return $menuList;
	}
	
	/**
	 * 获取角色列表
	 */
	public function getRoleList($role_type,$limit="") {
		$roleModel = D ( 'User/Role' );
		$result = $roleModel->selectRole ( "role_type = {$role_type}","*",'',$limit );
		return $result;
	}
	
	/**
	 * 获取商家角色列表
	 */
	public function getRoleListByBusId($role_type, $bus_id,$limit='') {
		$roleModel = D ( 'User/Role' );
		$result = $roleModel->selectRoleByBusId ( $bus_id ,$limit);
		return $result;
	}
	
	/**
	 * 获取门店角色列表
	 */
	public function getRoleListByStoreId($role_type, $store_id) {
		$roleModel = D ( 'User/Role' );
		$result = $roleModel->selectRoleByStoreId ( $store_id );
		return $result;
	}
	
	/**
	 * 添加角色
	 */
	public function addRole($role, $remark, $role_type, $uid) {
		$roleModel = D ( 'User/Role' );
		$data ['role'] = $role;
		$data ['remark'] = $remark;
		$data ['role_type'] = $role_type;
		$data ['uid_owner'] = $uid;
		$result = $roleModel->addRole ( $data );
		return $result;
	}
	
	/**
	 * 编辑角色
	 */
	public function editRole($role, $role_id, $remark, $uid) {
		$roleModel = D ( 'User/Role' );
		$data ['role'] = $role;
		$data ['remark'] = $remark;
		$data ['uid_owner'] = $uid;
		$condition = "role_id = {$role_id}";
		$result = $roleModel->editRole ( $condition, $data );
		return $result;
	}
	
	/**
	 * 删除角色
	 */
	public function deleteRole($role_id) {
		$roleModel = D ( 'User/Role' );
		$condition = "role_id = {$role_id}";
		$result = $roleModel->deleteRole ( $condition, '', '' );
		return $result;
	}
	
	/**
	 * 保存角色权限
	 */
	public function saveRoleAccess($role_id, $access) {
		$accessModel = D ( 'User/Access' );
		// 删除该角色原来权限
		$accessModel->deleteAccess ( "role_id={$role_id}" );
		// 添加新权限
		$data ['role_id'] = $role_id;
		foreach ( $access as $key => $value ) {
			$data ['url'] = $key;
			$accessModel->addAccess ( $data );
		}
	}
	
	/**
	 * 获取用户列表
	 */
	public function getUserList($type_user, $isadmin = "",$limit="") {
		$userModel = new UserModel();
		if (! empty ( $isadmin )) {
			$where = " AND isadmin = {$isadmin}";
		}
		$result = $userModel->selectUser ( "type_user={$type_user} AND status IN (0,1) {$where}","*",'',$limit );
		if (empty ( $result )) {
			$result = [];
		}
		$userStatus = C ( 'STATUS' );
		foreach ( $result as $k => &$v ) {
			if (isset ( $userStatus [$v ['status']] )) {
				$v ['status_name'] = $userStatus [$v ['status']];
			}
			$result2 = $userModel->selectBusStoreMarketByUid ( $v ['uid'] );
			$bus_id = $result2 [0];
			$store_id = $result2 [1];
			$market_id = $result2 [2];
			$busstoremarketids = "";
			if (! empty ( $bus_id )) {
				if (is_array ( $bus_id )) {
					foreach ( $bus_id as $k1 => &$v1 ) {
						$v1 = "1" . $v1;
					}
					$bus_id = implode ( ',', $bus_id );
				} else {
					$bus_id = "1" . $bus_id;
				}
				$busstoremarketids = $bus_id;
			}
			if (! empty ( $store_id )) {
				if (is_array ( $store_id )) {
					foreach ( $store_id as $k2 => &$v2 ) {
						$v2 = "2" . $v2;
					}
					$store_id = implode ( ',', $store_id );
				} else {
					$store_id = "2" . $store_id;
				}
				if (empty ( $busstoremarketids )) {
					$busstoremarketids = $store_id;
				} else {
					$busstoremarketids = $busstoremarketids . "," . $store_id;
				}
			}
			if (! empty ( $market_id )) {
				if (is_array ( $market_id )) {
					foreach ( $market_id as $k3 => &$v3 ) {
						$v3 = "3" . $v3;
					}
					$market_id = implode ( ',', $market_id );
				} else {
					$market_id = "3" . $v3;
				}
				if (empty ( $busstoremarketids )) {
					$busstoremarketids = $market_id;
				} else {
					$busstoremarketids = $busstoremarketids . "," . $market_id;
				}
			}
			$busstoremarketids = explode ( ",", $busstoremarketids );
			foreach ( $busstoremarketids as $key => &$value ) {
				$value = intval ( $value );
			}
			$v ['busstoremarketids'] = $busstoremarketids;
		}
		return $result;
	}
	
	/**
	 * 获取商家用户列表
	 */
	public function getBusUserList($type_user, $bus_id,$limit="") {
		$userModel = D ( 'User/User' );
		$result = $userModel->selectUserByBusId ( $type_user, $bus_id ,$limit);
		if (empty ( $result )) {
			$result = [];
		}
		$userStatus = C ( 'STATUS' );
		foreach ( $result as $k => &$v ) {
			if (isset ( $userStatus [$v ['status']] )) {
				$v ['status_name'] = $userStatus [$v ['status']];
			}
			$result2 = $userModel->selectBusStoreMarketByUid ( $v ['uid'] );
			$store_id = $result2 [1];
			$market_id = $result2 [2];
			$storemarketids = "";
			if (! empty ( $store_id )) {
				if (is_array ( $store_id )) {
					foreach ( $store_id as $k2 => &$v2 ) {
						$v2 = "2" . $v2;
					}
					$store_id = implode ( ',', $store_id );
				} else {
					$store_id = "2" . $store_id;
				}
				if (empty ( $storemarketids )) {
					$storemarketids = $store_id;
				} else {
					$storemarketids = $storemarketids . "," . $store_id;
				}
			}
			if (! empty ( $market_id )) {
				if (is_array ( $market_id )) {
					foreach ( $market_id as $k3 => &$v3 ) {
						$v3 = "3" . $v3;
					}
					$market_id = implode ( ',', $market_id );
				} else {
					$market_id = "3" . $v3;
				}
				if (empty ( $storemarketids )) {
					$storemarketids = $market_id;
				} else {
					$storemarketids = $storemarketids . "," . $market_id;
				}
			}
			$storemarketids = explode ( ",", $storemarketids );
			foreach ( $storemarketids as $key => &$value ) {
				$value = intval ( $value );
			}
			$v ['storemarketids'] = $storemarketids;
		}
		return $result;
	}
	
	/**
	 * 获取门店用户列表
	 */
	public function getStoreUserList($type_user, $store_id = "", $isadmin = "",$limit="") {
		$userModel = new UserModel ();
		$result = $userModel->selectUserByStoreId ( $type_user, $store_id, $isadmin,$limit );
		if (empty ( $result )) {
			$result = [];
		}
		$userStatus = C ( 'STATUS' );
		foreach ( $result as $k => &$v ) {
			if (isset ( $userStatus [$v ['status']] )) {
				$v ['status_name'] = $userStatus [$v ['status']];
			}
			$result2 = $userModel->selectBusStoreMarketByUid ( $v ['uid'] );
			$bus_id = $result2 [0];
			$store_id = $result2 [1];
			$market_id = $result2 [2];
			$busstoremarketids = "";
			if (! empty ( $bus_id )) {
				if (is_array ( $bus_id )) {
					foreach ( $bus_id as $k1 => &$v1 ) {
						$v1 = "1" . $v1;
					}
					$bus_id = implode ( ',', $bus_id );
				} else {
					$bus_id = "1" . $bus_id;
				}
				$busstoremarketids = $bus_id;
			}
			if (! empty ( $store_id )) {
				if (is_array ( $store_id )) {
					foreach ( $store_id as $k2 => &$v2 ) {
						$v2 = "2" . $v2;
					}
					$store_id = implode ( ',', $store_id );
				} else {
					$store_id = "2" . $store_id;
				}
				if (empty ( $busstoremarketids )) {
					$busstoremarketids = $store_id;
				} else {
					$busstoremarketids = $busstoremarketids . "," . $store_id;
				}
			}
			if (! empty ( $market_id )) {
				if (is_array ( $market_id )) {
					foreach ( $market_id as $k3 => &$v3 ) {
						$v3 = "3" . $v3;
					}
					$market_id = implode ( ',', $market_id );
				} else {
					$market_id = "3" . $v3;
				}
				if (empty ( $busstoremarketids )) {
					$busstoremarketids = $market_id;
				} else {
					$busstoremarketids = $busstoremarketids . "," . $market_id;
				}
			}
			$busstoremarketids = explode ( ",", $busstoremarketids );
			foreach ( $busstoremarketids as $key => &$value ) {
				$value = intval ( $value );
			}
			$v ['busstoremarketids'] = $busstoremarketids;
		}
		return $result;
	}
	
	/**
	 * 获取门店管理员列表
	 */
	public function getStoreAdminList($bus_id = "") {
		$userModel = new UserModel ();
		$result = $userModel->selectUserByStoreId ( $type_user, $store_id, $isadmin );
		if (empty ( $result )) {
			$result = [];
		}
		$userStatus = C ( 'STATUS' );
		foreach ( $result as $k => &$v ) {
			if (isset ( $userStatus [$v ['status']] )) {
				$v ['status_name'] = $userStatus [$v ['status']];
			}
			$result2 = $userModel->selectBusStoreMarketByUid ( $v ['uid'] );
			$bus_id = $result2 [0];
			$store_id = $result2 [1];
			$market_id = $result2 [2];
			$busstoremarketids = "";
			if (! empty ( $bus_id )) {
				if (is_array ( $bus_id )) {
					foreach ( $bus_id as $k1 => &$v1 ) {
						$v1 = "1" . $v1;
					}
					$bus_id = implode ( ',', $bus_id );
				} else {
					$bus_id = "1" . $bus_id;
				}
				$busstoremarketids = $bus_id;
			}
			if (! empty ( $store_id )) {
				if (is_array ( $store_id )) {
					foreach ( $store_id as $k2 => &$v2 ) {
						$v2 = "2" . $v2;
					}
					$store_id = implode ( ',', $store_id );
				} else {
					$store_id = "2" . $store_id;
				}
				if (empty ( $busstoremarketids )) {
					$busstoremarketids = $store_id;
				} else {
					$busstoremarketids = $busstoremarketids . "," . $store_id;
				}
			}
			if (! empty ( $market_id )) {
				if (is_array ( $market_id )) {
					foreach ( $market_id as $k3 => &$v3 ) {
						$v3 = "3" . $v3;
					}
					$market_id = implode ( ',', $market_id );
				} else {
					$market_id = "3" . $v3;
				}
				if (empty ( $busstoremarketids )) {
					$busstoremarketids = $market_id;
				} else {
					$busstoremarketids = $busstoremarketids . "," . $market_id;
				}
			}
			$busstoremarketids = explode ( ",", $busstoremarketids );
			foreach ( $busstoremarketids as $key => &$value ) {
				$value = intval ( $value );
			}
			$v ['busstoremarketids'] = $busstoremarketids;
		}
		return $result;
	}
	
	/**
	 * 获取用户类型列表
	 */
	public function getUserTypeList() {
		$userType = [];
		foreach ( C ( 'USER_TYPE' ) as $key => $value ) {
			$tmp = [];
			$tmp ['value'] = $key;
			$tmp ['text'] = $value;
			array_push ( $userType, $tmp );
		}
		return $userType;
	}
	
	/**
	 * 获取用户状态列表
	 */
	public function getStatusList() {
		$status = [];
		foreach ( C ( 'STATUS' ) as $key => $value ) {
			$tmp = [];
			$tmp ['value'] = $key;
			$tmp ['text'] = $value;
			array_push ( $status, $tmp );
		}
		return $status;
	}
	
	/**
	 * 添加用户
	 */
	public function addUser($user, $pass, $type_user_name, $busstoremarketids, $status_name, $name, $phone, $email, $isadmin) {
		$userModel = D ( "User/User" );
		$data ['user'] = $user;
		$data ['salt'] = mt_rand ( 1000, 9999 );
		$data ['pass'] = makeEncryptPass ( $data ['salt'], $pass );
		$data ['type_user'] = $type_user_name;
		$data ['status'] = $status_name;
		$data ['name'] = $name;
		$data ['phone'] = $phone;
		$data ['email'] = $email;
		$data ['isadmin'] = $isadmin;
		$data ['time_create'] = date ( 'Y-m-d H:i:s' );
		$result = $userModel->addUser ( $data );
		if (! empty ( $busstoremarketids )) {
			$userBusinessModel = D ( "User/UserBusiness" );
			$userStoreModel = D ( "User/UserStore" );
			$userMarketModel = D ( "User/UserMarket" );
			$busstoremarketids = array_unique ( $busstoremarketids );
			foreach ( $busstoremarketids as $k => $v ) {
				$initial = substr ( $v, 0, 1 );
				$id = substr ( $v, 1 );
				switch ($initial) {
					case '1' :
						$dataBusiness ['uid'] = $result;
						$dataBusiness ['bus_id'] = $id;
						$userBusinessModel->addUserBusiness ( $dataBusiness );
						break;
					case '2' :
						$dataStore ['uid'] = $result;
						$dataStore ['store_id'] = $id;
						$userStoreModel->addUserStore ( $dataStore );
						break;
					case '3' :
						$dataMarket ['uid'] = $result;
						$dataMarket ['market_id'] = $id;
						$userMarketModel->addUserMarket ( $dataMarket );
						break;
				}
			}
		}
	}
	
	/**
	 * 编辑用户
	 */
	public function editUser($user, $uid, $busstoremarketids, $name, $phone, $email, $isadmin) {
		$userModel = D ( "User/User" );
		$data ['user'] = $user;
		$data ['uid'] = $uid;
		$data ['name'] = $name;
		$data ['phone'] = $phone;
		$data ['email'] = $email;
		$data ['isadmin'] = $isadmin;
		$condition = "uid = {$uid}";
		$result = $userModel->editUser ( $condition, $data );
		$userBusinessModel = D ( "User/UserBusiness" );
		$userStoreModel = D ( "User/UserStore" );
		$userMarketModel = D ( "User/UserMarket" );
		if (! empty ( $busstoremarketids )) {
			$userBusinessModel = D ( "User/UserBusiness" );
			$userStoreModel = D ( "User/UserStore" );
			$userMarketModel = D ( "User/UserMarket" );
			$busstoremarketids = array_unique ( $busstoremarketids );
			$userBusinessModel->where ( "uid = {$uid}" )->delete ();
			$userStoreModel->where ( "uid = {$uid}" )->delete ();
			$userMarketModel->where ( "uid = {$uid}" )->delete ();
			foreach ( $busstoremarketids as $k => $v ) {
				$initial = substr ( $v, 0, 1 );
				$id = substr ( $v, 1 );
				switch ($initial) {
					case '1' :
						$dataBusiness ['uid'] = $uid;
						$dataBusiness ['bus_id'] = $id;
						$userBusinessModel->addUserBusiness ( $dataBusiness );
						break;
					case '2' :
						$dataStore ['uid'] = $uid;
						$dataStore ['store_id'] = $id;
						$userStoreModel->addUserStore ( $dataStore );
						break;
					case '3' :
						$dataMarket ['uid'] = $uid;
						$dataMarket ['market_id'] = $id;
						$userMarketModel->addUserMarket ( $dataMarket );
						break;
				}
			}
		}

        //编辑完用户信息，删除redis存的用户的配送云超市信息
        $redis = getRedisClient();

        $keyMarketInfo = "DispatchService_getPerMission_".$uid;

        if($redis -> EXISTS($keyMarketInfo))
        {
            $redis -> del($keyMarketInfo);
        }
	}
	
	/**
	 * 删除用户
	 */
	public function deleteUser($uid) {
		$userModel = D ( 'User/User' );
		$condition = "uid = {$uid}";
		$data ['status'] = 2;
		$result = $userModel->editUser ( $condition, $data );
		return $result;
	}
	
	/**
	 * 重置密码
	 */
	public function resetPass($uid, $newpass) {
		$userModel = D ( "User/User" );
		$condition = "uid = {$uid}";
		$salt = $userModel->selectUser ( $condition, 'salt' );
		$data ['pass'] = makeEncryptPass ( $salt [0] ['salt'], $newpass );
		$result = $userModel->editUser ( $condition, $data );
		return $result;
	}
	
	/**
	 * 修改状态
	 */
	public function resetStatus($uid, $status) {
		$userModel = D ( "User/User" );
		$condition = "uid = {$uid}";
		$data ['status'] = $status;
		$result = $userModel->editUser ( $condition, $data );
		return $result;
	}
	
	/**
	 * 获取用户角色列表
	 */
	public function getRoleUserList($uid, $role_type) {
		$userModel = D ( 'User/User' );
		$isadmin = $userModel->selectUser ( "uid={$uid}", 'isadmin' );
		if ($isadmin [0] ['isadmin']) {
			return [];
		}
		$result = $this->getRoleList ( $role_type );
		$roleUserModel = D ( "User/RoleUser" );
		// 用户当前拥有的角色
		$result2 = $roleUserModel->selectRoleUser ( "uid={$uid}", 'role_id' );
		if (empty ( $result2 )) {
			return $result;
		}
		foreach ( $result as $key => &$value ) {
			if (deep_in_array ( $value ['role_id'], $result2 )) {
				$value ['check'] = true;
			}
		}
		return $result;
	}
	
	/**
	 * 获取商家用户角色列表
	 */
	public function getBusRoleUserList($uid, $role_type, $bus_id) {
		$userModel = D ( 'User/User' );
		$isadmin = $userModel->selectUser ( "uid={$uid}", 'isadmin' );
		if ($isadmin [0] ['isadmin']) {
			return [];
		}
		$roleModel = D ( 'User/Role' );
		$result = $roleModel->selectRoleByBusId ( $bus_id );
		$roleUserModel = D ( "User/RoleUser" );
		// 用户当前拥有的角色
		$result2 = $roleUserModel->selectRoleUser ( "uid={$uid}", 'role_id' );
		if (empty ( $result2 )) {
			return $result;
		}
		foreach ( $result as $key => &$value ) {
			if (deep_in_array ( $value ['role_id'], $result2 )) {
				$value ['check'] = true;
			}
		}
		return $result;
	}
	
	/**
	 * 获取门店用户角色列表
	 */
	public function getStoreRoleUserList($uid, $role_type, $store_id) {
		$userModel = D ( 'User/User' );
		$isadmin = $userModel->selectUser ( "uid={$uid}", 'isadmin' );
		if ($isadmin [0] ['isadmin']) {
			return [];
		}
		$roleModel = D ( 'User/Role' );
		$result = $roleModel->selectRoleByStoreId ( $store_id );
		$roleUserModel = D ( "User/RoleUser" );
		// 用户当前拥有的角色
		$result2 = $roleUserModel->selectRoleUser ( "uid={$uid}", 'role_id' );
		if (empty ( $result2 )) {
			return $result;
		}
		foreach ( $result as $key => &$value ) {
			if (deep_in_array ( $value ['role_id'], $result2 )) {
				$value ['check'] = true;
			}
		}
		return $result;
	}
	
	/**
	 * 绑定用户角色
	 */
	public function bindUserRole($uid, $role_ids) {
		$roleUserModel = D ( "User/RoleUser" );
		$result = $roleUserModel->selectRoleUser ( "uid={$uid}" );
		if ($result) {
			$roleUserModel->deleteRoleUser ( "uid={$uid}" );
		}
		if ($role_ids) {
			$role_ids = explode ( ',', $role_ids );
			foreach ( $role_ids as $k => $v ) {
				$data ['uid'] = $uid;
				$data ['role_id'] = $v;
				$roleUserModel->addRoleUser ( $data );
			}
		}
	}
	
	/**
	 * 验证用户密码
	 */
	public function checkUserPass($user, $pass, $auto, $type_user = '0') {
		$userModel = D ( "User/User" );
		$condition = "user = '{$user}'";
		$result = $userModel->selectUser ( $condition );
		if (empty ( $result )) {
			throwErrMsg ( '该用户不存在！' );
		} else {
			$querySalt = $result [0] ['salt'];
			$queryPass = $result [0] ['pass'];
		}
		if (md5 ( $querySalt . $pass ) === $queryPass && false !== strpos ( $type_user, $result [0] ['type_user'] )) {
			if ($result [0] ['status'] == 0) {
				throwErrMsg ( '该用户被禁用！' );
			} elseif ($result [0] ['status'] == 2) {
				throwErrMsg ( '该用户不存在！' );
			}
			if ($auto == 'on') {
				$login_info = [
						'user' => $user,
						'pass' => $queryPass 
				];
				$type_user = $result[0]['type_user'];
				switch ($type_user){
					case USER_TYPE_PLATFORM:
						lzcookie ( "platform_login_info", json_encode ( $login_info ), ['expire' => 365 * 24 * 60 * 60, 'path' => '/'] );
						break;
					case USER_TYPE_COMMERCIAL:
						lzcookie ( "business_login_info", json_encode ( $login_info ),['expire' => 365 * 24 * 60 * 60, 'path' => '/'] );
						break;
					case USER_TYPE_STORE:
						lzcookie ( "store_login_info", json_encode ( $login_info ), ['expire' => 365 * 24 * 60 * 60, 'path' => '/'] );
						break;
				}
			}
			return $result [0];
		} else {
			throwErrMsg ( '用户名或密码错误！' );
		}
	}
	/**
	 * 验证配送员登录
	 */
	public function checkLogisticUserPass($user, $pass) {
		$userModel = D ( "User/User" );
		$condition = "user = '{$user}' and (type_user = ".USER_TYPE_LOGISTICS." or type_user  = ".USER_TYPE_DISTRIBUTION.')' ;
		$result = $userModel->selectUser ( $condition );
		if (empty ( $result )) {
            return ['info'=>'该用户不存在！','data'=>''];
		} else {
			$querySalt = $result [0] ['salt'];
			$queryPass = $result [0] ['pass'];
		}
		if (md5 ( $querySalt . $pass ) === $queryPass) {
			if ($result [0] ['status'] == 0) {
                return ['info'=>'该用户被禁用！','data'=>''];
			} elseif ($result [0] ['status'] == 2) {
                return ['info'=>'该用户不存在！','data'=>''];
			}
			return $result [0];
		} else {
            return ['info'=>'用户名或密码错误！','data'=>''];
		}
	}
	/**
	 * 验证cookie用户密码
	 */
	public function checkUserPassByCookie($user, $md5Pass, $type_user = 0) {
		$userModel = D ( "User/User" );
		$condition = "user = '{$user}'";
		$result = $userModel->selectUser ( $condition );
		if (empty ( $result )) {
			throwErrMsg ( '该用户不存在！' );
		}
		if ($md5Pass !== $result [0] ['pass'] || $type_user != $result [0] ['type_user']) {
			throwErrMsg ( '用户名或密码错误！' );
		}
		if ($result [0] ['status'] == 0) {
			throwErrMsg ( '该用户被禁用！' );
		} elseif ($result [0] ['status'] == 2) {
			throwErrMsg ( '该用户不存在！' );
		}
		return $result [0];
	}
	
	/**
	 * 获取用户权限
	 */
	public function getUserAccess($uid, $isadmin, $user_type = 0) {
		if (! $isadmin) {
			$roleUserModel = D ( 'User/RoleUser' );
			$roleId = $roleUserModel->selectRoleUser ( "uid = {$uid}" );
			if ($roleId != null) {
				for($i = 0; $i < count ( $roleId ); $i ++) {
					$n .= $roleId [$i] ['role_id'] . ',';
				}
				$n = substr ( $n, 0, - 1 );
				$accessModel = D ( 'User/Access' );
				$result = $accessModel->selectAccess ( "role_id IN ({$n})" );
			} else {
				return false;
			}
		}
		switch ($user_type) {
			case 0 :
				$menu = C ( 'MENU_PLATFORM' );
				break;
			case 1 :
				$menu = C ( 'MENU_BUSINESS' );
				break;
			case 2 :
				$menu = C ( 'MENU_BUSINESS' );
				break;
		}
		$arr1 = [];
		$arr2 = [];

		// 如果是门店用户，得到这个门店的 is_platform_store 字段
		$isPlatformStore = 1;
		if ($user_type == 2) {
			$loginInfo = session('login_info');

			if (!empty($loginInfo['storeId'])) {
				$store = (new StoreService())->getStoreById($loginInfo['storeId']);
				$isPlatformStore = $store['is_platform_store'];
				unset($store);
			}
		}

		$id = 0;
		foreach ( $menu as $k => $v ) {
			$tmp ['meid'] = $id ++;
			$tmp ['nickname'] = $v ['name'];
			$tmp ['icon'] = $v ['cls'];
			$tmp ['color'] = $v ['color'];
			array_push ( $arr1, $tmp );
		}

		foreach ( $menu as $k1 => $v1 ) {
			$arr2 [$k1] = [];
			if (isset ( $v1 ['sub'] )) {
				foreach ( $v1 ['sub'] as $k2 => $v2 ) {
					$tmp2 = [];
					if (isset ( $v2 ['name'] )) {
						$tmp2 ['mname'] = $v2 ['name'];
						$tmp2 ['meid'] = $id ++;
					} else {
						throwErrMsg ( "菜单配置项有误" );
					}
					if (isset ( $v2 ['sub'] )) {
						$tmp2 ['children'] = [];
						foreach ( $v2 ['sub'] as $k3 => $v3 ) {
							$tmp3 = [];
							if (isset ( $v3 ['name'] )) {
								$tmp3 ['text'] = $v3 ['name'];
							} else {
								throwErrMsg ( "菜单配置项有误" );
							}
							if (isset ( $v3 ['url'] )) {
								$tmp3 ['id'] = $v3 ['url'];
								if ($isadmin || deep_in_array ( $tmp3 ['id'], $result )) {
									if ($user_type == 0) {
										if (isset($v3['development'])) {
											if (in_array(APP_STATUS, $v3['development'])) {
												array_push($tmp2 ['children'], $tmp3);
											}
										} else {
											array_push($tmp2 ['children'], $tmp3);
										}
									} elseif ($user_type == 1) {
										if (in_array ( 1, $v3 ['user_type'] )) {
											array_push ( $tmp2 ['children'], $tmp3 );
										}
									} else {
										if (in_array(2, $v3 ['user_type'])) {
											// 第三方门店只可见菜单的 access 配置为 thirdPartyOnly 的菜单，这些菜单平台门店不可见，liuchao 修改
											if ($isPlatformStore) {
												if (isset($v3['access']) && $v3['access'] == 'thirdPartyOnly') {

												} else {
													array_push($tmp2 ['children'], $tmp3);
												}
											} else {
												if (isset($v3['access']) && $v3['access'] == 'thirdPartyOnly') {
													array_push($tmp2 ['children'], $tmp3);
												}
											}
										}
									}
								}
							} else {
								throwErrMsg ( "菜单配置项有误" );
							}
						}
					}
					array_push ( $arr2 [$k1], $tmp2 );
					for($m = 0; $m < count ( $arr2 [$k1] ); $m ++) {
						if (empty ( $arr2 [$k1] [$m] ['children'] )) {
							unset ( $arr2 [$k1] [$m] );
						}
					}
				}
			}
		}
		
		$one = [];
		
		foreach ( $arr1 as $key => $value ) {
			
			foreach($arr2 [$key] as $uv){
				
				if(count( $uv ['children'])){
					
					$array ["menu" . $value ['meid']] = $arr2 [$key];
					$one[$key] = $value;
				}
				
			}
		}
		
		$arr = [
				'one' => $one,
				'two' => $array 
		];
		return $arr;
	}
	
	/**
	 * 根据uid查询商家
	 */
	public function getBusinessByUid($uid) {
		$userBusinessModel = D ( 'User/UserBusiness' );
		$result = $userBusinessModel->selectUserBusiness ( "uid={$uid}" );
		if (empty ( $result )) {
			throwErrMsg ( "当前用户没有所属商家" );
		}
		return $result [0] ['bus_id'];
	}
    /**
     * @param $uid int 用户id
     * @return $result array (array('uid'=>911,'bus_id'=>330),array('uid'=>911,'bus_id'=>329))
     * @author cuiyan
     * @throws \Common\Cls\WrapException
     */
    public function getBusinessIdByUid($uid) {
        $userBusinessModel = D ( 'User/UserBusiness' );
        $result = $userBusinessModel->selectUserBusiness ( "uid={$uid}" );
        if (empty ( $result )) {
            throwErrMsg ( "当前用户没有所属商家" );
        }
        return $result;
    }
	
	/**
	 * 根据uid查询门店
	 */
	public function getStoreByUid($uid) {
		$userStoreModel = D ( 'User/UserStore' );
		$result = $userStoreModel->selectUserStore ( "uid={$uid}" );
		if (empty ( $result )) {
			throwErrMsg ( "当前用户没有所属门店" );
		}
		return $result [0] ['store_id'];
	}
	/*
	 * 根据uid查便利站
	 */
	public function getMarkeIdByUid($uid) {
		$userStoreModel = D ( 'User/UserMarket' );
		$result = $userStoreModel->selectUserMarket ( "uid={$uid}" );
		if (empty ( $result )) {
			throwErrMsg ( "当前用户没有所属云超市" );
		}
		return $result;
	}

    /**
     * @param $uid int 用户id
     * @return $result array (array('uid'=>941,'store_id'=>775),array('uid'=>944,'store_id'=>774))
     * @author cuiyan
     * @throws \Common\Cls\WrapException
     */
    public function getStoreIdByUid($uid){
        $userStoreModel = D ( 'User/UserStore' );
        $result = $userStoreModel->selectUserStore ( "uid={$uid}" );
        if (empty ( $result )) {
            throwErrMsg ( "当前用户没有所属门店" );
        }
        return $result;
    }
	/*
	 * 根据uid查寻云超市和门店
	 * @param int $uid 用户标识
	 * @param int $userType 用户类型
	 */
	public function getMarkeIdAndStoreByUid($uid, $userType){
		$userStoreModel = D ( 'User/UserMarket' );

		if($userType == USER_TYPE_LOGISTICS){//平台物流人员
			$result = $userStoreModel->selectPlatformUserMarketAndMarket ($uid);
		}elseif($userType == USER_TYPE_DISTRIBUTION){//商家配送员
			$result = $userStoreModel->selectStoreUserMarketAndMarket ($uid);
		}

		if (empty ( $result )) {
			throwErrMsg ( "您没有配送云超市的权限，请核实！" );
		}
		return $result;
	}
	
	/**
	 * 获取用户权限
	 */
	public function getAllAccessByUid($uid) {
		$accessModel = new AccessModel ();
		$result = $accessModel->selectAccessByUid ( $uid );
		foreach ( $result as $key => $value ) {
			$access [] = $value;
		}
		return $access;
	}

    /**
     * 根据uid查寻云超市和门店
     * @param int $uid 用户标识
     * @param string $userType 用户类型
     * @param string $userName 用户名称
     * @author  HuangQing
     * email: huangqing@jpgk.com.cn
     * QQ:2322523770
     */
    public function getMarkeAndStoreIdByUid($uid, $userType,$userName)
    {
        //获取到用户的数据权限
        $subject = new Subject();
        $userRole =  $subject -> getRolesByUser($userName);
        $marketIdOrStoreId = $subject -> getValueByRoles(array_column($userRole,'name'),$userName,$userType);

        $userStoreModel = new UserMarketModel();
        //如果userType为数字说明权限控制还没有更改
        $result = [];
        if($userType == '平台物流人员')
        {
            $result = $userStoreModel->selectPlatformUserMarketAndMarketByMarketId ($marketIdOrStoreId);
        }
        elseif($userType == '商家配送员')
        {
            $result = $userStoreModel->selectStoreUserMarketAndMarketByStoreId ($marketIdOrStoreId);
        }

        return $result ? $result : [];
    }
}
