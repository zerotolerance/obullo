<?php      

Class Start extends Controller {
    
    function __construct()
    {   
        parent::__construct();
        parent::__global();
        
        loader::database();

        // $this->db->query("SELECT * FROM articles");
        
        $this->db->query("SELECT * FROM articles");
        
        $this->db->prep();        
        $this->db->query("SELECT * FROM articles WHERE jahr = :year AND id = :id");
        
        $result = $this->db->exec(array('year' => '1990', 'id' => '1'));
        // print_r($result->fetch_all(assoc));
        
        $result2 = $this->db->exec(array(':year' => '1997', ':id' => '2'));
        // print_r($result2->fetch_all(assoc));
        
        // $this->db->exec(array('year' => '2001'));
        echo $this->db->last_query(true);
        
        $this->output->profiler();
        
    }                                      
    
    public function index()
    {          
        $this->title = 'Welcome to Obullo Framework !';
        
        $data['var'] = 'This page generated by Obullo Framework.';
        $this->body  = view('view_welcome', $data);
        view_app('view_base_layout');
    }
    
}

/* End of file start.php */
/* Location: .application/welcome/controllers/start.php */