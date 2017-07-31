<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once 'Services/Component/classes/class.ilComponent.php';
require_once('./include/inc.ilias_version.php');
/**
 * Class CustomInitialization
 *
 * @author  Tomasz Kolonko <thomas.kolonko@ilub.unibe.ch>
 */
class CustomInitialization {

    /**
     *
     */
    public static function initILIAS() {
        require_once('./Services/Context/classes/class.ilContext.php');
        require_once('./Services/Authentication/classes/class.ilAuthFactory.php');
        $il_context_auth = ilAuthFactory::CONTEXT_WEB;
        $_COOKIE['ilClientId'] = "ilias3_unibe";
        $_POST['username'] = "root";
        $_POST['password'] = "homer";


        ilAuthFactory::setContext($il_context_auth);
        require_once('./include/inc.header.php');

    }
}
?>