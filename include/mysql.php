<?php
require_once(INCLUDE_DIR.'class.sys.php');

    $__db = null;

    function db_connect($dbhost, $dbuser, $dbpass, $dbname = "") {
        global $__db;

        if(!strlen($dbuser) || !strlen($dbpass) || !strlen($dbhost))
      	    return NULL;

        $__db = @mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
        if($__db) {
            mysqli_set_charset($__db, 'utf8');
        }
        return $__db;
    }

    function db_close(){
        global $__db;
        return @mysqli_close($__db);
    }

    function db_select_database($dbname) {
        global $__db;
        return @mysqli_select_db($__db, $dbname);
    }

    function db_version(){
        $row = mysqli_fetch_row(db_query('SELECT VERSION()'));
        preg_match('/(\d{1,2}\.\d{1,2}\.\d{1,2})/', $row[0], $matches);
        return $matches[1];
    }

	function db_query($query, $database="", $conn=""){
        global $cfg, $__db;

        $link = $conn ? $conn : $__db;

        if($database) {
            mysqli_select_db($link, $database);
        }
        $response = @mysqli_query($link, $query);

        if(!$response) {
            $alert='['.$query.']'."\n\n".db_error();
            Sys::log(LOG_ALERT,'DB Error #'.db_errno(),$alert,($cfg && $cfg->alertONSQLError()));
        }
        return $response;
	}

	function db_squery($query){

		$args  = func_get_args();
  		$query = array_shift($args);
  		$query = str_replace("?", "%s", $query);
  		$args  = array_map('db_real_escape', $args);
  		array_unshift($args,$query);
  		$query = call_user_func_array('sprintf',$args);
		return db_query($query);
	}

	function db_count($query){
		list($count)=db_fetch_row(db_query($query));
		return $count;
	}

	function db_fetch_array($result, $mode=false) {
   	    return ($result)?mysqli_fetch_array($result,($mode)?$mode:MYSQLI_ASSOC):null;
  	}

    function db_fetch_row($result) {
        return ($result)?mysqli_fetch_row($result):NULL;
    }

    function db_fetch_fields($result) {
        return mysqli_fetch_field($result);
    }

    function db_assoc_array($result, $mode=false){
        $results = array();
	    if($result && db_num_rows($result)){
      	    while ($row=db_fetch_array($result,$mode))
         	    $results[]=$row;
        }
        return $results;
    }

    function db_num_rows($result) {
   	    return ($result)?mysqli_num_rows($result):0;
    }

	function db_affected_rows() {
        global $__db;
        return mysqli_affected_rows($__db);
    }

  	function db_data_seek($result, $row_number) {
   	    return mysqli_data_seek($result, $row_number);
  	}

  	function db_data_reset($result){
   	    return mysqli_data_seek($result,0);
  	}

  	function db_insert_id() {
        global $__db;
   	    return mysqli_insert_id($__db);
  	}

	function db_free_result($result) {
   	    return mysqli_free_result($result);
  	}

	function db_output($param) {
        return $param;
  	}

    function db_real_escape($val, $quote=false){
        global $__db;

        $val=mysqli_real_escape_string($__db, $val ?? '');

        return ($quote)?"'$val'":$val;
    }

    function db_input($param, $quote=true) {

        if($param !== null && $param !== '' && preg_match("/^\d+(\.\d+)?$/", (string)$param))
            return intval($param) == $param ? (int)$param : (float)$param;

        if($param && is_array($param)){
            foreach($param as $key => $value) {
                $param[$key] = db_input($value, $quote);
            }
            return $param;
        }
        return db_real_escape($param, $quote);
    }

	function db_error(){
        global $__db;
   	    return mysqli_error($__db);
	}

    function db_errno(){
        global $__db;
        return mysqli_errno($__db);
    }
?>
