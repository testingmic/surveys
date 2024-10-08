<?php
namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;

class AppController extends ApiServices {

    use ResponseTrait;

    public $baseURL;
    public $AppAdminURL;
    public $sessObject;
    public $imageObject;
    public $apiVersion;
    private $appIsDown = false;
    public $accepted_images_types = ".jpg,.png,.jpeg";
    public $permission_denied = "Sorry! You are not permitted to access this resource.";
    public $processing_error = "Sorry! An unexpected error was encountered while processing the request.";

    // Survey Categories
    public $surveyCategories = [
        0  => "Select a Category",
        1  => "Community or volunteer feedback",
        2  => "Customer feedback",
        3  => "Concept, product, or ad testing",
        4  => "Brand tracking or awareness",
        5  => "General market research",
        6  => "Employee engagement",
        7  => "Employee performance",
        8  => "General employee feedback",
        9  => "Event registration",
        10 => "Event feedback",
        11 => "Academic research",
        12 => "Course evaluation",
        13 => "Student or parent feedback",
        14 => "Quiz",
        15 => "Other",
        16 => "Form or application",
        17 => "Vote or poll"
    ];

    public function __construct() {

        $this->apiVersion = config('Api')->api_version;
        $this->sessObject = session();
        $this->imageObject = \Config\Services::image();
        $this->baseURL = trim(config('App')->baseURL, '/');
        
    }

    /**
     * Modify content to parse
     * 
     * @return String
     */
    public function not_found_text($section = "survey") {
        return "This {$section} has been deleted or you are not authorized to access this page.";
    }

    /**
     * Display the file
     * 
     * @param String $filename
     * 
     * @return Mixed
     */
    public function show_display($filename, $data = []){

        try {
            // set the count
            $data['count'] = 1;
            $data['metadata'] = [];

            // set the user data
            $data['isLoggedIn'] = $this->is_logged_in();
            $data['user'] = $this->_userData;
            
            // set the user agent
            $data['ip_address'] = $this->request->getIPAddress();

            // confirm if the user is logged in
            if( !empty($data['user']) ) {
                unset($data['user']['password']);
                $data['metadata'] = $data['user']['metadata'] ?? [];
            }
            
            // show the page
            return view($filename, $data);
        } catch(\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Login Check
     * 
     * @return Bool
     */
    public function is_logged_in() {
        
        if(!empty($this->sessObject->_userApiToken) && !empty($this->sessObject->_clientId) && !empty($this->sessObject->_userId)) {
            $authObject = new \App\Controllers\v1\AuthController();
            $this->_userData = $authObject->user_data($this->sessObject->_userId, $this->apiVersion, $this->sessObject->_userApiToken);
            return true;
        }

        return false;

    }

    /**
     * Login Check
     * 
     * @return Bool
     */
    public function login_check($page = "landing") {
        
        if(empty($this->sessObject->_clientId) && empty($this->sessObject->_userApiToken)) {
            try {
                $data['isLoggedIn'] = false;
                $data['ip_address'] = $this->request->getIPAddress();
                die( view($page, $data) );
            } catch(\Exception $e) {
                die( view("not_found", [
                    'pagetitle' => 'Access Denied',
                    'content' => $this->permission_denied
                ]) );
            }
        }

        return true;

    }

    /**
     * Api Request Response
     * 
     * @param String        $method
     * @param String        $page
     * @param Array|String  $request
     * @param String        $title      The name of the object being created
     * 
     * @return Array
     */
    public function api_response($request, $method = "GET", $page = null) {
        
        $info['code'] = $request['code'] ?? 203;
        $method = strtoupper($method);

        if(isset($request['result'])) {
            $info['data']['result'] = $request['result'];
            $info['data']['additional'] = $request['additional'] ?? null;
        }

        if( in_array($info['code'], [200, 201]) ) {
            $item_name = $request['result']['title'] ?? ($request['result']['username'] ?? ucwords($page));

            if( isset($request['result']['id']) ) {

                if( $method == 'POST' ) {
                    $info['data']['result'] = "{$item_name} record successfully created.";

                    $info['data']['additional'] = [
                        'clear' => true,
                        'href' => "{$page}/modify/{$request['result']['slug']}/" . ($request['additional']['route'] ?? "edit")
                    ];
                    
                } elseif( ($method == 'PUT') ) {
                    
                    $info['data']['result'] = "{$item_name} record successfully updated.";

                    if(isset($request['additional'])) {
                        $info['data']['additional'] = $request['additional'];
                    }
                }
            }
            
        }

        elseif($method == "DELETE") {

            if(isset($request['code'])) {
                return $this->respond($request, 200);
            }

            if(contains($request, ['has successfully been deleted'])) {
                $info['code'] = 200;
                $info['data']['result'] = $request;
            } else {
                $info['code'] = 200;
                $info['data']['result'] = $request;
            }

        }

        elseif( !is_array($request) || !isset($request['code']) ) {
            $info['data']['result'] = $request;
        }

        if( empty($info['data']['additional']) ) {
            unset($info['data']['additional']);
        }

        return $this->respond($info, 200);
    }

    /**
     * Return an error response
     * 
     * @param String        $response
     * 
     * @return JSON
     */
    public function error($response = null, $code = 203) {
        return $this->respond(['code' => $code, 'data' => ['result' => $response]], 200);
    }

    /**
     * Delete a Record
     * 
     * @param   String      $params['record']
     * @param   Int         $params['record_id']
     * 
     * @return Array
     */
    public function delete() {

        $record = $this->request->getVar('record');
        $record_id = $this->request->getVar('record_id');

        if( empty($record) || empty($record_id) ) {
            return 'Ensure the record and record_id variables are not empty.';
        }

        $request = $this->api_lookup("DELETE", "{$record}/{$record_id}");

        return $this->api_response("DELETE", $record, $request);
    }

    /**
     * Change the Status of a record
     * 
     * @param   String      $params['record']
     * @param   Int         $params['record_id']
     * 
     * @return Array
     */
    public function status() {

        $record = $this->request->getVar('record');
        $record_id = $this->request->getVar('record_id');

        if( empty($record) || empty($record_id) ) {
            return 'Ensure the record and record_id variables are not empty.';
        }

        $request = $this->api_lookup("PUT", "{$record}/state", ['primary_key' => $record_id]);

        if( isset($request['code']) && ($request['code'] == 200) ) {
            $status = $request['result']['status'] ?? $request['result'][0]['status'];
            $request['additional']['status_id'] = (int) $status;
            $request['code'] = 200;
            $request['result'] = $request['result'][0] ?? $request['result'];
        }
        
        return $this->api_response("PUT", $record, $request);
    }
    
}
