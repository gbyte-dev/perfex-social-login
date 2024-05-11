<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

/**
 * Database Class
 *
 * To handles all SAP Settings
 * 
 * @filesource https://github.com/bennettstone/simple-mysqli
 * @package Social Auto Poster
 * @since 1.0.0
 */

class Sap_Database {

    private $link = null;
    public $filter;
    static $inst = null;
    public static $counter = 0;

    /**
     * Allow the class to send admins a message alerting them to errors
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function log_db_errors($error, $query) {

        $message = '<p>Error at ' . date('Y-m-d H:i:s') . ':</p>';
        $message .= '<p>Query: ' . htmlentities($query) . '<br />';
        $message .= 'Error: ' . $error;
        $message .= '</p>';

        if (defined('SEND_ERRORS_TO')) {

            $headers = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
            $headers .= 'To: Admin <' . SEND_ERRORS_TO . '>' . "\r\n";
            $headers .= 'From: Yoursite <system@' . $_SERVER['SERVER_NAME'] . '.com>' . "\r\n";
            mail(SEND_ERRORS_TO, 'Database Error', $message, $headers);
        } else {
            trigger_error($message);
        }

        if (!defined('DISPLAY_DEBUG') || ( defined('DISPLAY_DEBUG') && DISPLAY_DEBUG )) {

            echo $message;
        }
    }

    public function __construct() {

        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');
        mysqli_report(MYSQLI_REPORT_STRICT);

        try {
            $this->link = new mysqli(SAP_DB_HOST, SAP_DB_USER, SAP_DB_PASS, SAP_DB_NAME);
        } catch (Exception $e) {
                echo '<pre>';
                print_r($e);
            die('Unable to connect to database');
        }
    }

    public function __destruct() {

        if ($this->link) {
            $this->disconnect();
        }
    }

    /**
     * Senitize user data
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function filter($data) {

        if (!is_array($data)) {
            $data  = strip_tags($data);
            $data  = trim(htmlentities($data, ENT_QUOTES, 'UTF-8', false));
        } else {
            //Self call function to sanitize array data
            $data = array_map(array($this, 'filter'), $data);
        }
        return $data;
    }

    /**
     * Extra function to filter when only mysqli_real_escape_string is needed
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function escape($data) {

        if (!is_array($data)) {
            $data = $this->link->real_escape_string($data);
        } else {
            //Self call function to sanitize array data
            $data = array_map(array($this, 'escape'), $data);
        }

        return $data;
    }

    /**
     * Normalize sanitized data for display (reverse $database->filter cleaning)
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function clean($data) {

        $data = stripslashes($data);
        $data = html_entity_decode($data, ENT_QUOTES, 'UTF-8');
        $data = urldecode($data);

        return $data;
    }

    /**
     * Determine if common non-encapsulated fields are being used
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function db_common($value = '') {

        if (is_array($value)) {

            foreach ($value as $v) {

                if (preg_match('/AES_DECRYPT/i', $v) || preg_match('/AES_ENCRYPT/i', $v) || preg_match('/now()/i', $v)) {

                    return true;
                } else {
                    return false;
                }
            }
        } else {
            if (preg_match('/AES_DECRYPT/i', $value) || preg_match('/AES_ENCRYPT/i', $value) || preg_match('/now()/i', $value)) {
                return true;
            }
        }
    }

    /**
     * Perform queries
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function query($query) {

        $full_query = $this->link->query($query);

        if ($this->link->error) {
            $this->log_db_errors($this->link->error, $query);
            return false;
        } else {
            return true;
        }
    }

    /**
     * Determine database table exists
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function table_exists($name) {

        self::$counter++;
        $check = $this->link->query("SELECT 1 FROM $name");

        if ($check !== false) {

            if ($check->num_rows > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Count number of rows found matching a specific query
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function num_rows($query) {

        self::$counter++;
        $num_rows = $this->link->query($query);

        if ($this->link->error) {
            $this->log_db_errors($this->link->error, $query);
            return $this->link->error;
        } else {
            return $num_rows->num_rows;
        }
    }

    /**
     * Run check to see if value exists, returns true or false
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function exists($table = '', $check_val = '', $params = array()) {

        self::$counter++;

        if (empty($table) || empty($check_val) || empty($params)) {
            return false;
        }

        $check = array();
        foreach ($params as $field => $value) {

            if (!empty($field) && !empty($value)) {
                //Check for frequently used mysql commands and prevent encapsulation of them
                if ($this->db_common($value)) {
                    $check[] = "$field = $value";
                } else {
                    $check[] = "$field = '$value'";
                }
            }
        }

        $check = implode(' AND ', $check);
        $rs_check = "SELECT $check_val FROM " . $table . " WHERE $check";
        $number = $this->num_rows($rs_check);

        if ($number === 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Return specific row based on db query
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function get_row($query, $object = false) {

        self::$counter++;
        $row = $this->link->query($query);

        if ($this->link->error) {
            $this->log_db_errors($this->link->error, $query);
            return false;
        } else {
            $r = (!$object ) ? $row->fetch_row() : $row->fetch_object();
            return $r;
        }
    }

    /**
     * Perform query to retrieve array of associated results
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function get_results($query, $array = false) {

        self::$counter++;
        //Overwrite the $row var to null
        $row = null;

        $results = $this->link->query($query);

        if ($this->link->error) {
            $this->log_db_errors($this->link->error, $query);
            return false;
        } else {

            $row = array();
            while ($r = ( $array ) ? $results->fetch_assoc() : $results->fetch_object()) {
                $row[] = $r;
            }

            return $row;
        }
    }

    /**
     * Insert data into table
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function insert($table, $variables = array()) {

        self::$counter++;

        //Make sure the array isn't empty
        if (empty($variables)) {
            return false;
        }

        $sql = "INSERT INTO " . $table;
        $fields = array();
        $values = array();

        foreach ($variables as $field => $value) {

            $fields[] = $field;
            if( is_null($value) ){
                $values[] = 'NULL';
            } else{
                $values[] = "'" . $value . "'";
            }
        }

        $fields = ' (' . implode(', ', $fields) . ')';
        $values = '(' . implode(', ', $values) . ')';

        $sql .= $fields . ' VALUES ' . $values;

        $query = $this->link->query($sql);
         
        if ($this->link->error) {
            //return false; 
            $this->log_db_errors($this->link->error, $sql);
            return false;
        } else {
            return true;
        }
    }

    /**
     * Insert data KNOWN TO BE SECURE into database table
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function insert_safe($table, $variables = array()) {

        self::$counter++;
        //Make sure the array isn't empty
        if (empty($variables)) {
            return false;
        }

        $sql = "INSERT INTO " . $table;
        $fields = array();
        $values = array();

        foreach ($variables as $field => $value) {
            $fields[] = $this->filter($field);
            //Check for frequently used mysql commands and prevent encapsulation of them
            $values[] = $value;
        }

        $fields = ' (' . implode(', ', $fields) . ')';
        $values = '(' . implode(', ', $values) . ')';

        $sql .= $fields . ' VALUES ' . $values;
        $query = $this->link->query($sql);

        if ($this->link->error) {
            $this->log_db_errors($this->link->error, $sql);
            return false;
        } else {
            return true;
        }
    }

    /**
     * Insert multiple records in a single query into a database table
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function insert_multi($table, $columns = array(), $records = array()) {

        self::$counter++;

        //Make sure the arrays aren't empty
        if (empty($columns) || empty($records)) {
            return false;
        }

        //Count the number of fields to ensure insertion statements do not exceed the same num
        $number_columns = count($columns);
        //Start a counter for the rows
        $added = 0;
        //Start the query
        $sql = "INSERT INTO " . $table;
        $fields = array();

        //Loop through the columns for insertion preparation
        foreach ($columns as $field) {
            $fields[] = '`' . $field . '`';
        }

        $fields = ' (' . implode(', ', $fields) . ')';
        //Loop through the records to insert
        $values = array();

        foreach ($records as $record) {

            //Only add a record if the values match the number of columns
            if (count($record) == $number_columns) {
                $values[] = '(\'' . implode('\', \'', array_values($record)) . '\')';
                $added++;
            }
        }

        $values = implode(', ', $values);
        $sql .= $fields . ' VALUES ' . $values;
        $query = $this->link->query($sql);

        if ($this->link->error) {
            $this->log_db_errors($this->link->error, $sql);
            return false;
        } else {
            return $added;
        }
    }

    /**
     * Update records in database table
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function update($table, $variables = array(), $where = array(), $limit = '') {

        self::$counter++;

        //Make sure the required data is passed before continuing
        //This does not include the $where variable as (though infrequently)
        //queries are designated to update entire tables
        if (empty($variables)) {
            return false;
        }

        $sql = "UPDATE " . $table . " SET ";

        foreach ($variables as $field => $value) {
            $updates[] = "`$field` = '$value'";
        }

        $sql .= implode(', ', $updates);

        //Add the $where clauses as needed
        if (!empty($where)) {

            foreach ($where as $field => $value) {
                $value = $value;
                $clause[] = "$field = '$value'";
            }

            $sql .= ' WHERE ' . implode(' AND ', $clause);
        }

        if (!empty($limit)) {
            $sql .= ' LIMIT ' . $limit;
        }

        $query = $this->link->query($sql);

        if ($this->link->error) {
            $this->log_db_errors($this->link->error, $sql);
            return false;
        } else {
            return true;
        }
    }

    /**
     * Delete data from table
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function delete($table, $where = array(), $limit = '') {

        self::$counter++;

        //Delete clauses require a where param, otherwise use "truncate"
        if (empty($where)) {
            return false;
        }

        $sql = "DELETE FROM " . $table;

        foreach ($where as $field => $value) {
            $value = $value;
            $clause[] = "$field = '$value'";
        }

        $sql .= " WHERE " . implode(' AND ', $clause);

        if (!empty($limit)) {
            $sql .= " LIMIT " . $limit;
        }

        $query = $this->link->query($sql);

        if ($this->link->error) {
            //return false; //
            $this->log_db_errors($this->link->error, $sql);
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get last auto-incremented id from database
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function lastid() {
        self::$counter++;
        return $this->link->insert_id;
    }

    /**
     * Return the number of rows affected by a given query
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function affected() {
        return $this->link->affected_rows;
    }

    /**
     * Get number of fields
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function num_fields($query) {

        self::$counter++;

        $query = $this->link->query($query);
        $fields = $query->field_count;

        return $fields;
    }

    /**
     * Get field names associated with a table
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function list_fields($query) {

        self::$counter++;

        $query = $this->link->query($query);
        $listed_fields = $query->fetch_fields();

        return $listed_fields;
    }

    /**
     * Truncate Entire table
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function truncate($tables = array()) {

        if (!empty($tables)) {
            $truncated = 0;

            foreach ($tables as $table) {

                $truncate = "TRUNCATE TABLE `" . trim($table) . "`";
                $this->link->query($truncate);

                if (!$this->link->error) {
                    $truncated++;
                    self::$counter++;
                }
            }

            return $truncated;
        }
    }

    /**
     * Output result of quries
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function display($variable, $echo = true) {

        $out = '';
        if (!is_array($variable)) {
            $out .= $variable;
        } else {
            $out .= '<pre>';
            $out .= print_r($variable, TRUE);
            $out .= '</pre>';
        }

        if ($echo === true) {
            echo $out;
        } else {
            return $out;
        }
    }

    /**
     * Output the total number of queries
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function total_queries() {
        return self::$counter;
    }

    /**
     * Singleton function
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    static function getInstance() {

        if (self::$inst == null) {
            self::$inst = new Sap_Database();
        }

        return self::$inst;
    }

    /**
     * Disconnect from DB
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function disconnect() {
        $this->link->close();
    }

}

//end class DB