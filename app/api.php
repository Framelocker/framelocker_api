<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!class_exists('Auth')) require_once APPPATH."controllers/auth.php";

if (!class_exists('S3')) require_once (APPPATH.'libraries/S3.php');

use Aws\S3\S3Client as S3c;
use Aws\Sns\SnsClient;
use Aws\Common\Aws;
use Aws\ElasticTranscoder\ElasticTranscoderClient;

class API extends Auth{

	private $novp_storage_config;

	public function __construct() {
        parent::__construct();
		$this->load->helper('novpapp');                         
    }

    public function index(){  
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');

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
        set_time_limit(0);        
    	$this->config->load('novp');
    	$this->config->load('upload');
    	$allowedExt = explode("|", $this->config->item("allowed_types"));
        $video_types = explode('|', $this->config->item("video_types"));
		$maxFileSize = $this->config->item("max_size");
        $aws_settings = $this->config->item("aws");
        $transcode_settings = $this->config->item("elastic_transcoder_client");
        $transcode_presets = $transcode_settings['presets'];

        $response = array();

        $config = array(
            'key' => $aws_settings['access_key'],
            'secret' => $aws_settings['secret_key'],
            'region' => $aws_settings['region']
        );

		$ext = end(explode('.', strtolower($_FILES['novp_file']['name'])));
		
		if (!in_array($ext, $allowedExt)) {
			echo 'Denied exstension';
			exit;
		}

		if ($maxFileSize < $_FILES['novp_file']['size']) {
			$response['status'] = 0;
            $response['description'] = 'Max upload size limit!';
            echo json_encode($response);
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
                $description = '';

                if($this->input->post('title')){
                    $title = $this->input->post('title');
                }

                if($this->input->post('description')){
                    $description = $this->input->post('description');
                }
				
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
						'date' => $date,
                        'description' => $description
					);

                    // Transcode if video
                    $file_ext = explode('.', $fileExt)[1];
                    if(in_array($file_ext, $video_types)){
                        $client = ElasticTranscoderClient::factory($config);

                        $result = $client->createJob(array(
                                // PipelineId is required
                                'PipelineId' => $transcode_settings['pipeline_id'],
                                // Input is required
                                'Input' => array(
                                    'Key' => $nameFile . '/' . $nameFile . $fileExt,
                                    'FrameRate' => 'auto',
                                    'Resolution' => 'auto',
                                    'AspectRatio' => 'auto',
                                    'Interlaced' => 'auto',
                                    'Container' => 'auto',
                                ),
                                'Outputs' => array(                                    
                                    array(
                                        'Key' => $nameFile . '/' . $nameFile .'default.mp4',
                                        'ThumbnailPattern' => "",
                                        'Rotate' => 'auto',
                                        'PresetId' => $transcode_presets['default'],
                                    ),
                                    array(
                                        'Key' => $nameFile . '/' . $nameFile .'mobile.mp4',
                                        'ThumbnailPattern' => "",
                                        'Rotate' => 'auto',
                                        'PresetId' => $transcode_presets['mobile'],
                                    ),
                                ),
                            )); 


                           /*$resultPipe = $client->createPipeline(array(
                                // Name is required
                                'Name' => '1410534240472-ip1dmm',
                                // InputBucket is required
                                'InputBucket' => 'novp_files',
                                'OutputBucket' => 'novp_files',                                
                                'Role' => 'arn:aws:iam::291234165292:role/transcoder',
                            ));                      
                            */
                    }

					$this->db->insert('novp_files', $file_data);
					$insert_id = $this->db->insert_id();
					$response = array(
						'status' => 1,
						'description' => "File was uploaded",
						'title' => $title,
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
        $this->config->load('novp');
        $this->novp_storage_config = $this->config->item('aws'); 
        $this->novp_trans = $this->config->item('elastic_transcoder_client'); 

        $response = array();
    	$user_id = intval($user);    	

    	$r_files = $this->db->get_where('novp_files', array("user_id" => $user_id));
    	$files = $r_files->result();
        
    	if(empty($files)){
    		$response['status'] = 0;
    		$response['description'] = "No files";
    		echo json_encode($response);
    	}else{
            $count_files = count($files);  

            foreach ($files as &$file) {
                $file->src = $this->novp_storage_config['src_host']."/".$this->novp_storage_config['bucket']."/".$file->aws."/".$file->aws.$file->ext;
                foreach($this->novp_trans['presets'] as $pres => $pres_code){
                    $file->additional_src[] = $this->novp_storage_config['src_host']."/".$this->novp_storage_config['bucket']."/".$file->aws."/".$file->aws.$pres.'.mp4';
                }
                
            }           

    		$files = json_encode($files);    		
    		$response['status'] = 1;    		
    		$response['description'] = $count_files." file(s) were found";
    		$response['file_data'] = $files;
    		echo json_encode($response);
    	}
    }

    private function edit_file(){
        $response = array();

        $data = (object)$this->input->post("params");
        if(!isset($data->fid)){
            $response['status'] = 0;            
            $response['description'] = "Required Content ID";
            echo json_encode($response);
            return;
        }

        if(!isset($data->title) && !isset($data->description)){
            $response['status'] = 0;            
            $response['description'] = "Required Title or Description";
            echo json_encode($response);
            return;
        }
        
        $req = array();
        if(!empty($data->title)){
            $req['filename'] = $data->title;
        }
        if(!empty($data->description)){
            $req['description'] = $data->description;
        }

        $this->db->where(array('id'=>$data->fid));
        $this->db->update('novp_files', $req);
    }

    private function delete_files($uid){
        $response = array();

        $fids = $this->input->post('fids');      
        $this->db->where_in('id', $fids);
        $this->db->delete('novp_files');

        $response['status'] = 1;            
        $response['description'] = "File(s) were deleted";
        echo json_encode($response);
        return;
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

    private function getUserInfo($user_id){        
    	$response = array();        
        $repo = $this->em->getRepository("Entity\User");
        $user = $repo->findOneById($user_id);
        $user_info = $user->toArray();
        echo json_encode($user_info);    	
    	return;
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

        if(!isset($data->username)){
            $response["status"] = 0;
            $response["description"] = "Undefined Username Or Password";
            echo json_encode($response);        
            return false;
        } 	
		
		$data->username = trim($data->username);
		
        if(!isset($data->name))
            $data->name = !empty($data->username) ? $data->username : ''; 
		
    	$repo = $this->em->getRepository("Entity\User");
    	if($repo->findOneByUsername($data->username)){
    		$response["status"] = 0;
    		$response["description"] = "User already exists";    		
    		echo json_encode($response);
    	}else{
    		if(strlen($data->username) > 200 || strlen($data->password) > 200){
    			$response["status"] = 0;
	    		$response["description"] = "Max length of field should be less then 200 symbols";    		
	    		echo json_encode($response);	
    		}
    		elseif(strlen($data->username) < 5 || strlen($data->password) < 5){
    			$response["status"] = 0;
	    		$response["description"] = "Max length of fields should be more then 4 symbols";    		
	    		echo json_encode($response);		
    		}else{
    			$response["status"] = 1;
	    		$response["description"] = "User was registered";
				
				if(!filter_var($data->username,FILTER_VALIDATE_EMAIL))
				{
					$response["status"] = 0;
	    			$response["description"] = "Email is not valid";
	    			echo json_encode($response);
                    exit;
				}
				
	    		$user = new \Entity\User();
	    		$user->setUsername($data->username);
	    		$user->setPassword($data->password);                
	    		$user->setName($data->name);
	    		$user->setStatus(3);
	    		$this->em->persist($user);	    		
				$this->em->flush();

				$uid = $repo->findOneByUsername($data->username)->getId();
                $creator = $this->em->getRepository('Entity\Institution')->getInstituteAdminsIds($this->data['institute']->getId());
                $creator_id = $creator['user_id'];                

				if($uid){
					$this->db->insert('user_details',array('email'=>$data->username,'phone'=>'', 'user_id'=>$user->getId(),'avatar'=>'image/newuser.jpg','start_date'=>date("Y-m-d H:i:s", time()), 'contact_name'=>NULL, 'contact_details'=>NULL, 'created'=>$creator_id));

                    $this->db->insert('institution_user', array('institution_id'=>$this->data['institute']->getId(), 'user_id'=>$uid));		    		
                    $this->rbac->Users->assign('NoVP API', $uid);
                    // Upload avatar
                    if(array_key_exists('novp_file', $data)){
                        $this->upload_avatar($uid);
                    }
		    		echo json_encode($response);		
				}	    		
	    		else{
	    			$response["status"] = 0;
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

    private function signin(){        
    	$response = array();
    	$data = (object)$this->input->post("params");
    	// Get login and pass
    	if($data){
    		$login = trim($data->login);
    		$pass = trim($data->pass);
    		$org = $this->data['institute'];
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
			$auth = $record->checkPassword($pass, $this->data['institute']);
		}

		if($auth){
			$uid = $record->getId();
			$isOnline = $this->authorization_check($uid, true);
			if($isOnline){
				$response["status"] = 1;
				$response["description"] = "User is online";
				// Get token
				$token = update_token($uid,$this->em,$this->db);             
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
				$token = update_token($uid,$this->em,$this->db);
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

    private function get_boxes($uid){
        $uid = intval($uid);
        $boxes = $this->db->get('box')->result();
        $response = array();
        if(!empty($boxes)){
            $response['status'] = 1;
            $response['description'] = count($boxes)." boxes were found";
            $response['boxes'] = $boxes;
            echo json_encode($response);
            return;
        }else{
            $response['status'] = 0;
            $response['description'] = "No boxes were found";
            echo json_encode($response);
            return;
        }
    }

    private function assign_box($uid){
        $response = array();   
        $data = (object)$this->input->post("params");

        if(isset($data->user_id) && isset($data->box_id)){
            $box_data = array(
                'user_id' => intval($data->user_id),
                'box_id' => intval($data->box_id)
            );

            $id = intval($data->user_id);
            $bid = intval($data->box_id);

            // Select if such box exists for assigning
            $r_box = $this->db->get_where('box', array('id'=>$bid))->result();
            if(count($r_box) == 0){
                $response['status'] = 0;
                $response['description'] = "Error with matching Box-User";            
                echo json_encode($response);    
                return;
            }         
            // Check for duplicate row
            $r_box_user = $this->db->get_where('box_user', array('user_id'=>$id, 'box_id'=>$bid))->result();
            if(count($r_box_user) == 0){
                $this->db->insert('box_user', $box_data);
                $response['status'] = 1;
                $response['description'] = "Successfull assigning box to user";            
                echo json_encode($response);
                return;
            }else{                
                $response['status'] = 1;
                $response['description'] = "User was already assigned to box";
                echo json_encode($response);
                return;
            }
            
        }else{
            $response['status'] = 0;
            $response['description'] = "Error with assigning box to user";            
            echo json_encode($response);
            return;
        }
    }

    private function add_user($uid){        
        $params = $this->input->post('params');
        $response = array();
        
        if(!isset($params) || !$params){
            $response['status'] = 0;
            $response['description'] = 'Access Denied. Required parameters';
            echo json_encode($response);
            return;
        }            

        $res = array('success'=>0);
        
        if(!$this->rbac->check('adduser', $uid))
        {
            $response['status'] = 0; 
            $response['description'] = 'Access denied. User has no rights';
            echo json_encode($response);
            return;
        }
        
        $inviteRepos = $this->em->getRepository('Entity\Invites');
        $iid = $inviteRepos->addInvite($params,$this, true, $uid);
        $errors = $inviteRepos->getErrors();
        if(!empty($errors)){
            $response['status'] = 0; 
            $response['description'] = 'Multiple errors has apeared';
            echo json_encode($response);
            return;
        }
        else{
            $response['status'] = 1; 
            $response['description'] = 'Invitaion was send successfuly';
            echo json_encode($response);
            return;
        }
        
        exit;
    }

    private function assigning_to_facebook($uid){
        $response = array();
        $fid = $this->input->post('fid');
        if(!isset($fid) || empty($fid)){
            $response['status'] = 0;
            $response['description'] = 'Undefined fid. Expecting POST value fid';
            echo json_encode($response);
            return;
        }

        // Geting user
        $repo = $this->em->getRepository("Entity\User");
        $user = $repo->findOneById($uid);
        if(!isset($user) || empty($user)){
            $response['status'] = 0;
            $response['description'] = 'Undefined user';
            echo json_encode($response);
            return;
        }

        $fid = intval($fid);
        $user->setFid($fid);
        $this->em->persist($user);
        $this->em->flush();

        $response['status'] = 1;
        $response['description'] = 'Successfull assigning facebook ID to user';
        echo json_encode($response);
        return;

    }

    private function prime_check($uid){
        $response = array();
        
        if($this->rbac->check('prime_novp', $uid)){
            $response['status'] = 1;
            $response['description'] = 'User has Prime Account';
            echo json_encode($response);
            return;
        }else{
            $response['status'] = 0;
            $response['description'] = 'User has General Account';
            echo json_encode($response);
            return;
        }
        
        
    }

}