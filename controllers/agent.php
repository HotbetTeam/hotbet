<?php

class Agent extends Controller {

    function __construct() {
        parent::__construct();


        $this->view->title = "Agent - ". PAGE_TITLE;
        $this->view->theme = 'manage';
        $this->view->currentPage = 'agent';
        $this->view->elem('body')->addClass('hidden-tobar');
    }

    public function index() {
        
        $this->view->theme = 'default';
        $this->view->elem('body')->addClass('col-x');
        $this->view->render('agent/index');
    }

    public function register() {

        if( Cookie::get( COOKIE_KEY_AGENT ) ){
            header('Location: ' . URL . 'agent/manage');
        }

        if (!empty($_POST)) {

            $dataPost = $_POST;
            try {
                $form = new Form();

                $form   ->post('agent_name')->val('is_empty')
                        ->post('agent_email')->val('is_empty')
                        ->post('agent_password')->val('password')->val('is_empty');

                $form->submit();
                $dataPost = $form->fetch();

                // ตรวจสอบอีเมล์
                if( filter_var($dataPost['agent_email'], FILTER_VALIDATE_EMAIL) ){

                    $ext = explode("@", $dataPost['agent_email']);

                    if( !in_array($ext[1], array('gmail.com','hotmail.com')) ){
                        $arr['error']['agent_email'] = "โปรดป้อนอีเมลที่ถูกต้อง!";
                    }else if ( $this->model->query('member')->is_user( $dataPost['agent_email'] ) ){
                        $arr['error']['agent_email'] = "อีเมลนี้เคยลงทะเบียนไว้ก่อนแล้ว!";
                    }

                }elseif( is_numeric($dataPost['agent_email']) ){

                    if ( !@eregi("^((\([0-9]{3}\) ?)|([0-9]{3}))?[0-9]{3}[0-9]{4}$", $dataPost['agent_email']) ){
                        $arr['error']['agent_email'] = "ไม่ใช่เบอร์โทรศัพท์ที่ถูกต้อง (ตัวอย่างที่ถูกต้อง 0843635952)";
                    }
                    else if ( $this->model->query('member')->is_user( $dataPost['agent_email'] ) ){
                        $arr['error']['agent_email'] = "หมายเลขโทรศัพท์นี้เคยลงทะเบียนไว้ก่อนแล้ว!";
                    }else{
                        $dataPost['agent_tel'] = $dataPost['agent_email'];
                        unset($dataPost['agent_email']);
                    }

                }else{
                    $arr['error']['agent_email'] = "โปรดป้อนอีเมลที่ถูกต้อง";
                }

                if( empty($arr['error']) ){
                    $this->model->query('agent')->insert( $dataPost );

                    $arr['message'] = "ทะเบียนเรียบร้อยแล้ว";
                    $arr['url'] = URL . 'agent/login';
                    
                    // Cookie::set( COOKIE_KEY , $post['m_id'], time()+86400);
                    // header("location:".$arr['url']);
                }


            } catch (Exception $e) {
                $arr['error'] = $this->_getError( $e->getMessage() );
            }

            if( $this->format=='json' ){
                echo json_encode($arr);
                exit;
            }
            else if( !empty($arr['url']) ) {
                header('location:'. $arr['url']);
            }

        }

        if( !empty($arr['error']) ){
            // print_r($arr['error']); die;
            $this->view->error = $arr['error'];
        }

        if (!empty($dataPost)) {
            $this->view->post = $dataPost;
        }

        $this->view->css('login');
        $this->view->theme = 'login';
        $this->view->render('agent/Layouts/register');
    }

    public function regcomplete() {
        $this->view->currentPage = 'agent';
        $this->view->elem('body')->addClass('col-x');
        // $this->view->js('casino');
        $this->view->render('agent/regconplate');
    }

    public function login() {

        if( Cookie::get( COOKIE_KEY_AGENT ) ){
            header('Location: ' . URL . 'agent/manage');
        }

        if (!empty($_POST)) {

            try {
                $form = new Form();

                $form   ->post('email')->val('is_empty')
                        ->post('pass')->val('is_empty');

                $form->submit();
                $post = $form->fetch();

                $login = $this->model->query('agent')->login($post['email'], $post['pass']);
                if (!empty($login)) {

                    if (Cookie::get(COOKIE_KEY_ADMIN)) {
                        Cookie::clear( COOKIE_KEY_ADMIN );
                    }

                    if(Cookie::get(COOKIE_KEY)){
                        Cookie::clear( COOKIE_KEY );
                    } 

                    Cookie::set(COOKIE_KEY_AGENT, $login, time() + (86400 * 30)); // 30 วัน  
                    $arr['message'] = "เข้าสู่ระบบเรียบร้อยแล้ว";
                    $arr['url'] = URL . 'agent/member';
                } else {

                    if (!$this->model->query('agent')->duplicate($post['email'])) {
                        $arr['error']['email'] = 'ชื่อผู้ใช้ไม่ถูกต้อง';
                    } else {
                        $arr['error']['pass'] = 'รหัสผ่านไม่ถูกต้อง';
                    }

                }

            } catch (Exception $e) {
                $arr['error'] = $this->_getError( $e->getMessage() );
            }

            if( $this->format=='json' ){
                echo json_encode($arr);
                exit;
            }
            else if( !empty($arr['url']) ) {
                header('location:'. $arr['url']);
            }
        }

        $this->view->css('login');
        $this->view->theme = 'login';
        $this->view->render('agent/Layouts/login');
    }

    public function manage() {

        $this->member();
    }
    public function redirect($id) {
        Cookie::set('Agentredirect', $id, time() + (86400 * 1)); // 1 วัน   
        header('Location: ' . URL . 'register');
    }



    public function banner() {
        if( empty($this->me['agent_id']) ) $this->error();
        
        $this->view->currentPage = 'banner';
        $this->view->render('agent/banner');
    }
    // join member
    public function member( $section='', $id = null ) {
        if( empty($this->me['agent_id']) ) $this->error();
        $this->view->currentPage = 'member';

        if( $section=='add' ){

            if( !empty($_POST) ){
                try {
                    $form = new Form();
                    $form   ->post('m_name')->val('is_empty')
                            ->post('m_email')
                            ->post('m_phone_number')

                            ->post('m_username')->val('username')
                            ->post('m_password')->val('password')

                            ->post('m_note');

                    $form->submit();
                    $dataPost = $form->fetch();

                    // ตรวจสอบอีเมล์
                    if( !empty($dataPost['m_email']) ){

                        $err = $form->verify('email', $dataPost['m_email']);

                        if( !empty($err) ){
                            $arr['error']['m_email'] = $err;
                        } else if( $this->model->query('member')->is_user( $dataPost['m_email'] ) ){
                            $arr['error']['m_email'] = "ไม่สามารถใช้อีเมล์นี้ได้ (อีเมลนี้ถูกใช้ไปแล้ว)";
                        }
                    }

                    // ตรวจสอบg phone
                    if( !empty($dataPost['m_phone_number']) ){

                        $err = $form->verify('phone_number', $dataPost['m_phone_number']);

                        if( !empty($err) ){
                            $arr['error']['m_phone_number'] = $err;
                        } else if( $this->model->query('member')->is_user( $dataPost['m_phone_number'] ) ){
                            $arr['error']['m_phone_number'] = "ไม่สามารถใช้เบอร์โทรศัพท์นี้ได้ (เบอร์โทรศัพท์นี้ถูกใช้ไปแล้ว)";
                        }
                    }

                    // ตรวจสอบชื่อผู้เข้าใช้
                    if( $this->model->query('member')->is_user( $dataPost['m_username'] ) ){
                        $arr['error']['m_username'] = "ไม่สามารถใช้ชื่อผู้เข้าใช้นี้ได้ (ชื่อผู้เข้าใช้นี้ถูกใช้ไปแล้ว)";
                    }
                    
                    if( empty($arr['error']) ){

                        $dataPost['m_agent_id'] = $this->me['agent_id'];
                        // insert 
                        $this->model->query('member')->insert( $dataPost );
                        $id = $dataPost['m_id'];

                        $this->model->query('agent')->joinMember($this->me['agent_id'], $id );

                        $arr['message'] = "เพิ่มสมาชิกเรียบร้อย";
                        $arr['url'] = URL.'agent/member/'.$id;
                    }

                } catch (Exception $e) {
                    $arr['error'] = $this->_getError($e->getMessage());
                }

                echo json_encode($arr);
            }
            else{
                $this->view->render('agent/member/dialog/add_form');
            }
            exit;
        } elseif ( $section=='edit' ) {

            $id = isset($_REQUEST['id']) ? $_REQUEST['id']: $id;
            $item = $this->model->query('member')->get( $id );
            if( empty($item)) $this->error();

            if( !empty($_POST) ){
                try {
                    $form = new Form();
                    $form   ->post('m_name')->val('name')->val('maxlength', 20)->val('is_empty')
                            ->post('m_email')
                            ->post('m_phone_number')

                            ->post('m_username')->val('username')
                            ->post('m_note');

                    $form->submit();
                    $dataPost = $form->fetch();

                    // ตรวจสอบอีเมล์
                    if( !empty($dataPost['m_email']) ){

                        $err = $form->verify('email', $dataPost['m_email']);

                        if( !empty($err) ){
                            $arr['error']['m_email'] = $err;
                        } else if( $item['email']!=$dataPost['m_email'] && $this->model->query('member')->is_user( $dataPost['m_email'] ) ){
                            $arr['error']['m_email'] = "ไม่สามารถใช้อีเมล์นี้ได้ (อีเมลนี้ถูกใช้ไปแล้ว)";
                        }
                    }

                    // ตรวจสอบg phone
                    if( !empty($dataPost['m_phone_number']) ){

                        $err = $form->verify('phone_number', $dataPost['m_phone_number']);

                        if( !empty($err) ){
                            $arr['error']['m_phone_number'] = $err;
                        } else if( $item['phone_number']!=$dataPost['m_phone_number'] && $this->model->query('member')->is_user( $dataPost['m_phone_number'] ) ){
                            $arr['error']['m_phone_number'] = "ไม่สามารถใช้เบอร์โทรศัพท์นี้ได้ (เบอร์โทรศัพท์นี้ถูกใช้ไปแล้ว)";
                        }
                    }

                    // ตรวจสอบชื่อผู้เข้าใช้
                    if( $item['username']!=$dataPost['m_username'] && $this->model->query('member')->is_user( $dataPost['m_username'] ) ){
                        $arr['error']['m_username'] = "ไม่สามารถใช้ชื่อผู้เข้าใช้นี้ได้ (ชื่อผู้เข้าใช้นี้ถูกใช้ไปแล้ว)";
                    }
                    
                    if( empty($arr['error']) ){

                        // update 
                        $this->model->query('member')->update($id, $dataPost);
                        $arr['message'] = "แก้ไขข้อมูลเรียบร้อย";
                        $arr['url'] = URL.'agent/member/'.$id;
                    }

                } catch (Exception $e) {
                    $arr['error'] = $this->_getError($e->getMessage());
                }

                echo json_encode($arr);
            }
            else{
                $this->view->item = $item;
                $this->view->render('agent/member/dialog/edit_form');
            }

            exit;
        } elseif ( $section=='change_password' ) {

            $id = isset($_REQUEST['id']) ? $_REQUEST['id']: $id;
            $item = $this->model->query('member')->get( $id );
            if( empty($item)) $this->error();

            if( !empty($_POST) ){
                try {
                    $form = new Form();
                    $form   ->post('password_new')->val('password')
                            ->post('password_confirm');

                    $form->submit();
                    $dataPost = $form->fetch();

                    if( $dataPost['password_new']!=$dataPost['password_confirm'] ){
                        $arr['error']['password_confirm'] = 'รหัสผ่านไม่ตรงกัน';
                    }

                    if( empty($arr['error']) ){

                        $this->model->query('member')->update($item['m_id'], array( 'm_password' => $dataPost['password_new']) );

                        $arr['message'] = "แก้ไขข้อมูลเรียบร้อย";
                        // $arr['url'] = 'refresh';
                    }


                } catch (Exception $e) {
                    $arr['error'] = $this->_getError($e->getMessage());
                }

                echo json_encode($arr);
            }
            else{
                $this->view->item = $item;
                $this->view->render('agent/member/dialog/change_password_form');
            }

            exit;
        } elseif ( $section=='del' ) {
            $id = isset($_REQUEST['id']) ? $_REQUEST['id']: $id;
            $item = $this->model->query('member')->get( $id );
            if( empty($item)) $this->error();

            if( !empty($_POST) ){
                $this->model->query('member')->delete( $id );
                
                // 
                if( !empty($item['agent_id']) ){
                    $this->model->query('agent')->delMember($item['agent_id'], $id);
                }

                $arr['message'] = "ลบเรียบร้อย";
                $arr['url'] = URL.'agent/member';
                echo json_encode($arr);
            }
            else{
                $this->view->item = $item;
                $this->view->render('agent/member/dialog/del_form');
            }
            exit;
        } elseif ( $section=='live_update' ) {

            $post['field']= isset($_REQUEST['field']) ? $_REQUEST['field']: null;
            $post['value'] = isset($_REQUEST['val']) ? $_REQUEST['val']: null;

            $id = isset($_REQUEST['id']) ? $_REQUEST['id']: $id;
            $item = $this->model->query('member')->get( $id );
            if( empty($item) || empty($post['field']) ) $this->error();

            $form = new Form();
            $arr['error_message'] = $form->check( array( 'email', 'phone_number', 'username', 'password'), $post['field'], $post['value'] );

            
            if( $post['field']=='email' ) {

                if( $this->model->query('member')->is_user($post['value']) && $post['value']!=$item['email'] ){
                    $arr['error_message'] = 'อีเมลนี้เคยลงทะเบียนไว้ก่อนแล้ว';
                }
            } else if( $post['field']=='phone_number' ) {
     
                if( $this->model->query('member')->is_user($post['value']) && $post['value']!=$item['phone_number'] ){
                    $arr['error_message'] = 'หมายเลขโทรศัพท์นี้เคยลงทะเบียนไว้ก่อนแล้ว';
                }

            } else if( $post['field']=='username' ) {
                if( $this->model->query('member')->is_user($post['value']) && $post['value']!=$item['user'] ){
                    $arr['error_message'] = 'ไม่สามารถใช้ชื่อผู้เข้าใช้นี้ได้ (ชื่อผู้เข้าใช้นี้เคยลงทะเบียนไว้ก่อนแล้ว)';
                }
            } else if( $post['field']=="password" && strlen($post['value'])<6 ){
                $arr['error_message'] = "รหัสผ่านต้องมีความยาว 6 ตัวขึ้นไป";
                
            } else if( $post['field']=='name' && $post['value']=="" ){
                $arr['error_message'] = "กรอกชื่อ-สกุล";
            }

            if( empty($arr['error_message']) ){

                // seve 
                $dataPost[ "m_".$post['field'] ] = $post['value'];

                $this->model->query('member')->update( $id , $dataPost);
            }

            $arr['error'] = !empty($arr['error_message']);

            echo json_encode( $arr );

            exit;
        } else if( is_numeric($section) ){
            $item = $this->model->query('member')->get( $section );
            if( empty($item)) $this->error();
            // print_r($item); die;

            $this->view->item = $item;
            $this->view->render('agent/member/profile/display');
            exit;
        }


        // 
        $this->view->results = $this->model->query('agent')->member( Cookie::get( COOKIE_KEY_AGENT ) );
        if( $this->format=='json' ){
            $this->view->render('agent/member/lists/json');
        }
        else{
            $this->view->render('agent/member/lists/display');
        }
    }
    public function settings( $section = 'profile' ) {


        if( empty($this->me['agent_id']) || !in_array($section, array('profile', 'password')) ) $this->error();
        
        $this->view->currentPage = 'settings';
        $this->view->section = $section;
        $this->view->elem('body')->addClass('hidden-tobar settings-page');
        $this->view->render('agent/settings/display');
    }
    public function change_password() {

        if( empty($this->me['agent_id']) ) $this->error();

        $data = $_POST;

        if( $this->me['agent_password'] != $data['password_old'] ){
            $arr['error']['password_old'] = "รหัสของคุณไม่ถูกต้อง";
        }
        else if( $this->me['agent_password'] == $data['password_new'] ){
            $arr['error']['password_new'] = "รหัสใหม่จะเมือนกับรหัสเก่าไม่ได้";
        }
        else if( $data['password_confirm'] != $data['password_new'] ){
            $arr['error']['password_confirm'] = "คุณต้องใส่รหัสผ่านที่เหมือนกันสองครั้งเพื่อเป็นการยืนยัน";
        }
        else if( strlen($data['password_new']) < 4 ){
            $arr['error']['password_confirm'] = "ต้องมีความยาว 4 ตัวขึ้นไป";
        }

        if( empty($arr['error']) ){

            $this->model->query('agent')->update($this->me['agent_id'], array('agent_password' => $data['password_new'] ));
            $arr['url'] = 'refresh';
            $arr['message'] = 'บันทึกข้อมูลเรียบร้อย';
        }

        echo json_encode($arr);
    }


    // admin manage
    public function add() {

        $this->view->render('agent/dialog/add_or_edit_form');
    }
    public function edit($id = null) {
        if (empty($id))
            $this->_error();

        $item = $this->model->query('agent')->get($id);
        if (empty($item))
            $this->error();

        $this->view->item = $item;
        $this->view->render('agent/dialog/add_or_edit_form');
    }
    public function update($id = null) {

        if (empty($_POST))
            $this->_error();

        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : $id;
        if (!empty($id)) {
            $item = $this->model->query('agent')->get($id);
            if (empty($item))
                $this->error();
        }

        try {
            $form = new Form();
            $form   ->post('agent_email')->val('email')
                    ->post('agent_name')
                    ->post('agent_tel')->val('phone_number');

            $form->submit();
            $data = $form->fetch();

            // ตรวจสอบ email ซ้ำ
            if (!empty($item)) {
                if ($item['agent_email'] != $data['agent_email'] && $this->model->query('agent')->duplicate($data['agent_email']))
                    $arr['error']['agent_email'] = "อีเมล์ไม่สามารถใช้ได้ (อีเมล์นี้ถูกใช้ไปแล้ว)";
            }
            else if ($this->model->query('agent')->duplicate($data['agent_email'])) {
                $arr['error']['agent_email'] = "อีเมล์ไม่สามารถใช้ได้ (อีเมล์นี้ถูกใช้ไปแล้ว)";
            }

            // ตรวจสอบเบอร์โทร ซ้ำ
            if (!empty($item)) {
                if ($item['agent_tel'] != $data['agent_tel'] && $this->model->query('agent')->duplicate($data['agent_tel']))
                    $arr['error']['agent_tel'] = "ไม่สามารถใช้เบอร์โทรศัพท์นี้ได้ (เบอร์โทรศัพท์นี้ถูกใช้ไปแล้ว)";
            }
            else if ($this->model->query('agent')->duplicate($data['agent_tel'])) {
                $arr['error']['agent_tel'] = "ไม่สามารถใช้เบอร์โทรศัพท์นี้ได้ (เบอร์โทรศัพท์นี้ถูกใช้ไปแล้ว)";
            }

            if (isset($_POST['agent_password'])) {
                if (strlen($_POST['agent_password']) < 4) {
                    $arr['error']['agent_tel'] = "รหัสผ่านต้องมี 4 ตัวขึ้นไป";
                } else {
                    $data['agent_password'] = $_POST['agent_password'];
                }
            }

            if (empty($arr['error'])) {


                if (!empty($item)) {
                    // edit
                    $this->model->query('agent')->update($id, $data);
                    $arr['message'] = "แก้ไขข้อมูล Agent เรียบร้อย";
                } else {

                    // insert 
                    $this->model->query('agent')->insert($data);
                    $id = $data['agent_id'];
                    $arr['message'] = "เพิ่ม Agent เรียบร้อย";
                }

                $arr['url'] = !empty($_REQUEST['next']) ? $_REQUEST['next'] : 'refresh';
            }
        } catch (Exception $e) {
            $arr['error'] = $this->_getError($e->getMessage());
        }

        echo json_encode($arr);
    }
    public function change_pass($id = null) {

        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : $id;
        if ($this->format != 'json' || empty($id))
            $this->error();

        $item = $this->model->query('agent')->get($id);
        if (empty($item))
            $this->error();

        // 
        if (!empty($_POST)) {
            try {
                $form = new Form();
                $form->post('password_new')->val('password', 4)
                        ->post('password_confirm');

                $form->submit();
                $dataPost = $form->fetch();

                if ($dataPost['password_new'] != $dataPost['password_confirm']) {
                    $arr['error']['password_confirm'] = 'รหัสผ่านไม่ตรงกัน';
                }

                if (empty($arr['error'])) {

                    // update
                    $this->model->query('agent')->update($id, array('agent_password' => $dataPost['password_new']));

                    $arr['message'] = "แก้ไขข้อมูลเรียบร้อย";
                    // $arr['url'] = 'refresh';
                }
            } catch (Exception $e) {
                $arr['error'] = $this->_getError($e->getMessage());
            }

            echo json_encode($arr);
        } else {
            $this->view->item = $item;
            $this->view->render('agent/dialog/change_pass_form');
        }
    }
    public function del($id = null) {
        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : $id;
        if ($this->format != 'json' || empty($id))
            $this->error();

        $item = $this->model->query('agent')->get($id);
        if (empty($item))
            $this->error();


        if (!empty($_POST)) {

            $this->model->query('agent')->delete($id);
            $arr['message'] = "ลบเรียบร้อย";
            $arr['url'] = URL . "manage/agent";
            echo json_encode($arr);
        } else {

            $this->view->item = $item;
            $this->view->render('agent/dialog/del_form');
        }
    }

    /**/
    /* live */

    public function live_update($id = null) {

        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : $id;
        $data['field'] = isset($_REQUEST['field']) ? $_REQUEST['field'] : null;
        $data['value'] = isset($_REQUEST['val']) ? $_REQUEST['val'] : null;
        if ($this->format != 'json' || empty($id) || empty($data['field']))
            $this->error();

        $item = $this->model->query('agent')->get($id);
        if (empty($item))
            $this->error();

        $form = new Form();

        // $arr['error_message'] = $form->check( array('agent_email', 'agent_name', 'agent_tel', 'agent_note'), $data['field'], $data['value'] );


        if ($data['field'] == 'agent_email') {

            $arr['error_message'] = $form->verify('email', $data['value']);

            if (empty($arr['error_message'])) {

                if ($item['agent_email'] != $data['agent_email'] && $this->model->query('agent')->duplicate($data['agent_email'])) {

                    $arr['error_message'] = "อีเมล์ไม่สามารถใช้ได้ (อีเมล์นี้ถูกใช้ไปแล้ว)";
                }
            }
        } else if ($data['field'] == 'agent_tel') {

            $arr['error_message'] = $form->verify('phone_number', $data['value']);
            if (empty($arr['error_message'])) {

                if ($item['agent_tel'] != $data['value'] && $this->model->query('agent')->duplicate($data['value'])) {
                    $arr['error_message'] = "เบอร์โทรศัพท์ม่สามารถใช้ได้ (เบอร์โทรศัพท์นี้ถูกใช้ไปแล้ว)";
                }
            }
        }

        if (empty($arr['error_message'])) {
            // seve 

            $post[$data['field']] = $data['value'];
            $this->model->query('agent')->update($id, $post);
        }

        $arr['error'] = !empty($arr['error_message']);

        echo json_encode($arr);
    }

}
