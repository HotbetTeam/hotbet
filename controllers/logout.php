<?php

class Logout extends Controller {

    function __construct() {
        parent::__construct();

    }

    public function index(){
    	if( empty($this->me) ){
			header('location:' . URL);
		}

        /*Session::init();
        Session::destroy();*/

        $url = !empty($_REQUEST['next'])
            ? $_REQUEST['next']
            : URL;

        Cookie::clear( COOKIE_KEY );
        header('location:' . $url);
    }

    public function admin() {
        
        $url = URL.'manage';
        if( empty($this->me) ){
            header('location:' . $url );
        }
        
        $url = !empty($_REQUEST['next'])
            ? $_REQUEST['next']
            : $url;

        Cookie::clear( COOKIE_KEY_ADMIN );
        header('location:' . $url);
    }

    public function agent() {
        $url = URL.'agent';

        if( $this->format == 'json' ){
            $this->view->render('agent/dialog/confirm_logout');
            exit;
        }

        if( empty($this->me) ){
            header('location:' . $url );
        }

        $url = !empty($_REQUEST['next'])
            ? $_REQUEST['next']
            : $url;

        Cookie::clear( COOKIE_KEY_AGENT );
        header('location:' . $url);

    }

}