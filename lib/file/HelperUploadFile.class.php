<?php

	/**
	 * 上传文件类
	 * 	 支持多文件上传，不论文件上传成功与否，临时文件皆会被删除
	 * @author kuangwenjie 2013-03-19
	 */
	
	class HelperUploadFile{
		protected static $allowTypeArray = array('csv');
		
		/**
		 * 上传文件
		 * @param array $paramsArray
		 * 	示例：
		 * 		$paramsArray = array(
		 * 			'name' => 'fileName', //上传表单里的name（必选），如   <!--<input type="file" name="fileName" />-->
		 * 			'maxSize' => '100', //允许的最大文件大小，单位Kb（可选）
		 * 			'filePath' => 'D:\temp', //文件存放路径（必选）
		 * 			'allowType' => array('jpg', 'png', 'jpeg', 'gif'), //允许上传的文件类型（可选） ，默认值如上
		 * 		);
		 */
		public static function uploadFile(array $paramsArray){
			if(!self::checkParam(array('name', 'filePath'), $paramsArray)){
				return false; //参数传递错误
			}
			$_FILES[$paramsArray['name']] = self::getFilesParam($paramsArray['name']);
			$paramsArray = self::assignValues($paramsArray); //参数中的可选项赋初值
			$fileArray = self::validateUploadFile($paramsArray); //检验上传的文件（类型，大小等）
			if(is_array($fileArray) && (count($fileArray)>0)){
				$fileArray = self::moveFile($fileArray, $paramsArray['filePath']);
				if(is_array($fileArray) && (count($fileArray)>0)){
					self::delTmpName($fileArray);//删除临时文件
					return $fileArray;
				}
			}
			self::delTmpName($fileArray);//删除临时文件
			return false;
		}
		
		/**
		 * 给可选参数赋初值
		 * @param array $paramsArray
		 * @return array $paramsArray
		 */
		protected static function assignValues(array $paramsArray){
			empty($paramsArray['allowType']) &&  $paramsArray['allowType']= self::$allowTypeArray;
			return $paramsArray;
		}
		
		/**
		 * 检查参数传递是否符合要求
		 * @param string | array $field 字段
		 * @param array $paramsArray 参数数组
		 */
		protected static function checkParam($field, $paramsArray){
			if(!empty($paramsArray) && (count($paramsArray)>0)){
				if(is_array($field) && (count($field)>0)){
					foreach($field as $key=>$value){
						if(!array_key_exists($value, $paramsArray) || !isset($paramsArray[$value])){
							return false;
						}	
					}
					return true;
				}else{
					if(array_key_exists($field, $paramsArray) && isset($paramsArray[$field])){
						return true;
					}
					return false;
				}
			}
			return false;
		}
		
		/**
		 * 处理$_FILES参数
		 * @param string $file <!--<input type="file" name="fileName" />-->中的name
		 */
		protected static function getFilesParam($file){
			$temp = array();
			if(is_array($_FILES[$file]['name']) && (count($_FILES[$file]['name'])>0)){
				for($i=0; $i<count($_FILES[$file]['name']); $i++){
					$temp[] = array(
						'name'=>$_FILES[$file]['name'][$i],
						'type'=>$_FILES[$file]['type'][$i],
						'tmp_name'=>$_FILES[$file]['tmp_name'][$i],
						'error'=>$_FILES[$file]['error'][$i],
						'size'=>$_FILES[$file]['size'][$i],
						'extension'=>pathinfo($_FILES[$file]['name'][$i], PATHINFO_EXTENSION),
					);
				}
				if(!empty($temp) && count($temp) > 0){
					$_FILES[$file] = $temp;
					return $_FILES[$file];
				} 
			}
			$_FILES[$file]['extension'] = pathinfo($_FILES[$file]['name'], PATHINFO_EXTENSION);
			return array($_FILES[$file]);
		}
	
		/**
		 * 检验上传的文件是否符合要求（类型，大小等等）
		 * @param array $paramsArray 用户传入的参数数组
		 * @return false | $fileArray 包含$_FILES及错误信息的数组
		 */
		protected static function validateUploadFile(array $paramsArray){
			if(!empty($paramsArray) && (count($paramsArray)>0)){
				$fileArray = $_FILES[$paramsArray['name']];
				$allowType = !is_array($paramsArray['allowType']) ? array($paramsArray['allowType']) : $paramsArray['allowType'];
				foreach($allowType as $key=>&$value){
					$value = strtolower($value);
				}			
				foreach($fileArray as $filekey=>&$filevalue){
					$filevalue['message'] = null;
					if(!in_array(strtolower($filevalue['extension']), $allowType)){
						$filevalue['message'] = '文件类型错误，仅支持：'.implode(',', $allowType);
						$filevalue['error'] = 9;//文件类型错误自定义错误代码
					}else{
						if($filevalue['error'] != 0){
							switch($filevalue['error']){
								case '1':
									$filevalue['message'] = '上传的文件大小超过了PHP配置文件中限制的值：'.ini_get('upload_max_filesize');
									break;
								case '2':
									$filevalue['message'] = '上传文件的大小超过了HTML表单中的MAX_FILE_SIZE选项的值';
									break;
								case '3':
									$filevalue['message'] = '文件只有部分被上传';
									break;
								case '4':
									$filevalue['message'] = '没有文件被上传';
									break;
								default:
									$filevalue['message'] = '文件上传出错或文件为空';
									break;
							}
						}else{
							if(!empty($paramsArray['maxSize']) && round($filevalue['size'] / 1024, 2) > $paramsArray['maxSize']){
								$filevalue['message'] = "上传的文件大小(".round($filevalue['size'] / 1024, 2)."KB)超过了程序限制的值:{$paramsArray['maxSize']}";
								$filevalue['error'] = 10; //自定义错误代码
							}						
						}
					}
				}
				return $fileArray;
			}
			return false;
		}
		
		/**
		 * 移动临时文件
		 * @param array $fileArray 文件信息数组
		 * @param string $filePath 目标目录
		 * @return false | $fileArray
		 */
		protected static function moveFile(array $fileArray, $filePath){
			if(!empty($fileArray) && count($fileArray)>0 && !empty($filePath)){
				!is_dir($filePath) && @mkdir($filePath, 0777 ,true);
				foreach($fileArray as $key=>&$tempArray){
					$newName = null;
					$newName = md5(time().$tempArray['name'].mt_rand(1, 100));
					$tmpName = $tempArray['tmp_name'];
					$destination = rtrim($filePath, '/').'/'.$newName.'.'.$tempArray['extension'];
					if($tempArray['error'] == 0){//为0,即上传成功
						if(is_file($tmpName)){
							if(!@move_uploaded_file($tmpName, $destination)){
								$tempArray['message'] = '文件移动失败，目标路径：'.$destination;
								$tempArray['error'] = 11; //文件移动失败自定义错误代码 
							}else{
								$tempArray['new_name'] = $destination;
							}
						}else{
							$tempArray['message'] = '不是一个文件：'.$tmpName;
							$tempArray['error'] = 12;//不是一个文件的自定义错误代码
						}
					}
				}
				return $fileArray;
			}
			return false;
		}
		
		/**
		 * 删除临时文件
		 * @param array $fileArray
		 */
		protected function delTmpName(array $fileArray){
			if(!empty($fileArray) && (count($fileArray)>0)){
				foreach($fileArray as $key=>$tempArray){
					if(is_array($tempArray) && (count($tempArray)>0)){
						if(is_file($tempArray['tmp_name'])){
							@unlink($tempArray['tmp_name']);
						}
					}
				}
			}
		}
		
	}
	
	