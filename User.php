<?php
defined('BASEPATH') OR exit('No direct script access allowed');

//include Rest Controller library
//require APPPATH . '/libraries/REST_Controller.php';
//require(APPPATH.'/libraries/REST_Controller.php');
//use application\libraries\REST_Controller;

//require APPPATH .'libraries/REST_Controller.php';
//require('application/libraries/REST_Controller.php');
//require APPPATH . 'libraries/Format.php';

//require(APPPATH'.libraries/REST_Controller');
//namespace Restserver\Libraries;

//require_once("application/libraries/REST_Controller.php");
//require_once("application/libraries/Format.php");

//require_once('libraries/Redis1.php');

$config = array(
    'protocol' => 'smtp', // 'mail', 'sendmail', or 'smtp'
    'smtp_host' => 'smtp.example.com', 
    'smtp_port' => 465,
    'smtp_user' => 'no-reply@example.com',
    'smtp_pass' => '12345!',
    'smtp_crypto' => 'ssl', //can be 'ssl' or 'tls' for example
    'mailtype' => 'text', //plaintext 'text' mails or 'html'
    'smtp_timeout' => '4', //in seconds
    'charset' => 'iso-8859-1',
    'wordwrap' => TRUE
);

class User extends CI_Controller{

//	function __construct($config = 'rest'){
//		parent::__construct($config);
//
//		$this->load->database();
//		$this->load->model('Users');
//		$this->load->library('encryption');
//	}

	public function __construct()
	{
		parent::__construct();

		$this->load->database();
		$this->load->model('Users');
		$this->load->model('admin/University_model');
		$this->load->library('encryption');
		$this->load->library('upload');
		$this->load->helper('common_helper');
		$this->load->helper(array('form', 'url'));
		$this->load->library('form_validation');
        $this->load->library('email');
//		$this->load->driver('cache', ['adapter'=>'redis']);

//		$this->load->driver('cache', array('adapter' => 'redis', 'backup' => 'file'));
//		$this->load->library('format');

	}

	public function index()
	{

	}

	//login
	public function login(){

		$email = $this->input->post('email');
		$password = $this->input->post('password');
		$device_token = $this->input->post('device_token');
		$user_type = $this->input->post('user_type');

		if ($user_type == '2'){

		}

		$encrypt_pass = base64_encode($password);

//		$encrypt_pass = password_hash($password,PASSWORD_DEFAULT);

		$user = $this->Users->checkemail($email,$encrypt_pass,$user_type);

		if (!$user){
			$data['status'] = 'false';
			$data['message'] = 'Email/Password does not Exist';
			$data['data'] = '';
			echo json_encode($data);
		}
		else{

			if ($user[0]->is_active == '0'){
				$data['status'] = 'false';
				$data['message'] = 'User is inactive';
				$data['data'] = '';
				echo json_encode($data);
			}
			else{
				$check_fcm = $this->Users->checkfcm($user[0]->user_id,$device_token);

				$data['status'] = 'true';
				$data['message'] = 'User Logged in Successfully';
				$data['data'] = $user;
				echo json_encode($data);

//				$verify_pass = password_verify($password,$user[0]->password);
//				if (!$verify_pass){
//					$data['status'] = 'false';
//					$data['message'] = 'Enter Wrong Password';
//					$data['data'] = '';
//					echo json_encode($data);
//				}
//				else{
//
//				}

			}
		}
	}

	//validate email
	public function validateEmail(){

		$jsonArray = json_decode(file_get_contents('php://input'),true);

		$email = $jsonArray['email'];
		$user_type = $jsonArray['user_type'];

		$isExist = $this->Users->checkexistance($email,$user_type);

		if($isExist){
//			$this->response("Email Already Exist", 400);
			$data['status_code'] = '400';
			$data['status'] = 'false';
			$data['message'] = 'Email Already Exist';
			$data['data'] = '';
			echo json_encode($data,set_status_header(400));
		}
		else{
			$data['status'] = 'true';
			$data['message'] = 'Successfully Validated.';
			$data['data'] = '';
			echo json_encode($data);
		}

	}

	//image upload
	public function imageUpload(){

		$jsonArray = json_decode(file_get_contents('php://input'),true);
		$image = $jsonArray['image'];

		$image_name = md5( uniqid($image));
		$filename = $image_name . '.' . 'png';

		$path = './images/users/profile_picture/';
		$upload = file_put_contents($path . $filename, $image_name);
		$url = base_url().'images/users/profile_picture/'.$filename;

		if($upload){

			$data['status_code'] = '200';
			$data['status'] = 'true';
			$data['message'] = 'Image Uploaded Successfully.';
			$data['data'] = $url;
			echo json_encode($data,set_status_header(200));

		}
		else{

			$data['status_code'] = '400';
			$data['status'] = 'false';
			$data['message'] = 'Error Occur in Image Uploading';
			$data['data'] = '';
			echo json_encode($data,set_status_header(400));
		}

	}

	//user signup
	public function signUp(){
		$jsonArray = json_decode(file_get_contents('php://input'),true);
 
//		print_r($jsonArray['email']);die;

		$first_name = $jsonArray['first_name'];
		$last_name = $jsonArray['last_name'];
		$email = $jsonArray['email'];
		$password = $jsonArray['password'];
		$mobile_number = $jsonArray['mobile_number'];
		$image_url = $jsonArray['image'];
		$type_id = $jsonArray['user_type'];
		$street = $jsonArray['street'];
		$unit = $jsonArray['unit'];
		$city_state = $jsonArray['city_state'];
		$zip_code = $jsonArray['zip_code'];
		$latitude = $jsonArray['latitude'];
		$longitude = $jsonArray['longitude'];
		$this->sendSignUpCode($email);
		//Checking if user already exists!
		$check_user = $this->Users->checkexistance($email,$type_id);
		if($check_user != false){
			$data['status_code'] = '400';
			$data['status'] = 'false';
			$data['message'] = 'User Already Exists!';
			$data['data'] = '';
			echo json_encode($data,set_status_header(400));
			die;
		}

	   // checking if the user sigup request  is for Student
	   if($type_id == "2"){
		    
			$email_extension = substr($email, strpos($email, '@') + 1);
			$email_extension = substr($email_extension, 0,strpos($email_extension, '.') );

			//retrieve universities
		   $universities  = $this->University_model->getUniversity();
		   $uni_matched = false;
		   	//checking if the email has the extension of the registered universities
		    foreach($universities as $uni){
				if($uni->email_extension == $email_extension){
					$uni_matched = true;
				}
			}

			// if uni extension not matched then throw error
			if(!$uni_matched){
				$data['status_code'] = '400';
				$data['status'] = 'false';
				$data['message'] = 'Email is not valid for a Student';
				$data['data'] = '';
				echo json_encode($data,set_status_header(400));
				die;
			}
	   }


		//generating code for redis
		$unique_string = generatecode();

		$this->load->library('redis');
		$redis = $this->redis->config();
		
		$set_email = $redis->hmset('Verfication_Tokens:'.$email,array('email' => $email, 'token' => $unique_string));
		
		$e = $redis->hgetall('Verfication_Tokens:'.$email);


		$user_data = array(
			'first_name' => $first_name,
			'last_name' => $last_name,
			'email' => $email,
			'password' => base64_encode($password),
			'phone_number' => $mobile_number,
			'profile_picture' => $image_url,
			'type_id' => $type_id,
		);

		$insert_id = $this->Users->insert($user_data);
		if ($insert_id){

			foreach ($jsonArray['kids'] as $kids){

				$child[] = $kids;

				$childdata = array(
					'name' => $kids['name'],
					'age' => $kids['age'],
					'gender' => $kids['gender'],
					'description' => $kids['description'],
					'parent_id' => $insert_id,
				);
				$child_data = $this->Users->insert_children($childdata);
			}

			$address_data = array(
				'street' => $street,
				'unit' => $unit,
				'city_state' => $city_state,
				'zip_code' => $zip_code,
				'latitude' => $latitude,
				'longitude' => $longitude,
				'user_id' => $insert_id,
			);
			$this->Users->insert_address($address_data);
			$data_array = array_merge($user_data,$address_data);
			
			
			$data['status_code'] = '200';
			$data['status'] = 'true';
			$data['message'] = 'Data Inserted Successfully!!';
			$data['data'] = $data_array;
			$data['kids'] = $child;
			echo json_encode($data,set_status_header(200));
		


		}
		else{
			$data['status_code'] = '400';
			$data['status'] = 'false';
			$data['message'] = 'Error Occured';
			$data['data'] = '';
			echo json_encode($data,set_status_header(400));
		}

	}
	function sendSignUpCode( $email) {
	
        $config = array();
		$config['protocol'] = 'smtp';
$config['smtp_host'] = 'mail.supremecluster.com';
$config['smtp_user'] = 'support@healthsolutions.com.pk';
$config['smtp_pass'] = 'Support123!@#';
$config['smtp_port'] = 25;
		$this->email->initialize($config);

		$this->email->from('support@healthsolutions.com.pk', 'Bachat Mart');
		$this->email->to($email);
		$subject = 'Sign Up Verification Code';
        $message = 'Verification code: '.mt_rand(1000,9999);
        $this->email->subject($subject);
        $this->email->message($message);

        if ($this->email->send()) {
            echo 'Your Email has successfully been sent.';
        } else {
            show_error($this->email->print_debugger());
		}
		die;
    }

	public function saveDeviceToken(){

		$device_token = $this->input->post('device_token');
		$device_type = $this->input->post('device_type');
		$user_id = $this->input->post('user_id');

		if (isset($user_id)){

			$update_devicetoken = $this->Users->update_devicetoken($this->input->post('user_id'),$device_token,$device_type);

			$data['status'] = 'true';
			$data['message'] = 'Device Token Updated Successfully!';
			$data['data'] = '';
			echo json_encode($data);
		}
		else{
			$insert_devicetoken = $this->Users->insert_devicetoken($device_token,$device_type);

			$data['status'] = 'true';
			$data['message'] = 'Device Token Inserted Successfully!';
			$data['data'] = '';
			echo json_encode($data);
		}
	}
}
