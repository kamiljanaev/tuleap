<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\password\HaveIBeenPwned\PwnedPasswordChecker;
use Tuleap\password\HaveIBeenPwned\PwnedPasswordRangeRetriever;
use Tuleap\password\PasswordCompromiseValidator;

/**
* PasswordStrategy
*/
class PasswordStrategy {
    
    var $validators;
    var $errors;
    
    /**
    * Constructor
    */
    function PasswordStrategy() {
        $this->validators = array();
        $this->errors     = array();

        if (! ForgeConfig::get('reject_compromised_password')) {
            $pwned_password_range_retriever = new PwnedPasswordRangeRetriever(new Http_Client(), new BackendLogger());
            $pwned_password_checker         = new PwnedPasswordChecker($pwned_password_range_retriever);
            $password_compromise_validator  = new PasswordCompromiseValidator($pwned_password_checker);
            $this->add($password_compromise_validator);
        }
    }
    
    /**
    * validate
    * 
    * validate a password with the help of validators
    *
    * @param  pwd  
    */
    function validate($pwd) {
        $valid = true;
        foreach($this->validators as $key => $nop) {
            if (!$this->validators[$key]->validate($pwd)) {
                $valid = false;
                $this->errors[$key] = $this->validators[$key]->description();
            }
        }
        return $valid;
    }
    
    /**
    * add
    * 
    * @param  v  
    */
    function add(&$v) {
        $this->validators[] =& $v;
    }
    
}
?>
