<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require_once APPPATH."controllers/auth.php";

class Novp extends Auth{

	public function __constructor(){
        parent::__construct();
    }

    public function index(){
    	if($this->input->post("type") && $this->input->post("type") == "ajax"){
    		// Ajax Methods
    		$ajax_method = $this->input->post("method");
    		if($ajax_method){
    			$this->$ajax_method();
    		}
    	}else{
    		$this->config->load('aws');
			$aws_config = $this->config->item('aws');
			if($this->authedUser){
				if (!check_access($this->authedUser, 'use_novp_api'))
					redirect(base_url('app/novp/start/'));
	            else{
	            	redirect(base_url('app/novp/videos/'));
	            }            
			}

	    	$this->data['page_title'] = 'Welcome to NoVP';
		    $this->load->view('novp/views/header', $this->data);	    
		    $this->load->view('novp/views/index', $this->data);
		    $this->load->view('novp/views/footer');
    	}    	
    }

    public function videos(){    	
		if(!isset($this->authedUser))
			redirect(base_url('app/novp/'));
		else{
			$this->data['user'] = $this->authedUser;            
            if (!check_access($this->authedUser, 'use_novp_api')) {
				redirect(base_url('app/novp/start/'));
			}
		}
        if(!empty($this->authedUser->toArray()['details']))
            $this->data['user_details'] = $this->authedUser->toArray()['details'][0];
        

    	$this->data['page_title'] = 'Admin | NOVP - Power your video with NOVP';
    	$this->data['menu_active'] = "videos";
	    $this->load->view('novp/views/header', $this->data);
	    $this->load->view('novp/views/nav');	    	    	    
	    $this->load->view('novp/views/videos');	    
	    $this->load->view('novp/views/footer');
    }

    public function start(){
    	if(!$this->authedUser)
    		redirect(base_url('app/novp/'));
    	else{
    		$novp_role = $this->rbac->Roles->returnId("NoVP API");    		
    		$this->rbac->Users->assign($novp_role, $this->authedUser->getId());
    		redirect(base_url('app/novp/videos/'));
    	}
    }

    public function settings(){
    	if(!$this->authedUser)
    		redirect(base_url('app/novp/'));
    	else{    		
            if (!check_access($this->authedUser, 'use_novp_api')) {
				redirect(base_url('app/novp/start/'));
			}
    	}

    	// Get token
    	$r_token = $this->db->get_where('novp_tokens', array('user_id' => $this->authedUser->getId()));
    	if($r_token->num_rows() == 1){
    		$token = $r_token->result()[0]->token;
    	}else{
    		$token = "Click Refresh icon to get token";
    	}


    	$this->data['user'] = $this->authedUser;
    	$this->data['token'] = $token;
    	$this->data['user_details'] = $this->authedUser->toArray()['details'][0];
    	$this->data['page_title'] = 'Settings | NOVP - Power your video with NOVP';
    	$this->data['menu_active'] = "settings";
	    $this->load->view('novp/views/header', $this->data);
	    $this->load->view('novp/views/nav');	    	    
	    $this->load->view('novp/views/settings');	    
	    $this->load->view('novp/views/footer');
    }

    public function test(){
    	$this->load->helper('form');
    	// Get token
    	
        $r_token = $this->db->get_where('novp_tokens', array('user_id' => $this->authedUser->getId()));
    	if($r_token->num_rows() == 1){
    		$token = $r_token->result()[0]->token;
    	}else{
    		$token = "Click Refresh icon to get token";
    	}
    	$this->data['token'] = $token;
        
    	$this->data['menu_active'] = "test";
    	$this->load->view('novp/views/header', $this->data);
	    $this->load->view('novp/views/nav');	    	    
	    $this->load->view('novp/views/test');	    
	    $this->load->view('novp/views/footer');	
    }

    private function update_token(){
    	$uid = $this->input->post("uid");
    	$uid = intval($uid);

    	// If exist user
        $r_user = $this->em->getRepository("Entity\User")->findOneById($uid);
    	
    	if(!isset($r_user))
    		exit("Access Denied");
    	else{
    		$date = new DateTime();
    		$date = $date->format('Y-m-d H:i:s');
            $u_name = $r_user->getUsername();
    		$to_encode = $uid.$u_name.$date;
    		$token = sha1($to_encode);

    		// Update user token or obtaining
    		$r_token = $this->db->get_where('novp_tokens', array("user_id" => $uid));
    		if($r_token->num_rows() == 0){
    			// Add token
    			$this->db->insert('novp_tokens', array("user_id" => $uid, "token" => $token));
    		}else{
    			// Update token
    			$this->db->where('user_id', $uid);
    			$this->db->update('novp_tokens', array('token' => $token));
    		}

    		echo $token;
    	}

    }
}