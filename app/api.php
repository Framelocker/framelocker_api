<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!class_exists('Auth')) require_once APPPATH."controllers/auth.php";

if (!class_exists('S3')) require_once (APPPATH.'libraries/S3.php');

class API extends Auth{

	private $novp_storage_config;

	public function __constructor(){
        parent::__construct();           
    }

    public function index(){    	
    	if($method = $this->input->post("method")){ 
    		if($token = $this->input->post("token")){
    			$user = $this->getUserByToken($token);
    			if($user){
    				if(method_exists($this, $method))
    					$this->$method($user);
    				else
    					exit("Uknown method");
    			}else{
    				exit("Uncorrect token. Access Denied");		
    			}    			
    		}else{
    			if($method == "register")
    				$this->register();
    			elseif($method == "signin")
    				$this->signin();    			
    			else
    				exit("Required token. Access Denied");		
    		}   		
    		
    	}
    	elseif($method = $this->input->get("method")){
    		if($token = $this->input->get("token")){
    			$user = $this->getUserByToken($token);
    			if($user){
    				if(method_exists($this, $method))
    					$this->$method($user);
    				else
    					exit("Uknown method");
    			}else{
    				exit("Uncorrect token. Access Denied");		
    			}    			
    		}else{
    			exit("Required token. Access Denied");		
    		}   		
    	}else{
    		exit("Access Denied");
    	}
    }

    private function upload_file($user){
    	$this->config->load('novp');
    	$this->config->load('upload');
    	$allowedExt = explode("|", $this->config->item("allowed_types"));
		$maxFileSize = $this->config->item("max_size");

		$ext = end(explode('.', strtolower($_FILES['novp_file']['name'])));
		
		if (!in_array($ext, $allowedExt)) {
			echo 'Denied exstension';
			exit;
		}

		if ($maxFileSize < $_FILES['novp_file']['size']) {
			echo 'false';
			exit;
		}

		if (is_uploaded_file($_FILES['novp_file']['tmp_name'])) {
			$uploadFile = $_FILES['novp_file']['tmp_name'];

	        $this->novp_storage_config = $this->config->item('aws');
	        $s3 = new S3($this->novp_storage_config['access_key'], $this->novp_storage_config['secret_key']);
	        if (!in_array($this->novp_storage_config['bucket'], (array) $s3->listBuckets())) {
				// Create a bucket with public read access
				if (!$s3->putBucket($this->novp_storage_config['bucket'], S3::ACL_PUBLIC_READ))
					throw new \Exception\EntityNotFoundException(); //if can't create bucket
			}
			if (in_array($this->novp_storage_config['bucket'], (array) $s3->listBuckets())) {
				
				$fileExt = substr($_FILES['novp_file']['name'], strripos($_FILES['novp_file']['name'], '.'));
				$filesize = $_FILES['novp_file']['size'];
				$nameFile = '' . time() . uniqid();		
				$idu = intval($user);
				$title = substr($_FILES['novp_file']['name'], 0, strripos($_FILES['novp_file']['name'], '.'));

				if ($s3->putObjectFile($uploadFile, $this->novp_storage_config['bucket'], $nameFile . '/' . baseName($nameFile . $fileExt), S3::ACL_PUBLIC_READ)) {
					$r_bid = $this->db->get_where('buckets', array('bucket' => $this->novp_storage_config['bucket']));				
					if($r_bid->num_rows() > 0)
						$bid = $r_bid->result()[0]->id;
					else{
						exit("Bucket undefined!");
					}

					$date = new DateTime();
					$date = $date->format('Y-m-d H:i:s');

					$file_data = array(
						'user_id' => $user,
						'bucket_id' => $bid,
						'filename' => $title,
						'size' => $filesize,
						'ext' => $fileExt,
						'aws' => $nameFile,
						'date' => $date
					);

					$this->db->insert('novp_files', $file_data);
					$insert_id = $this->db->insert_id();
					$response = array(
						'status' => 1,
						'description' => "File was uploaded",
						'title' => $filesize,
						'src' => $this->novp_storage_config['src_host']."/".$this->novp_storage_config['bucket']."/".$nameFile."/".$nameFile.$fileExt,							
					);
					if($insert_id){
						echo json_encode($response);
					}else{
						$response['status'] = 0;
						$response['description'] = "Error while uploading";
						echo json_encode($response);
					}
				} 
			}
		}

    }

    private function get_files($user){
    	$response = array();
    	$user_id = intval($user);
    	$this->config->load('novp');
        $this->novp_storage_config = $this->config->item('aws');

    	$r_files = $this->db->get_where('novp_files', array("user_id" => $user_id));
    	$files = $r_files->result();
    	if(empty($files)){
    		$response['status'] = 0;
    		$response['description'] = "No files";
    		echo json_encode($response);
    	}else{
    		$files = json_encode($files);
    		$count_files = count($files);
    		$response['status'] = 1;    		
    		$response['description'] = $count_files." file(s) were found";
    		$response['file_data'] = $files;
    		echo json_encode($response);
    	}
    }

    private function getUserByToken($t){
    	$where = array(
    		"token" => $t
    	);
    	$query = $this->db->get_where('novp_tokens', $where);
    	if($query->num_rows()){
    		return $query->result()[0]->user_id;
    	}else{
    		return false;
    	}
    }

    private function getToken($id, $access = false){
    	$response = array();
    	if(!isset($id)){
    		$response["status"] = 0;
    		$response["description"] = "Access denied!";
    		echo json_encode($response);
    		return;
    	}

    	if(!$access){
    		$response["status"] = 0;
    		$response["description"] = "Access denied!";
    		echo json_encode($response);
    		return;
    	}else{
    		$r_tokens = $this->db->get_where('novp_tokens', array("user_id" => $id));
    		if($r_tokens){
    			$r_token = array_pop($r_tokens->result());
    			$token = $r_token->token;
    			return $token;
    		}else{
    			return false;
    		}
    	}

    }

    private function register(){
    	$data = (object)$this->input->post("params");
    	$response = array();    	
    	
    	$repo = $this->em->getRepository("Entity\User");
    	if($repo->findOneByUsername($data->username)){
    		$response["status"] = 4;
    		$response["description"] = "User already exists";    		
    		echo json_encode($response);
    	}else{
    		if(strlen($data->username) > 200 || strlen($data->password) > 200 || strlen($data->name) > 200){
    			$response["status"] = 3;
	    		$response["description"] = "Max length of field should be less then 200 symbols";    		
	    		echo json_encode($response);	
    		}
    		elseif(strlen($data->username) < 5 || strlen($data->password) < 5 || strlen($data->name) < 5){
    			$response["status"] = 2;
	    		$response["description"] = "Max length of field should be more then 4 symbols";    		
	    		echo json_encode($response);		
    		}else{
    			$response["status"] = 1;
	    		$response["description"] = "User was registered";

	    		$user = new \Entity\User();
	    		$user->setUsername($data->username);
	    		$user->setPassword($data->password);
	    		$user->setName($data->name);
	    		$user->setStatus(3);
	    		$this->em->persist($user);	    		
				$this->em->flush();

				$uid = $repo->findOneByUsername($data->username)->getId();
				if($uid){
					$this->db->insert('user_details',array('email'=>$data->email,'phone'=>'', 'user_id'=>$user->getId(),'avatar'=>'image/newuser.jpg','start_date'=>date("Y-m-d H:i:s", time()), 'contact_name'=>NULL, 'contact_details'=>NULL, 'created'=>1));

		    		$this->rbac->Users->assign('NoVP API', $uid);
		    		echo json_encode($response);		
				}	    		
	    		else{
	    			$response["status"] = 7;
	    			$response["description"] = "Unknown error, please contact developer using http://vk.com/alex_gaba";
	    			echo json_encode($response);		
	    		}
    		}    			
    	}
    }

    /*
    *  Checking user authorization session
    */
    private function authorization_check($user_id, $mode = false){
    	$response = array();
        // Searching session_id
        $this->db->like('user_data', '"uid";i:'.$user_id);
        $r_sess = $this->db->get('ci_sessions');
        $u_sess = $r_sess->result();
        if(count($u_sess) > 0){
        	// Checking status
        	$user_data = array_pop($u_sess);        	
        	if(stripos($user_data->user_data, "Successfull") > 0){
        		$response["status"] = 1;
        		$response["description"] = "User is online";	
        		if(!$mode)
        			echo json_encode($response);
        		return true;
        	}else{
        		$response["status"] = 0;
        		$response["description"] = "User is offline";
        		if(!$mode)
        			echo json_encode($response);
        		return false;
        	}
        }else{
        	// User obviously not login, cause he probably never logged in or even doesn't exist
        	$response["status"] = 0;
        	$response["description"] = "User is offline or doesn't exist";
        	if(!$mode)
        		echo json_encode($response);
        	return false;
        }

    }

    private function getDomainOrganisation($mode = false){
    	if(!$mode){
    		$response["status"] = 0;
			$response["description"] = "Acess denied!";
			echo json_encode($response);
			return;	
    	}else{
    		$request = $_SERVER['HTTP_REFERER'];
    		$requests = explode("/", $request);
    		

    		$domain = $requests[2];

    		return $domain;    		

    	}
    }

    private function signin(){
    	$response = array();
    	$data = (object)$this->input->post("params");
    	// Get login and pass
    	if($data){
    		$login = trim($data->login);
    		$pass = trim($data->pass);
    		$org = $this->getDomainOrganisation(true);
    	}    	

    	$repo = $this->em->getRepository("Entity\User");
		$auth = false;

		if(!isset($login) || empty($login)){
			$response["status"] = 0;
			$response["description"] = "Method required login and password";
			echo json_encode($response);
			return;
		}
		if(!isset($pass) || empty($pass)){
			$response["status"] = 0;
			$response["description"] = "Method required login and password";
			echo json_encode($response);
			return;	
		}
		
		$record = $repo->findOneByUsername($login);
		if($record){
			$auth = $record->checkPassword($pass);
		}

		if($auth){
			$uid = $record->getId();
			$isOnline = $this->authorization_check($uid, true);
			if($isOnline){
				$response["status"] = 1;
				$response["description"] = "User is online";
				// Get token
				$token = $this->getToken($uid, true);
				if($token){
					$response["token"] = $token;
				}else{
					$response["description"] = "Unhandled error please contact developer http://vk.com/alex_gaba";
				}
				echo json_encode($response);
				return;		
			}else{
				$this->session->set_userdata(array("uid" => $uid));
				$this->session->set_userdata(array("user_login_status" => "Successfull"));
				$this->session->set_userdata(array("organisation" => $org));
				$response["status"] = 1;
				$response["description"] = "User is online ";
				// Get token
				$token = $this->getToken($uid, true);
				if($token){
					$response["token"] = $token;
				}else{
					$response["description"] = "Unhandled error please contact developer http://vk.com/alex_gaba";
				}
				echo json_encode($response);
				return;
			}
		}else{
			// User undefined
			$response["status"] = 2;
			$response["description"] = "Wrong username or password";
			echo json_encode($response);
			return;	
		}
    }

    private function signout($uid){
    	$response = array();
    	
    	$repo = $this->em->getRepository("Entity\User");
		$auth = false;

		$user = $repo->findOneById($uid);
		if(!$user){
			$response["status"] = 0;
			$response["description"] = "User not found!";
			echo json_encode($response);
			return;
		}

		$isOnline = $this->authorization_check($uid, true);
		if(!$isOnline){
			$response["status"] = 1;
			$response["description"] = "User is already offline";
			echo json_encode($response);
			return;	
		}else{
			if(isset($this->session)){
				$this->session->sess_destroy();
				$response["status"] = 1;
				$response["description"] = "User is offline";
				echo json_encode($response);
				return;	
			}
			else{
				$response["status"] = 0;
				$response["description"] = "Can't get access from this device for such operation!";
				echo json_encode($response);
				return;		
			}
		}

    }

    private function upload_avatar($uid){
    	$response = array();
    	$this->config->load('upload');
    	$allowedExt = explode("|", $this->config->item("allowed_image_types"));
		$maxFileSize = $this->config->item("image_max_size");		

		$ext = end(explode('.', strtolower($_FILES['novp_file']['name'])));
		
		if (!in_array($ext, $allowedExt)) {
			$response["status"] = 0;
			$response["description"] = "Uncorrect file extension";
			echo json_encode($response);
			return;
		}

		if ($maxFileSize < $_FILES['novp_file']['size']) {
			$response["status"] = 0;
			$response["description"] = "Uncorrect file size";
			echo json_encode($response);
			return;
		}

		$uploadDir = APPPATH."../image/";
		$file_name = md5($_FILES['novp_file']['name'].$uid).".".$ext;
		$uploadFile = $uploadDir.$file_name;

		if(move_uploaded_file($_FILES['novp_file']['tmp_name'], $uploadFile)) {
			// Send record to DB
			$repo = $this->em->getRepository("Entity\UserDetail");
			$userd = $repo->findBy(array("userId" => $uid));
			$userd = array_pop($userd);

			$userd->setAvatar($file_name);
			$this->em->persist($userd);
			$this->em->flush();

		    $response["status"] = 1;
		    $response["description"] = "File was uploaded";
		    $response["filename"] = $file_name;
			echo json_encode($response);
		    return;
		}else {
		    $response["status"] = 0;
		    $response["description"] = "Unhandled error please contact developer http://vk.com/alex_gaba";
			echo json_encode($response);
		    return;
		}

    }

    private function set_name($uid){
    	$response = array();   
    	$data = (object)$this->input->post("params");
    	if($data){    		
    		$fstname = trim($data->fstname);
    		$lstname = trim($data->lstname);
    	}
    	
    	if(!isset($fstname) || !isset($lstname)){
    		$response["status"] = 0;
    		$response["description"] = "First Name or Last Name required!";
    		echo json_encode($response);
    		return;
    	}
    	if(empty($fstname) || empty($lstname)){
    		$response["status"] = 0;
    		$response["description"] = "Empty First Name or Last Name";
    		echo json_encode($response);
    		return;
    	}
    	$repo = $this->em->getRepository("Entity\User");
    	$user = $repo->findOneById($uid);
    	$name = $fstname." ".$lstname;
    	$user->setName($name);
    	$this->em->persist($user);
    	$this->em->flush();

    	$response["status"] = 1;
    	$response["description"] = "User name was changed to ".$name;
    	echo json_encode($response);
    	return;
    }
}