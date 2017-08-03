<?php
require_once('./Services/Logging/classes/class.ilLog.php');

/**
 * Class
 *
 * @author  Thomas Kolonko <thomas.kolonko@ilub.unibe.ch>
 * @version 1.0.0
 */
class UpdateRolesLog extends ilLog {

    const DEBUG_DEACTIVATED = 0;
    const DEBUG_LEVEL_1 = 1;
    const DEBUG_LEVEL_2 = 2;
    const DEBUG_LEVEL_3 = 3;
    const DEBUG_LEVEL_4 = 4;
    const UPDATE_LOG = 'updateRoles.log';
    /**
     * @var UpdateRolesLog
     */
    protected static $instance;
    /**
     * @var int
     */
    protected static $log_level = self::DEBUG_DEACTIVATED;


    /**
     * @param $log_level
     */
    public static function init($log_level = 4) {
        self::$log_level = $log_level;
    }


    /**
     * @param $log_level
     *
     * @return bool
     */
    public static function relevant($log_level) {
        return $log_level <= self::$log_level;
    }


    /**
     * @return UpdateRolesLog
     */
    public static function getInstance() {
        if (! isset(self::$instance)) {
            self::$instance = new self(ILIAS_LOG_DIR, self::UPDATE_LOG);
        }

        return self::$instance;
    }


    /**
     * @param      $a_msg
     * @param null $log_level
     */
    function write($a_msg, $log_level = null, $echo_output = false) {
        if ($echo_output) {
            echo $a_msg . "\n";
        }
        parent::write($a_msg);
    }


    public function writeTrace() {
        try {
            throw new Exception();
        } catch (Exception $e) {
            parent::write($e->getTraceAsString());
        }
    }


    /**
     * @return mixed
     */
    public function getLogDir() {
        return ILIAS_LOG_DIR;
    }


    /**
     * @return string
     */
    public function getLogFile() {
        return self::UPDATE_LOG;
    }


    /**
     * @return string
     */
    public static function getFullPath() {
        $log = self::getInstance();

        return $log->getLogDir() . '/' . $log->getLogFile();
    }


    /**
     * @return int
     */
    public static function getLogLevel() {
        return self::$log_level;
    }

}

?>
