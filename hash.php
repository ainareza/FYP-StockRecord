<?php
//this is used to make a new account for store owner
/*user fill in preferred password in "password" to get hashed
  password, then copy the the output and paste it in phpMyAdmin
  under user table -> password*/
echo password_hash("password", PASSWORD_DEFAULT); 
?>