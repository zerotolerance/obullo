<?php      

Class Start extends Controller {
    
    function __construct()
    {   
        parent::__construct();
        parent::__global();
                                 
        loader::model('model_test');
        loader::base_lib('parser');
        
        table::instance();
        
        $this->db->prep();
        $this->db->query("SELECT * FROM articles WHERE id = '1'");
        $this->db->exec();
        
        $this->db->query("SELECT * FROM articles");
        
        $this->output->profiler();
    }                                      

    public function index()
    {  
        $this->title = 'Welcome to Obullo Framework !';
        $this->meta .= meta('keywords', 'obullo, php5, framework');   // You can manually set head tags
                                                                      // or globally using Global views. 
        $this->head .= js('welcome.js');  
        $this->head .= content_script('welcome');
        
        $data['var'] = 'This page generated by Obullo Framework.';
        
        $this->body  = content_view('view_welcome', $data);
        content_app_view('view_base_layout');
    }
    
}
?>