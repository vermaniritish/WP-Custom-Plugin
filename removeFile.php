<?php
session_start();
if(isset($_SESSION['email_list_col']) && $_SESSION['email_list_col'])
unset($_SESSION['email_list_col']);
if(isset($_SESSION['verifier_file']) && $_SESSION['verifier_file'])
unset($_SESSION['verifier_file']);
if(isset($_SESSION['email_list_response']) && $_SESSION['email_list_response'])
unset($_SESSION['email_list_response']);
header("Location: " . $_SERVER['HTTP_REFERER']);