<?php
$router = $this->router;

$router->get('/', function() {
    $this->load->layout("Shared/_layoutPage", ['title' => "wena wn"]);
    return $this->load->view("welcome");
});

$router->get('/usuarios', function() {
    try{
        $CI =& get_instance();
        $CI->load->model('user_model');

        $users = $CI->user_model->all();

        $CI->load->view('verusuarios', [ 'items'=>$users ]);
    }catch(\Throwable $ex){
        echo $ex->stacktrace;
    }
});

$router->post('/api/login', function() {
    $CI =& get_instance();
    $CI->load->model('auth_model');
    $data = json_decode(file_get_contents("php://input"), true);
    echo json_encode($CI->auth_model->login($data));
});
?>