<?php      

Class Start extends Controller {
    
    function __construct()
    {   
        parent::__construct();
        parent::__global();
         
    }           
    
    public function index()
    {      
        view_var('title', 'Welcome to Obullo Framework !');
        
        $data['var'] = 'This page generated by Obullo.';
        
        view_var('body', view('view_welcome', $data)); 
        view_app('view_base_layout');
    }
    
}

/* End of file start.php */
/* Location: .application/welcome/controllers/start.php */