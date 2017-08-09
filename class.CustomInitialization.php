<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once('Services/Component/classes/class.ilComponent.php');
require_once('./include/inc.ilias_version.php');
require_once("./Services/Context/classes/class.ilContext.php");
/**
 * Class CustomInitialization
 *
 * @author  Tomasz Kolonko <thomas.kolonko@ilub.unibe.ch>
 */
class CustomInitialization extends ilContext{

    /**
     *

    public static function initILIAS() {

        require_once('./Services/Context/classes/class.ilContext.php');
        require_once('./Services/Authentication/classes/class.ilAuthFactory.php');
        $il_context_auth = ilAuthFactory::CONTEXT_WEB;

//        $_COOKIE['ilClientId'] = "ilias3_unibe";
//        $_POST['username'] = "root";
//        $_POST['password'] = "homer";
        $_COOKIE['ilClientId'] = $_SERVER['argv'][3];
        $_POST['username'] = $_SERVER['argv'][1];
        $_POST['password'] = $_SERVER['argv'][2];


        ilAuthFactory::setContext($il_context_auth);
        require_once('./include/inc.header.php');
    }
     * /

    /**
     * Initialization of a existing context (CONTEXT_WEB_ACCESS_CHECK)
     */
    public static function initILIAS() {


        define(CLIENT_ID, "ilias3_unibe");


        parent::init(ilContext::CONTEXT_CRON);
        require_once('./include/inc.header.php');
    }
}
?>