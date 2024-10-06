<?php

namespace App\Controllers;

use App\Controllers\cronjobs\FilesCron;
use CodeIgniter\Database\RawSql;

class Crontab extends BaseController {

    /**
     * Command Line Actions
     * 
     * These are a list of the commands that can be executed at any point in time
     * 
     * FILES
     * 
     * root files removetemp                 Delete all temporary files
     * 
     * SYSTEM
     * 
     * root system shutdown 30_minute        system to be restored in 30 minutes from the time of executing command
     * root system shutdown 3_hour           system to be restored in 3 hours from the time of executing command
     * root system shutdown 10_minute        system to be restored in 10 minutes from the time of executing command
     * 
     * TABLE ROW STATUS CHANGE
     * 
     * The row id can be comma separated followed by the status to set
     * 
     * root changestatus residence 4,5,6,7,9,8_0
     * root changestatus users 1_0
     * root changestatus programmes 1,2,3,4,5,6_0
     * 
     * root selecttable users "client_id=5 & id > 5 & email like '%emmallob14@gmail.com%' LIMIT 5"
     * root selecttable programmes "client_id=1"
     * root selecttable residence "client_id=2"     add all the sql queries in double quotes
     * 
     * PASSWORD GENERATOR
     * NB: You can set the length of autogenerated password using an underscore after
     * the keywords auto or autogenerate    eg. auto_8,     auto_10     The length should not be more than 32. 
     * If the character after the underscore is not a number, the default (8) will apply
     * 
     * root password auto                   auto generate a password
     * root password autogenerate           auto generates a password
     * root password auto_12                auto generates password with the specified length
     * 
     * root password emmanuel               hashes the password emmanuel and returns it
     */
    public function jobs($request = null, $action = null, $info = null) {

        # only accessible via the cli
        if( !is_cli() && $info !== 'in_app_action') {
            return "Access denied!";
        }

        $config['system']['createsuperuser'] = "createsuperuser";
        $config['system']['setup'] = "setup";
        $config['system']['shutdown'] = "shutdown";
        $config['system']['restore'] = "restore";
        $config['files']['removetemp'] = "removetemp";

        // dbtable manage
        $config['changestatus'] = "changestatus";
        $config['selecttable'] = "selecttable";
        $config['password'] = "password";
        $config['sql'] = "sql";

        if( !isset($config[$request]) ) {
            return "Unknown request: {$request} {$action}.\n";
        }

        $method = null;
        if(isset($config[$request])) {
            if(!empty($action) && isset($config[$request][$action])) {
                $method = $config[$request][$action];
            } else {
                $method = $config[$request];
            }
        }

        if(is_array($method)) {

            if(empty($action)) {
                return "Action accepted however, the Method was not found.\n";
            }

            if(!method_exists($this, $action)) {
                return "Action accepted however, the Method {$method} has not yet been created.\n";
            }
        }

        return $this->{$method}($info, $action);
    }

    private function write_file($ifile, $content, $note) {
        $file = fopen($ifile, 'w');
        fwrite($file, json_encode($content));
        fclose($file);
        return "The system was successfully {$note} at ". date("Y-m-d h:i:sa")."\n";
    }

    private function setup() {
        
        try {

            $db = db_connect();

        } catch(\CodeIgniter\Database\Exceptions\DatabaseException $e) {
            print $e->getMessage();
        }
    }

    private function shutdown($info = null) {
        $tfile = APPPATH . "Files/System.json";

        $info = str_ireplace("_", " ", $info);
        if(is_file($tfile) && file_exists($tfile)) {
            $read = file_get_contents($tfile);
            $content = empty($read) ? [] : json_decode($read, true);
            $content['system'] = "unavailable";
            $content['downtime'] = date("Y-m-d H:i:s");

            $rdate = date("Y-m-d H:i:s", strtotime("{$info}"));
            $rdate = valid_date($rdate) ? $rdate : date("Y-m-d H:i:s", strtotime("1 hour"));

            $content['restored_at'] =  $rdate;
        }
        return $this->write_file($tfile, $content, "shutdown");
    }

    private function restore() {
        $tfile = APPPATH . "Files/System.json";
        if(is_file($tfile) && file_exists($tfile)) {
            $read = file_get_contents($tfile);
            $content = empty($read) ? [] : json_decode($read, true);
            $content['system'] = "available";
            $content['uptime'] = date("Y-m-d H:i:s");
        }
        return $this->write_file($tfile, $content, "restored");
    }

    private function selecttable($rule = null, $table = null) {

        try {
            
            if( empty($rule) ) {
                $rule = 1;
            }

            $db = db_connect();
            $rule = new RawSql($rule);
            $rule = str_ireplace("where", "", $rule);
            
            $result = $db->query("SELECT * FROM {$table} WHERE {$rule}");
            $data = empty($result) ? [] : $result->getResult();

            print_r($data);
        } catch(\CodeIgniter\Database\Exceptions\DatabaseException $e) {
            print $e->getMessage();
        }
    }

    private function removetemp() {

        # set the helper file
        helper(['filesystem', 'api']);

        # set the session file directory
        $files_list = [
            WRITEPATH . 'session' => [
               'minutes' => 15,
               'contains' => ['surveyMonkey']
            ],
            PUBLICPATH . 'uploads/tmp/avatar' => [
                'minutes' => 10
            ]
        ];

        # loop through the directors
        foreach($files_list as $dir => $data) {

            # remove all session files that was last modified the specified minutes ago
            foreach(get_dir_file_info($dir) as $file) {
                # clean date
                $date_modified = $file['date'];
                
                # set the time
                $current_time = time();

                # convert the time to normal
                $time_diff = round(($current_time - $date_modified) / 60);

                # only how the files that were lasb t modified 5 minutes ago
                if($time_diff > $data['minutes']) {
                    if(isset($data['contains'])) {
                        if(contains($file['name'], $data['contains'])) {
                            # delete the temp files
                            unlink($file['server_path']);
                        }
                    } else {
                        # delete the file
                        unlink($file['server_path']);
                    }
                }
            }

        }

        # print success message
        return date("l, F jS, Y h:ia") . " Temporary files deleted successfully.\n";
    }

}
?>