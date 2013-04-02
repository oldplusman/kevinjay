<?php

	/**
	 * mongodb数据库操作类
	 * @author kuangwenjie 2013-04-02
	 *   说明：1、尚不支持负载均衡
	 *         2、暂时只封装了insert, find两个函数
	 *
	 */

	class MongoCustomer{
		protected $mongo; //mongo对象
		protected $db; //库对象
		protected $collection; //集合对象
		
		/**
		 * 初始化mongodb连接（尚不支持负载均衡）
		 * @param array $configArray 参数数组
		 * 			eg: $configArray = array(
		 *					'host'=>'localhost', //mongodb主机地址（必选）
		 *					'dbname' => 'log', //库名 （必选）
		 *					'collection' => 'systemlog', //集合名（必选）
		 *					'port' => '27017' //可选（默认值为 27017）
		 *					'username' => 'admin', //登录名（可选）
		 *					'password' => '123456', //密码（可选）
		 * 				)
		 * @param array $options
		 * @throws Exception
		 */
		public function __construct(array $configArray=array(), array $options=array()){
			try{
				global $mongoArray;
				empty($configArray) && $configArray = $mongoArray; 
				if(isset($configArray['username']) && isset($configArray['password'])){
					$mongoStr = "mongodb://{$configArray['username']}:{$configArray['password']}@";
				}else{
					$mongoStr = "mongodb://";
				}
				$port = empty($configArray['port']) ? 27017 : $configArray['port'];
				$mongoStr .= "{$configArray['host']}:{$port}";
				$mongoObj = new Mongo($mongoStr);
				if(!is_object($mongoObj)){
					throw new Exception('连接出错');
				}
				$this -> mongo = $mongoObj;
				$this -> db = $this -> chooseDB($configArray['dbname']);
				$this -> collection = $this -> chooseCollection($configArray['collection']);
			}catch(MongoConnectionException $e){
				echo $e->getMessage();
			}
		}
		
		/**
		 * 选择库
		 * @param string $dbname 库名
		 * @throws MongoConnectionException
		 */
		public function chooseDB($dbname){
			try{
				$db = $this -> mongo -> selectDB($dbname);
				if(!is_object($db)){
					throw new MongoConnectionException('选择库失败');
				}
				$this -> db = $db;
				return $this -> db;
			}catch(MongoConnectionException $e){
				echo $e->getMessage();
			}
		}
		
		/**
		 * 选择集合
		 * @param string $collectionName 集合名
		 * @throws MongoConnectionException
		 */
		public function chooseCollection($collectionName){
			try{
				$collection = new MongoCollection($this -> db, $collectionName);
				if(!is_object($collection)){
					throw new MongoConnectionException('选择集合失败');
				}
				$this -> collection = $collection;
				return $this -> collection;
			}catch(MongoConnectionException $e){
				echo $e->getMessage();
			}
		}
		
		/**
		 * 插入数据
		 * @param array $insertArray 关联数组
		 * @return boolean
		 */
		public function insert(array $insertArray){
			try{
				if(empty($insertArray) || (count($insertArray)<=0)){
					throw new Exception('参数错误'); 
				}
				if(!$this -> isAssocArray($insertArray)){
					throw new Exception('参数必须是全关联数组');
				}
				if(!$this -> collection -> insert($insertArray)){
					throw new Exception('数据入库失败');
				}	
				return true;			
			}catch(Exception $e){
				echo $e->getMessage();
				return false;
			}
		}
		
		/**
		 * 查询数据
		 * @param array $selectArray
		 * @throws Exception
		 */
		public function select(array $selectArray=array()){
			try{
				if(!empty($selectArray) && (count($selectArray)>=0)){
					if(!$this -> isAssocArray($selectArray)){
						throw new Exception('参数必须是全关联数组');
					}
				}
				$cursor = $this -> collection -> find($selectArray);
				if(!$cursor){
					throw new Exception('查询失败');
				}
				$rsArray = array();
				foreach($cursor as $key=>$value){
					$value['_id'] = $this -> object2Array($value['_id']);
					if(is_array($value['_id']) && (count($value['_id'])>0)){
						$value['_id'] = $value['_id']['$id'];
						$rsArray[$value['_id']] = $value;						
					}else{
						throw new Exception('对象转换成数组失败');
					}
				}
				return $rsArray;			
			}catch(Exception $e){
				echo $e->getMessage();
				return false;
			}
		}
		
		/**
		 * 检验是否是全关联数组
		 * @param array $paramsArray
		 */
		public function isAssocArray(array $paramsArray){
			if(!empty($paramsArray) && (count($paramsArray)>0)){
				$keysArray = array_keys($paramsArray);
				foreach($keysArray as $key=>$value){
					if(!is_string($value)){
						return false;
					}
				}
				return true;				
			}
			return false;
		}
		
		/**
		 * 对象转换为数组
		 * @param object $object
		 */
		public function object2Array($object){
			$return = NULL;
			if(is_array($object)) {
				foreach($object as $key => $value){
					$return[$key] = $this->object2Array($value);
				}
			}else{
				if(!is_object($object)) return $object;
				$var = get_object_vars($object);
				if($var) {
					foreach($var as $key => $value){
						if(!$value){
							$return[$key] = '';
						}else{
							$return[$key] = ($key && !$value) ? NULL : $this -> object2Array($value);
						}
					}
				} else{
					return false;
				}
			}
			return $return;			
		}
	}
	

