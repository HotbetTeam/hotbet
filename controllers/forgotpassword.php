<?php

class Forgotpassword extends Controller {

    function __construct() {
        parent::__construct();
    }

    public function index(){
    	$this->view->render('index/forgotpassword');
    }

}